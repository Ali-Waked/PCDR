<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use App\Models\OperationalRecord;
use App\Support\RichText;
use Illuminate\Support\Facades\DB;

/**
 * Domain-specific operational actions that remain within the safe Phase 4
 * boundary: verification, submission evidence, and aggregate reconciliation.
 */
final class OperationalWorkflowService
{
    public function __construct(
        private readonly OperationCatalog $catalog,
        private readonly OperationalTransitionService $transitions,
        private readonly OperationalDocumentService $documents,
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $input */
    public function verifyMeasurement(OperationalRecord $measurement, array $input): OperationalRecord
    {
        if ($measurement->getResource() !== 'indicator_measurements') {
            throw new \InvalidArgumentException('Record is not an indicator measurement.');
        }

        if ($measurement->getAttribute('status') === 'reported') {
            throw new \InvalidArgumentException('Reported measurements cannot be reverified.');
        }

        $verifiedValue = $input['verified_value'] ?? null;
        if ($verifiedValue === null || ! is_numeric($verifiedValue)) {
            throw new \InvalidArgumentException('A numeric verified value is required.');
        }

        $before = ['status' => $measurement->getAttribute('status')];

        DB::transaction(function () use ($measurement, $input, $verifiedValue): void {
            $measurement->forceFill([
                'verified_value' => $verifiedValue,
                'verifier_id' => auth()->id(),
                'verification_notes' => isset($input['verification_notes'])
                    ? RichText::plainText((string) $input['verification_notes'])
                    : null,
                'verified_at' => now(),
            ])->save();

            $this->transitions->transition('indicator_measurements', $measurement, 'verified', [
                'reason' => $input['verification_notes'] ?? null,
                'evidence_media_id' => $input['evidence_media_id'] ?? null,
            ]);
        });

        $this->audit->record(
            'operations.measurement_verified',
            'indicator_measurements',
            (string) $measurement->getKey(),
            $before,
            ['status' => 'verified'],
        );

        return $measurement->refresh();
    }

    /** @param array<string, mixed> $input */
    public function submitProposal(OperationalRecord $proposal, array $input): OperationalRecord
    {
        if ($proposal->getResource() !== 'grant_proposals') {
            throw new \InvalidArgumentException('Record is not a grant proposal.');
        }

        if (! in_array((string) $proposal->getAttribute('status'), ['drafting', 'internal_review'], true)) {
            throw new \InvalidArgumentException('Only drafting or internally reviewed proposals may be submitted.');
        }

        $evidenceId = $input['evidence_media_id'] ?? null;
        if (empty($evidenceId)) {
            throw new \InvalidArgumentException('Submission evidence is required.');
        }

        $before = ['status' => $proposal->getAttribute('status')];

        DB::transaction(function () use ($proposal, $input, $evidenceId): void {
            $proposal->forceFill([
                'submitted_at' => $input['submitted_at'] ?? now()->toDateString(),
                'submission_evidence_media_id' => $evidenceId,
            ])->save();

            $this->documents->attach(
                'grant_proposals',
                (int) $proposal->getKey(),
                Media::query()->findOrFail($evidenceId),
                'submission',
                auth()->id(),
            );

            $this->transitions->transition('grant_proposals', $proposal, 'submitted', [
                'reason' => $input['reason'] ?? null,
                'evidence_media_id' => $evidenceId,
            ]);
        });

        $this->audit->record(
            'operations.proposal_submitted',
            'grant_proposals',
            (string) $proposal->getKey(),
            $before,
            ['status' => 'submitted'],
        );

        return $proposal->refresh();
    }

    /** @param array<string, mixed> $input */
    public function reconcileDistribution(OperationalRecord $record, array $input): OperationalRecord
    {
        if ($record->getResource() !== 'distribution_records') {
            throw new \InvalidArgumentException('Record is not a distribution record.');
        }

        $verified = $input['verified_quantity'] ?? $record->getAttribute('verified_quantity');
        if ($verified === null || ! is_numeric($verified)) {
            throw new \InvalidArgumentException('A verified quantity is required.');
        }

        $before = ['status' => $record->getAttribute('status')];

        DB::transaction(function () use ($record, $input, $verified): void {
            $record->forceFill([
                'verified_quantity' => $verified,
                'reconciliation_notes' => isset($input['reconciliation_notes'])
                    ? RichText::plainText((string) $input['reconciliation_notes'])
                    : null,
            ])->save();

            $this->transitions->transition('distribution_records', $record, 'reconciled', [
                'reason' => $input['reconciliation_notes'] ?? null,
                'evidence_media_id' => $input['evidence_media_id'] ?? null,
            ]);
        });

        $this->audit->record(
            'operations.distribution_reconciled',
            'distribution_records',
            (string) $record->getKey(),
            $before,
            ['status' => 'reconciled'],
        );

        return $record->refresh();
    }
}
