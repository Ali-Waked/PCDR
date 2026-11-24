<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\GenerateAuditArchive;
use App\Services\AuditArchiveService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('pcdr:audit:archive {month? : Archive a specific YYYY-MM month; defaults to the previous complete month} {--force : Retry a pending, processing, or failed archive} {--queue : Dispatch archive generation to the queue}')]
#[Description('Create and verify a private monthly audit log archive')]
final class ArchiveAuditMonth extends Command
{
    public function handle(AuditArchiveService $archives): int
    {
        if (! (bool) config('audit_archive.enabled', true)) {
            $this->error('Audit archiving is disabled by configuration.');

            return self::FAILURE;
        }

        $month = $this->argument('month');
        $month = is_string($month) && $month !== '' ? $month : null;
        $force = (bool) $this->option('force');

        if ((bool) $this->option('queue')) {
            GenerateAuditArchive::dispatch($month, null, $force);
            $this->info('Audit archive generation was queued.');

            return self::SUCCESS;
        }

        try {
            $archive = $archives->createArchive($month, null, $force);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Archive {$archive->period_key} verified: {$archive->record_count} records.");

        return self::SUCCESS;
    }
}
