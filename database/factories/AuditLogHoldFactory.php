<?php

namespace Database\Factories;

use App\Models\AuditLogHold;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLogHold>
 */
class AuditLogHoldFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'label' => fake()->sentence(3),
            'reason' => fake()->sentence(8),
            'scope_type' => AuditLogHold::SCOPE_GLOBAL,
            'subject_type' => null,
            'subject_id' => null,
            'actor_id' => null,
            'period_start' => null,
            'period_end' => null,
            'expires_at' => null,
            'released_at' => null,
        ];
    }

    public function subject(string $type, string $id): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope_type' => AuditLogHold::SCOPE_SUBJECT,
            'subject_type' => $type,
            'subject_id' => $id,
        ]);
    }

    public function actor(int $actorId): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope_type' => AuditLogHold::SCOPE_ACTOR,
            'actor_id' => $actorId,
        ]);
    }

    public function period(\DateTimeInterface $start, \DateTimeInterface $end): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope_type' => AuditLogHold::SCOPE_PERIOD,
            'period_start' => $start,
            'period_end' => $end,
        ]);
    }
}
