<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditArchive;
use App\Services\AuditRetentionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('pcdr:audit:cleanup {archive : Archive ID or YYYY-MM period} {--dry-run : Only produce a report} {--execute : Delete eligible rows after confirmation} {--confirm= : Exact confirmation phrase required for deletion} {--requested-by= : Optional user ID requesting cleanup}')]
#[Description('Report or execute retention cleanup for one verified audit archive')]
final class AuditCleanup extends Command
{
    public function handle(AuditRetentionService $retention): int
    {
        $identifier = (string) $this->argument('archive');
        $archive = AuditArchive::query()
            ->when(ctype_digit($identifier), fn ($query) => $query->where('id', (int) $identifier))
            ->when(! ctype_digit($identifier), fn ($query) => $query->where('period_key', $identifier))
            ->first();

        if ($archive === null) {
            $this->error("Archive {$identifier} was not found.");

            return self::FAILURE;
        }

        try {
            if ((bool) $this->option('dry-run') || ! (bool) $this->option('execute')) {
                $runs = $retention->dryRun($archive->id);
                $this->info('Dry-run report created: run #'.$runs->first()?->id.'.');

                return self::SUCCESS;
            }

            $confirm = $this->option('confirm');
            $confirm = is_string($confirm) ? $confirm : '';
            $requestedBy = $this->option('requested-by');

            $retention->dryRun($archive->id);
            $run = $retention->execute(
                $archive,
                $confirm,
                is_numeric($requestedBy) ? (int) $requestedBy : null,
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Cleanup #{$run->id} completed: {$run->deleted_count} records deleted.");

        return self::SUCCESS;
    }
}
