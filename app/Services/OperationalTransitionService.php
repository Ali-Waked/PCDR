<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DomainTransition;
use App\Models\OperationalRecord;
use App\Support\RichText;
use Illuminate\Support\Facades\DB;

/**
 * Applies the explicit lifecycle transitions declared in config/operations.php.
 */
final class OperationalTransitionService
{
    public function __construct(
        private readonly OperationCatalog $catalog,
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $input */
    public function transition(string $resource, OperationalRecord $record, string $to, array $input = []): OperationalRecord
    {
        $from = $record->getAttribute('status') === null
            ? null
            : (string) $record->getAttribute('status');

        $this->catalog->assertTransition($resource, $from, $to);

        $evidenceId = $input['evidence_media_id'] ?? null;
        if ($this->catalog->requiresEvidence($resource, $to) && empty($evidenceId)) {
            throw new \InvalidArgumentException('Evidence is required for this status.');
        }

        $before = ['status' => $from];

        DB::transaction(function () use ($resource, $record, $from, $to, $input, $evidenceId): void {
            $record->forceFill(['status' => $to])->save();

            DomainTransition::query()->create([
                'resource' => $resource,
                'owner_id' => $record->getKey(),
                'status_field' => 'status',
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => auth()->id(),
                'reason' => isset($input['reason']) ? RichText::plainText((string) $input['reason']) : null,
                'evidence_media_id' => $evidenceId,
                'created_at' => now(),
            ]);
        });

        $this->audit->record(
            'operations.status_transitioned',
            $resource,
            (string) $record->getKey(),
            $before,
            ['status' => $to],
        );

        return $record->refresh();
    }
}
