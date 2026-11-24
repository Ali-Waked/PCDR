<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AuditRetentionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('pcdr:audit:hold {--scope=global : global, subject, actor, or period} {--subject-type= : Subject type for a subject hold} {--subject-id= : Subject ID for a subject hold} {--actor= : Actor user ID for an actor hold} {--from= : Period start YYYY-MM-DD} {--to= : Period end YYYY-MM-DD} {--expires= : Optional expiry date YYYY-MM-DD} {--label= : Short hold label} {--reason= : Legal or operational reason} {--created-by= : Optional user ID creating the hold}')]
#[Description('Place a legal hold that blocks retention cleanup')]
final class HoldAuditLogs extends Command
{
    public function handle(AuditRetentionService $retention): int
    {
        $opts = $this->options();

        try {
            $hold = $retention->createHold([
                'scope_type' => $opts['scope'] ?? 'global',
                'subject_type' => $opts['subject-type'] ?? null,
                'subject_id' => $opts['subject-id'] ?? null,
                'actor_id' => $opts['actor'] ?? null,
                'period_start' => $opts['from'] ?? null,
                'period_end' => $opts['to'] ?? null,
                'expires_at' => $opts['expires'] ?? null,
                'label' => $opts['label'] ?? null,
                'reason' => $opts['reason'] ?? null,
            ], is_numeric($opts['created-by'] ?? null) ? (int) $opts['created-by'] : null);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Legal hold #{$hold->id} created ({$hold->scope_type}).");

        return self::SUCCESS;
    }
}
