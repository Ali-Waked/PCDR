<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Events\Phase5Event;
use App\Models\DomainTransition;
use App\Models\Phase5\Referral;
use App\Models\Phase5\ReferralEvent;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Phase5Support;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Creates and advances non-identifying referrals. Source records are never
 * returned to a caller who cannot view the source directly.
 */
final class ReferralService
{
    private const PER_PAGE = 15;

    /** Source type to backing table. "operational_record" has no backing table. */
    private const SOURCE_TABLES = [
        'project' => 'projects',
        'activity' => 'activities',
        'accountability_case' => 'accountability_cases',
    ];

    public function __construct(
        private readonly Phase5Authorization $authorization,
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): Referral
    {
        $sourceType = (string) $data['source_type'];
        $sourceId = (int) $data['source_id'];
        $destinationType = (string) $data['destination_type'];
        $destinationId = (int) $data['destination_id'];

        $this->assertSource($user, $sourceType, $sourceId);
        $this->assertDestination($destinationType, $destinationId);

        $referral = null;

        DB::transaction(function () use (&$referral, $user, $data, $sourceType, $sourceId, $destinationType, $destinationId): void {
            /** @var Referral $referral */
            $referral = Referral::create([
                'reference' => $this->reference('REF'),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'destination_type' => $destinationType,
                'destination_id' => $destinationId,
                'reason' => Phase5Support::clean(
                    (string) $data['reason'],
                    (int) config('phase5.referral.max_reason_chars', 4000),
                ),
                'status' => 'draft',
                'created_by' => $user->id,
                'due_at' => isset($data['due_at']) ? $data['due_at'] : null,
            ]);

            ReferralEvent::query()->create([
                'referral_id' => $referral->getKey(),
                'action' => 'created',
                'from_status' => null,
                'to_status' => 'draft',
                'actor_id' => $user->id,
                'note' => null,
                'created_at' => now(),
            ]);

            $this->audit->record('referral.created', 'referral', (string) $referral->getKey(), null, [
                'reference' => $referral->reference,
                'source_type' => $sourceType,
                'destination_type' => $destinationType,
                'status' => 'draft',
            ]);
        });

        return $referral->refresh();
    }

    public function paginateReferrals(User $user): LengthAwarePaginator
    {
        $query = Referral::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! $user->isPlatformOwner()) {
            $query->where(function ($query) use ($user): void {
                $query->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        $paginator = $query->paginate(self::PER_PAGE);
        $visible = $paginator->getCollection()
            ->map(fn (Referral $referral): ?array => $this->canView($user, $referral)
                ? $this->serializeReferral($referral)
                : null)
            ->filter(static fn (?array $referral): bool => $referral !== null)
            ->values();

        $paginator->setCollection($visible);

        return $paginator;
    }

    public function showReferral(User $user, Referral $referral): array
    {
        $this->authorization->assertCanViewResource($user, 'referral', (int) $referral->getKey());

        return [
            'referral' => $this->serializeReferral($referral),
            'events' => $referral->events()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->map(static fn (ReferralEvent $event): array => [
                    'id' => $event->getKey(),
                    'action' => $event->action,
                    'fromStatus' => $event->from_status,
                    'toStatus' => $event->to_status,
                    'createdAt' => $event->created_at?->toISOString(),
                ])->all(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function transition(User $user, Referral $referral, array $data): Referral
    {
        $this->authorization->assertCanViewResource($user, 'referral', (int) $referral->getKey());

        $from = (string) $referral->status;
        $to = (string) $data['to_status'];
        $this->authorization->assertTransition($user, 'referral', $from, $to);

        DB::transaction(function () use ($user, $referral, $data, $from, $to): void {
            $referral->forceFill(['status' => $to])->save();

            ReferralEvent::query()->create([
                'referral_id' => $referral->getKey(),
                'action' => 'status_changed',
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => $user->id,
                'note' => isset($data['note'])
                    ? Phase5Support::clean((string) $data['note'], 2000)
                    : null,
                'created_at' => now(),
            ]);

            DomainTransition::query()->create([
                'resource' => 'referrals',
                'owner_id' => $referral->getKey(),
                'status_field' => 'status',
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => $user->id,
                'reason' => null,
                'evidence_media_id' => null,
                'created_at' => now(),
            ]);

            $this->audit->record('referral.status_changed', 'referral', (string) $referral->getKey(), [
                'status' => $from,
            ], [
                'reference' => $referral->reference,
                'status' => $to,
            ]);

            event(new Phase5Event('referral.status_changed', [
                'reference' => $referral->reference,
                'from' => $from,
                'to' => $to,
            ], 'referral', (int) $referral->getKey(), (int) $user->id));
        });

        return $referral->refresh();
    }

    private function reference(string $prefix): string
    {
        // Cast before uppercasing: Laravel 13 returns a Ulid value object.
        return strtoupper($prefix).'-'.strtoupper((string) Str::ulid());
    }

    private function canView(User $user, Referral $referral): bool
    {
        if ($this->authorization->allows($user, 'referrals.view') && $user->isPlatformOwner()) {
            return true;
        }

        return $this->authorization->canViewResource($user, (string) $referral->source_type, (int) $referral->source_id);
    }

    private function assertSource(User $user, string $sourceType, int $sourceId): void
    {
        $table = self::SOURCE_TABLES[$sourceType] ?? null;

        if ($table === null) {
            throw new InvalidArgumentException('Unsupported referral source type.');
        }

        $exists = DB::table($table)
            ->where('id', $sourceId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $exists) {
            abort(404, __('phase5.not_found'));
        }

        $this->authorization->assertCanViewResource($user, $sourceType, $sourceId);
    }

    private function assertDestination(string $destinationType, int $destinationId): void
    {
        $approved = match ($destinationType) {
            'partner' => DB::table('partners')
                ->where('id', $destinationId)
                ->whereNull('deleted_at')
                ->where('relationship_status', 'active')
                ->exists(),
            'internal_team' => DB::table('users')
                ->where('id', $destinationId)
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->exists(),
            'service' => DB::table('interventions')
                ->where('id', $destinationId)
                ->whereNull('deleted_at')
                ->whereIn('status', ['active', 'completed'])
                ->exists(),
            default => false,
        };

        if (! $approved) {
            throw new InvalidArgumentException('Referral destination is not an approved active entity.');
        }
    }

    /** @return array<string, mixed> */
    private function serializeReferral(Referral $referral): array
    {
        // Source and destination identifiers and the free-text reason are
        // intentionally omitted: only non-identifying references are exposed.
        return [
            'reference' => $referral->reference,
            'status' => $referral->status,
            'sourceType' => $referral->source_type,
            'destinationType' => $referral->destination_type,
            'createdAt' => $referral->created_at?->toISOString(),
            'updatedAt' => $referral->updated_at?->toISOString(),
            'dueAt' => $referral->due_at?->toISOString(),
        ];
    }
}
