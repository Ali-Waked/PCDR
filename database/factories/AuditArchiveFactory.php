<?php

namespace Database\Factories;

use App\Models\AuditArchive;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditArchive>
 */
class AuditArchiveFactory extends Factory
{
    private static int $monthOffset = 2;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $start = now()->startOfMonth()->subMonths(self::$monthOffset++);

        return [
            'period_key' => $start->format('Y-m'),
            'name' => 'audit-logs-'.$start->format('Y-m').'.zip',
            'period_start' => $start,
            'period_end' => $start->copy()->addMonth(),
            'cutoff_at' => $start->copy()->addMonth()->subDay(),
            'record_count' => 0,
            'file_path' => 'audit-archives/'.$start->format('Y-m').'/audit-logs-'.$start->format('Y-m').'.zip',
            'file_size' => 0,
            'checksums' => [],
            'manifest' => [],
            'status' => AuditArchive::STATUS_VERIFIED,
            'schema_version' => '1.0',
            'attempts' => 1,
            'verified_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AuditArchive::STATUS_FAILED,
            'verified_at' => null,
            'error' => 'Archive generation failed.',
        ]);
    }

    public function pruned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AuditArchive::STATUS_PRUNED,
            'pruned_at' => now(),
        ]);
    }
}
