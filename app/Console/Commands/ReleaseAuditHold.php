<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AuditRetentionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('pcdr:audit:release-hold {hold : Legal hold ID} {--released-by= : Optional user ID releasing the hold}')]
#[Description('Release a legal hold after authorized review')]
final class ReleaseAuditHold extends Command
{
    public function handle(AuditRetentionService $retention): int
    {
        $releasedBy = $this->option('released-by');

        try {
            $hold = $retention->releaseHold(
                (int) $this->argument('hold'),
                is_numeric($releasedBy) ? (int) $releasedBy : null,
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Legal hold #{$hold->id} released.");

        return self::SUCCESS;
    }
}
