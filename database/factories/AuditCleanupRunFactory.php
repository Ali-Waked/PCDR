<?php

namespace Database\Factories;

use App\Models\AuditCleanupRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditCleanupRun>
 */
class AuditCleanupRunFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'status' => AuditCleanupRun::STATUS_COMPLETED,
            'mode' => AuditCleanupRun::MODE_DRY_RUN,
            'eligible_count' => 0,
            'deleted_count' => 0,
            'skipped_hold_count' => 0,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'report' => [],
        ];
    }

    public function execute(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mode' => AuditCleanupRun::MODE_EXECUTE,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AuditCleanupRun::STATUS_FAILED,
            'error' => 'Cleanup failed.',
        ]);
    }
}
