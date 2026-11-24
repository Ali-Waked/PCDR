<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AuditRetentionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('pcdr:audit:cleanup-due {--execute : Execute cleanup after explicit confirmation; default is report only} {--confirm= : Exact confirmation phrase required for --execute}')]
#[Description('Report all retention-due archives; destructive execution requires explicit confirmation')]
final class AuditCleanupDue extends Command
{
    public function handle(AuditRetentionService $retention): int
    {
        $shouldExecute = (bool) $this->option('execute');
        $confirm = $this->option('confirm');
        $confirm = is_string($confirm) ? $confirm : '';

        try {
            $runs = $retention->dryRun();

            if (! $shouldExecute) {
                $this->info('Retention reports created: '.$runs->count().'. No records were deleted.');

                return self::SUCCESS;
            }

            $deleted = 0;
            foreach ($runs as $run) {
                if ($run->audit_archive_id === null) {
                    continue;
                }

                $archive = $run->archive;
                if ($archive === null) {
                    continue;
                }

                $deleted += $retention->execute($archive, $confirm)->deleted_count;
            }

            $this->info("Retention cleanup completed: {$deleted} records deleted.");
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
