<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditArchive;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pcdr:audit:archives {--status= : Filter by pending, processing, verified, failed, or pruned} {--limit=20 : Maximum rows to display}')]
#[Description('List monthly audit archive registry entries')]
final class ListAuditArchives extends Command
{
    public function handle(): int
    {
        $status = $this->option('status');
        $status = is_string($status) ? trim($status) : '';
        $limit = max(1, min(100, (int) $this->option('limit')));

        $archives = AuditArchive::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('period_start')
            ->limit($limit)
            ->get();

        if ($archives->isEmpty()) {
            $this->warn('No audit archives matched.');

            return self::SUCCESS;
        }

        $this->table(
            ['Period', 'Status', 'Records', 'Attempts', 'Verified', 'File'],
            $archives->map(fn (AuditArchive $archive): array => [
                $archive->period_key,
                $archive->status,
                (string) $archive->record_count,
                (string) $archive->attempts,
                $archive->verified_at?->toISOString() ?? '—',
                $archive->file_path,
            ])->all(),
        );

        return self::SUCCESS;
    }
}
