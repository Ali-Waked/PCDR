<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditArchive;
use App\Models\AuditLog;
use App\Support\AuditRedactor;
use App\Support\CsvSafeWriter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

final class AuditArchiveService
{
    private const CSV_NAME = 'audit-logs.csv';

    private const JSONL_NAME = 'audit-logs.jsonl';

    private const MANIFEST_NAME = 'manifest.json';

    public function __construct(private readonly AuditLifecycleNotifier $notifier) {}

    /**
     * @return array{key: string, start: Carbon, end: Carbon, cutoff: Carbon}
     */
    public function resolvePeriod(?string $month): array
    {
        $month = $month === null ? '' : trim($month);

        if ($month === '') {
            $start = now()->startOfMonth()->subMonth();
        } else {
            if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
                throw new RuntimeException('The archive month must use YYYY-MM format.');
            }

            $parsed = Carbon::createFromFormat('!Y-m-d', $month.'-01');
            if ($parsed === false || $parsed->format('Y-m') !== $month) {
                throw new RuntimeException('The archive month is invalid.');
            }

            $start = $parsed;
        }

        $end = $start->copy()->addMonth();
        if ($end->isFuture()) {
            throw new RuntimeException('Only complete months may be archived.');
        }

        return [
            'key' => $start->format('Y-m'),
            'start' => $start,
            'end' => $end,
            'cutoff' => now(),
        ];
    }

    public function createArchive(?string $month, ?int $initiatedBy = null, bool $force = false): AuditArchive
    {
        $period = $this->resolvePeriod($month);
        $existing = AuditArchive::query()->where('period_key', $period['key'])->first();

        if ($existing !== null) {
            if (! $force || ! in_array($existing->status, [
                AuditArchive::STATUS_PENDING,
                AuditArchive::STATUS_PROCESSING,
                AuditArchive::STATUS_FAILED,
            ], true)) {
                throw new RuntimeException('An archive already exists for this period. Use --force to retry a failed attempt.');
            }

            $archive = $existing;
            $archive->update([
                'status' => AuditArchive::STATUS_PROCESSING,
                'attempts' => $archive->attempts + 1,
                'error' => null,
                'cutoff_at' => $period['cutoff'],
                'initiated_by' => $initiatedBy ?? $archive->initiated_by,
            ]);

            if ($archive->file_path !== '' && Storage::disk($this->disk())->exists($archive->file_path)) {
                Storage::disk($this->disk())->delete($archive->file_path);
            }
        } else {
            $archive = AuditArchive::create([
                'period_key' => $period['key'],
                'name' => 'audit-logs-'.$period['key'].'.zip',
                'period_start' => $period['start'],
                'period_end' => $period['end'],
                'cutoff_at' => $period['cutoff'],
                'record_count' => 0,
                'file_path' => $this->storagePath($period['key']),
                'status' => AuditArchive::STATUS_PROCESSING,
                'schema_version' => (string) config('audit_archive.export.schema_version', '1.0'),
                'attempts' => 1,
                'initiated_by' => $initiatedBy,
            ]);
        }

        $tempDir = $this->temporaryDirectory();

        try {
            [$csvPath, $jsonlPath, $exportedCount] = $this->buildFiles($archive, $period, $tempDir);

            $checksums = [
                self::CSV_NAME => hash_file('sha256', $csvPath) ?: '',
                self::JSONL_NAME => hash_file('sha256', $jsonlPath) ?: '',
            ];

            $manifest = $this->buildManifest($archive, $period, $exportedCount, $csvPath, $jsonlPath);
            $manifestPath = $tempDir.'/'.self::MANIFEST_NAME;
            file_put_contents($manifestPath, CsvSafeWriter::json($manifest).PHP_EOL, LOCK_EX);
            $checksums[self::MANIFEST_NAME] = hash_file('sha256', $manifestPath) ?: '';

            $zipPath = $tempDir.'/'.$archive->name;
            $this->buildZip($zipPath, [$csvPath => self::CSV_NAME, $jsonlPath => self::JSONL_NAME, $manifestPath => self::MANIFEST_NAME]);

            $inspection = $this->inspectZip($zipPath, $checksums, $manifest, $exportedCount);

            $this->storeArchive($archive->file_path, $zipPath);

            DB::transaction(function () use ($archive, $period, $exportedCount, $checksums, $manifest, $inspection): void {
                $markedCount = $this->markLogsArchived($archive, $period);

                if ($markedCount !== $exportedCount) {
                    throw new RuntimeException(
                        "Archive count mismatch: exported {$exportedCount} records, marked {$markedCount}.",
                    );
                }

                $archive->update([
                    'status' => AuditArchive::STATUS_VERIFIED,
                    'record_count' => $exportedCount,
                    'file_size' => $inspection['file_size'],
                    'checksums' => $checksums,
                    'manifest' => $manifest,
                    'verified_at' => now(),
                    'error' => null,
                ]);
            });

            $this->notifier->archiveCreated($archive->refresh());

            return $archive;
        } catch (Throwable $exception) {
            $archive->update([
                'status' => AuditArchive::STATUS_FAILED,
                'error' => Str::limit($exception->getMessage(), 1000),
            ]);

            $this->notifier->archiveFailed($archive, $exception->getMessage());

            throw $exception;
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /** @return array<string, mixed> */
    public function verifyArchive(AuditArchive $archive, bool $persist = true): array
    {
        $disk = Storage::disk($this->disk());

        if (! $disk->exists($archive->file_path)) {
            throw new RuntimeException('The archive file is missing from private storage.');
        }

        $tempDir = $this->temporaryDirectory();
        $zipPath = $tempDir.'/verify.zip';

        try {
            $stream = $disk->readStream($archive->file_path);
            if ($stream === false) {
                throw new RuntimeException('Unable to read the archive file.');
            }

            $contents = stream_get_contents($stream);
            fclose($stream);
            if ($contents === false || file_put_contents($zipPath, $contents, LOCK_EX) === false) {
                throw new RuntimeException('Unable to stage the archive for verification.');
            }

            $inspection = $this->inspectZip(
                $zipPath,
                is_array($archive->checksums) ? $archive->checksums : null,
                is_array($archive->manifest) ? $archive->manifest : null,
                (int) $archive->record_count,
            );

            if ($persist) {
                $archive->update([
                    'status' => AuditArchive::STATUS_VERIFIED,
                    'verified_at' => now(),
                    'error' => null,
                ]);
            }

            return $inspection;
        } catch (Throwable $exception) {
            if ($persist) {
                $archive->update([
                    'status' => AuditArchive::STATUS_FAILED,
                    'error' => Str::limit($exception->getMessage(), 1000),
                ]);
            }

            throw $exception;
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /** @return array<string, mixed> */
    public function describeArchive(AuditArchive $archive): array
    {
        return [
            'id' => $archive->id,
            'name' => $archive->name,
            'period_key' => $archive->period_key,
            'period_start' => $archive->period_start?->toISOString(),
            'period_end' => $archive->period_end?->toISOString(),
            'cutoff_at' => $archive->cutoff_at?->toISOString(),
            'record_count' => $archive->record_count,
            'file_size' => $archive->file_size,
            'status' => $archive->status,
            'attempts' => $archive->attempts,
            'checksums' => $archive->checksums,
            'manifest' => $archive->manifest,
            'error' => $archive->error,
            'verified_at' => $archive->verified_at?->toISOString(),
            'pruned_at' => $archive->pruned_at?->toISOString(),
            'last_cleanup_at' => $archive->last_cleanup_at?->toISOString(),
            'created_at' => $archive->created_at?->toISOString(),
            'initiated_by' => $archive->initiator?->name,
        ];
    }

    /** @param array{key: string, start: Carbon, end: Carbon, cutoff: Carbon} $period */
    private function eligibleQuery(AuditArchive $archive, array $period)
    {
        return AuditLog::query()
            ->where('created_at', '>=', $period['start'])
            ->where('created_at', '<', $period['end'])
            ->where('created_at', '<=', $period['cutoff'])
            ->where(function ($query) use ($archive): void {
                $query->whereNull('archive_id')
                    ->orWhere('archive_id', $archive->id);
            });
    }

    /**
     * @param  array{key: string, start: Carbon, end: Carbon, cutoff: Carbon}  $period
     * @return array{0: string, 1: string, 2: int}
     */
    private function buildFiles(AuditArchive $archive, array $period, string $tempDir): array
    {
        $csvPath = $tempDir.'/'.self::CSV_NAME;
        $jsonlPath = $tempDir.'/'.self::JSONL_NAME;
        $csv = fopen($csvPath, 'wb');
        $jsonl = fopen($jsonlPath, 'wb');

        if ($csv === false || $jsonl === false) {
            throw new RuntimeException('Unable to open archive output files.');
        }

        $count = 0;

        try {
            // UTF-8 BOM keeps Arabic readable in common spreadsheet applications.
            fwrite($csv, "\xEF\xBB\xBF");
            fwrite($csv, CsvSafeWriter::row([
                'id', 'created_at', 'actor', 'actor_id', 'action',
                'subject_type', 'subject_id', 'request_id',
                'before', 'after', 'context',
            ]));

            $this->eligibleQuery($archive, $period)
                ->with('actor:id,name')
                ->orderBy('id')
                ->chunkById((int) config('audit_archive.export.batch_size', 500), function ($logs) use ($csv, $jsonl, &$count): void {
                    foreach ($logs as $log) {
                        $row = [
                            'id' => $log->id,
                            'created_at' => $log->created_at?->toISOString(),
                            'actor' => $log->actor?->name ?? 'system',
                            'actor_id' => $log->actor_id,
                            'action' => $log->action,
                            'subject_type' => $log->subject_type,
                            'subject_id' => $log->subject_id,
                            'request_id' => $log->request_id,
                            'before' => AuditRedactor::redact(is_array($log->before) ? $log->before : []),
                            'after' => AuditRedactor::redact(is_array($log->after) ? $log->after : []),
                            'context' => AuditRedactor::redact(is_array($log->context) ? $log->context : []),
                        ];

                        fwrite($csv, CsvSafeWriter::row([
                            $row['id'], $row['created_at'], $row['actor'], $row['actor_id'],
                            $row['action'], $row['subject_type'], $row['subject_id'],
                            $row['request_id'], $row['before'], $row['after'], $row['context'],
                        ]));
                        fwrite($jsonl, CsvSafeWriter::json($row).PHP_EOL);
                        $count++;
                    }
                });
        } finally {
            fclose($csv);
            fclose($jsonl);
        }

        return [$csvPath, $jsonlPath, $count];
    }

    /**
     * @param  array{key: string, start: Carbon, end: Carbon, cutoff: Carbon}  $period
     * @return array<string, mixed>
     */
    private function buildManifest(AuditArchive $archive, array $period, int $count, string $csvPath, string $jsonlPath): array
    {
        return [
            'archive_id' => $archive->id,
            'archive_name' => $archive->name,
            'period' => $period['key'],
            'period_start' => $period['start']->toISOString(),
            'period_end' => $period['end']->toISOString(),
            'cutoff_at' => $period['cutoff']->toISOString(),
            'exported_at' => $period['cutoff']->toISOString(),
            'record_count' => $count,
            'csv_filename' => self::CSV_NAME,
            'jsonl_filename' => self::JSONL_NAME,
            'file_sizes' => [
                self::CSV_NAME => filesize($csvPath) ?: 0,
                self::JSONL_NAME => filesize($jsonlPath) ?: 0,
            ],
            'checksums' => [
                self::CSV_NAME => hash_file('sha256', $csvPath) ?: '',
                self::JSONL_NAME => hash_file('sha256', $jsonlPath) ?: '',
            ],
            'schema_version' => (string) config('audit_archive.export.schema_version', '1.0'),
            'application_version' => config('audit_archive.export.application_version') ?? app()->version(),
            'git_commit' => config('audit_archive.export.git_commit'),
            'verification_status' => 'verified',
        ];
    }

    /** @param array<string, string> $paths */
    private function buildZip(string $zipPath, array $paths): void
    {
        $zip = new ZipArchive;
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            throw new RuntimeException('Unable to create the archive ZIP file.');
        }

        try {
            foreach ($paths as $path => $name) {
                if (! $zip->addFile($path, $name)) {
                    throw new RuntimeException("Unable to add {$name} to the archive.");
                }
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  array<string, string>|null  $expectedChecksums
     * @param  array<string, mixed>|null  $expectedManifest
     * @return array<string, mixed>
     */
    private function inspectZip(string $zipPath, ?array $expectedChecksums, ?array $expectedManifest, int $expectedCount): array
    {
        $zip = new ZipArchive;
        $opened = $zip->open($zipPath);

        if ($opened !== true) {
            throw new RuntimeException('The archive ZIP file could not be opened.');
        }

        $tempDir = $this->temporaryDirectory();

        try {
            $contents = [];
            $actualChecksums = [];

            foreach ([self::CSV_NAME, self::JSONL_NAME, self::MANIFEST_NAME] as $name) {
                $content = $zip->getFromName($name);
                if ($content === false) {
                    throw new RuntimeException("Archive is missing {$name}.");
                }

                $contents[$name] = $content;
                $actualChecksums[$name] = hash('sha256', $content);
                file_put_contents($tempDir.'/'.$name, $content, LOCK_EX);
            }

            if ($expectedChecksums !== null) {
                foreach ($expectedChecksums as $name => $checksum) {
                    if (! isset($actualChecksums[$name]) || ! hash_equals((string) $checksum, $actualChecksums[$name])) {
                        throw new RuntimeException("Checksum verification failed for {$name}.");
                    }
                }
            }

            $manifest = json_decode($contents[self::MANIFEST_NAME], true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($manifest)) {
                throw new RuntimeException('Archive manifest is not a JSON object.');
            }

            $jsonlCount = trim($contents[self::JSONL_NAME]) === ''
                ? 0
                : substr_count(rtrim($contents[self::JSONL_NAME], "\r\n"), "\n") + 1;
            $csvCount = $this->countCsvRows($tempDir.'/'.self::CSV_NAME);

            if ((int) ($manifest['record_count'] ?? -1) !== $jsonlCount || $jsonlCount !== $csvCount) {
                throw new RuntimeException('Archive record counts are inconsistent.');
            }

            if ($expectedCount >= 0 && $expectedCount !== $jsonlCount) {
                throw new RuntimeException('Archive record count does not match the registry.');
            }

            if ($expectedManifest !== null) {
                foreach (['period', 'period_start', 'period_end', 'record_count'] as $key) {
                    if (($manifest[$key] ?? null) != ($expectedManifest[$key] ?? null)) {
                        throw new RuntimeException("Archive manifest field {$key} does not match the registry.");
                    }
                }
            }

            return [
                'checksums' => $actualChecksums,
                'manifest' => $manifest,
                'record_count' => $jsonlCount,
                'file_size' => filesize($zipPath) ?: 0,
                'verified_at' => now()->toISOString(),
            ];
        } finally {
            $zip->close();
            File::deleteDirectory($tempDir);
        }
    }

    private function countCsvRows(string $path): int
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read archive CSV.');
        }

        try {
            $rows = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if ($rows === 0 && isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
                    if ($row[0] === 'id') {
                        $rows++;

                        continue;
                    }
                }

                $rows++;
            }

            return max(0, $rows - 1);
        } finally {
            fclose($handle);
        }
    }

    /** @param array{key: string, start: Carbon, end: Carbon, cutoff: Carbon} $period */
    private function markLogsArchived(AuditArchive $archive, array $period): int
    {
        $marked = 0;
        $batchSize = (int) config('audit_archive.export.batch_size', 500);

        $this->eligibleQuery($archive, $period)
            ->select('id')
            ->orderBy('id')
            ->chunkById($batchSize, function ($logs) use ($archive, $period, &$marked): void {
                $ids = $logs->pluck('id')->all();
                if ($ids === []) {
                    return;
                }

                // Retry-safe: count records already linked to this archive
                // before updating the remaining records, so each exported row
                // is counted exactly once.
                $alreadyLinked = AuditLog::query()
                    ->whereIn('id', $ids)
                    ->where('archive_id', $archive->id)
                    ->whereNotNull('archived_at')
                    ->count();

                $updated = AuditLog::query()
                    ->whereIn('id', $ids)
                    ->whereNull('archive_id')
                    ->update([
                        'archive_id' => $archive->id,
                        'archived_at' => $period['cutoff'],
                    ]);

                $marked += $alreadyLinked + $updated;
            });

        return $marked;
    }

    private function storeArchive(string $path, string $zipPath): void
    {
        $stream = fopen($zipPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Unable to read the generated archive.');
        }

        try {
            $stored = Storage::disk($this->disk())->put($path, $stream, 'private');
            if (! $stored) {
                throw new RuntimeException('Unable to store the archive in private storage.');
            }
        } finally {
            fclose($stream);
        }
    }

    private function storagePath(string $periodKey): string
    {
        $prefix = trim((string) config('audit_archive.path_prefix', 'audit-archives'), '/');

        return $prefix === ''
            ? 'audit-logs-'.$periodKey.'.zip'
            : $prefix.'/'.$periodKey.'/audit-logs-'.$periodKey.'.zip';
    }

    private function temporaryDirectory(): string
    {
        $path = sys_get_temp_dir().'/pcdr-audit-'.Str::lower((string) Str::ulid());
        File::ensureDirectoryExists($path, 0700);

        return $path;
    }

    private function disk(): string
    {
        return (string) config('audit_archive.disk', 'private');
    }
}
