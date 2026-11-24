<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Events\Phase5Event;
use App\Exceptions\Phase5GovernanceException;
use App\Models\DomainTransition;
use App\Models\Phase5\AccountabilityCase;
use App\Models\Phase5\AccountabilityEvent;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Phase5Support;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Owns accountability state changes. Only allow-listed, non-identifying fields
 * reach persistence; blocked categories fail before any write occurs.
 */
final class AccountabilityService
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly Phase5Authorization $authorization,
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): AccountabilityCase
    {
        $category = (string) $data['category'];
        $this->assertCategoryEnabled($category);

        $case = null;

        DB::transaction(function () use (&$case, $data, $category): void {
            /** @var AccountabilityCase $case */
            $case = AccountabilityCase::create([
                'reference' => $this->reference('AC'),
                'category' => $category,
                'channel' => (string) $data['channel'],
                'status' => 'received',
                'summary' => Phase5Support::clean(
                    (string) $data['summary'],
                    (int) config('phase5.accountability.max_summary_chars', 4000),
                ),
                'preferred_contact_method' => isset($data['preferred_contact_method'])
                    ? Phase5Support::clean((string) $data['preferred_contact_method'], 32)
                    : null,
                'language_preference' => (string) ($data['language_preference'] ?? app()->getLocale()),
                'is_sensitive' => false,
            ]);

            // The submitting actor is deliberately absent from the case and its
            // events. Audit logging is the only place an actor is recorded.
            AccountabilityEvent::query()->create([
                'case_id' => $case->getKey(),
                'action' => 'created',
                'from_status' => null,
                'to_status' => 'received',
                'actor_id' => null,
                'note' => null,
                'is_sensitive' => false,
                'created_at' => now(),
            ]);

            $this->recordDomainTransition($case, null, 'received', null);

            $this->audit->record('accountability.created', 'accountability_case', (string) $case->getKey(), null, [
                'reference' => $case->reference,
                'category' => $case->category,
                'status' => $case->status,
            ]);

            event(new Phase5Event('accountability.created', [
                'reference' => $case->reference,
                'category' => $case->category,
                'status' => $case->status,
            ], 'accountability_case', (int) $case->getKey()));
        });

        return $case->refresh();
    }

    public function paginateCases(User $user): LengthAwarePaginator
    {
        $query = AccountabilityCase::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! $this->authorization->allows($user, 'accountability.view_sensitive')) {
            $query->where('is_sensitive', false);
        }

        return $query->paginate(self::PER_PAGE)->withQueryString()->through(
            fn (AccountabilityCase $case): array => $this->serializeCase($case, false),
        );
    }

    public function showCase(User $user, AccountabilityCase $case): array
    {
        $this->authorization->assertCanViewResource($user, 'accountability_case', (int) $case->getKey());
        $canViewSensitive = $this->authorization->allows($user, 'accountability.view_sensitive');

        return [
            'caseData' => $this->serializeCase($case, true),
            'events' => $this->serializeEvents($case, $canViewSensitive),
            'statuses' => (array) config('phase5.accountability.statuses', []),
            'transitions' => (array) config('phase5.accountability.transitions', []),
            'canViewSensitive' => $canViewSensitive,
            'notice' => __('phase5.privacy_notice'),
        ];
    }

    /** @param array<string, mixed> $data */
    public function transition(User $user, AccountabilityCase $case, array $data): AccountabilityCase
    {
        $this->authorization->assertCanViewResource($user, 'accountability_case', (int) $case->getKey());

        $from = (string) $case->status;
        $to = (string) $data['to_status'];
        $this->authorization->assertTransition($user, 'accountability', $from, $to);

        DB::transaction(function () use ($user, $case, $data, $from, $to): void {
            $case->forceFill([
                'status' => $to,
                'closed_at' => $to === 'closed' ? now() : $case->closed_at,
            ])->save();

            AccountabilityEvent::query()->create([
                'case_id' => $case->getKey(),
                'action' => 'status_changed',
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => $user->id,
                'note' => isset($data['note'])
                    ? Phase5Support::clean((string) $data['note'], 2000)
                    : null,
                'is_sensitive' => (bool) $case->is_sensitive,
                'created_at' => now(),
            ]);

            $this->recordDomainTransition($case, $from, $to, (int) $user->id);

            $this->audit->record('accountability.status_changed', 'accountability_case', (string) $case->getKey(), [
                'status' => $from,
            ], [
                'reference' => $case->reference,
                'status' => $to,
            ]);

            event(new Phase5Event('accountability.status_changed', [
                'reference' => $case->reference,
                'from' => $from,
                'to' => $to,
            ], 'accountability_case', (int) $case->getKey(), (int) $user->id));
        });

        return $case->refresh();
    }

    /** @param array<string, mixed> $data */
    public function assign(User $user, AccountabilityCase $case, array $data): AccountabilityCase
    {
        $this->authorization->assertCanViewResource($user, 'accountability_case', (int) $case->getKey());
        $assigneeId = (int) $data['assigned_to'];

        DB::transaction(function () use ($user, $case, $assigneeId): void {
            $previousAssignee = $case->assigned_to;
            $case->forceFill(['assigned_to' => $assigneeId])->save();

            AccountabilityEvent::query()->create([
                'case_id' => $case->getKey(),
                'action' => 'assigned',
                'from_status' => (string) $case->status,
                'to_status' => (string) $case->status,
                'actor_id' => $user->id,
                'note' => null,
                'is_sensitive' => (bool) $case->is_sensitive,
                'created_at' => now(),
            ]);

            $this->audit->record('accountability.assigned', 'accountability_case', (string) $case->getKey(), [
                'assigned_to' => $previousAssignee,
            ], [
                'reference' => $case->reference,
                'assigned_to' => $assigneeId,
            ]);
        });

        return $case->refresh();
    }

    /**
     * Public tracking is intentionally narrow. The gate is checked by the
     * controller; only configured fields are ever returned here.
     *
     * @return array<string, mixed>|null
     */
    public function track(string $reference): ?array
    {
        $case = AccountabilityCase::query()->where('reference', $reference)->first();

        if ($case === null) {
            return null;
        }

        $candidate = [
            'reference' => $case->reference,
            'status' => $case->status,
            'updated_at' => $case->updated_at?->toISOString(),
        ];

        /** @var list<string> $allowed */
        $allowed = (array) config('phase5.accountability.public_tracking_fields', []);

        return array_intersect_key($candidate, array_flip($allowed));
    }

    /** @return array<string, mixed> */
    private function serializeCase(AccountabilityCase $case, bool $includeSummary): array
    {
        $data = [
            'id' => $case->getKey(),
            'reference' => $case->reference,
            'category' => $case->category,
            'channel' => $case->channel,
            'status' => $case->status,
            'isSensitive' => (bool) $case->is_sensitive,
            'createdAt' => $case->created_at?->toISOString(),
            'updatedAt' => $case->updated_at?->toISOString(),
        ];

        if ($includeSummary) {
            $data['summary'] = $case->summary;
            $data['preferredContactMethod'] = $case->preferred_contact_method;
            $data['languagePreference'] = $case->language_preference;
            $data['assignedTo'] = $case->assigned_to;
            $data['closedAt'] = $case->closed_at?->toISOString();
        }

        return $data;
    }

    /** @return list<array<string, mixed>> */
    private function serializeEvents(AccountabilityCase $case, bool $canViewSensitive): array
    {
        $query = $case->events()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! $canViewSensitive) {
            $query->where('is_sensitive', false);
        }

        return $query->get()->map(static fn (AccountabilityEvent $event): array => [
            'id' => $event->getKey(),
            'action' => $event->action,
            'fromStatus' => $event->from_status,
            'toStatus' => $event->to_status,
            'note' => $event->note,
            'isSensitive' => (bool) $event->is_sensitive,
            'createdAt' => $event->created_at?->toISOString(),
        ])->all();
    }

    private function reference(string $prefix): string
    {
        // Cast before uppercasing: Laravel 13 returns a Ulid value object.
        return strtoupper($prefix).'-'.strtoupper((string) Str::ulid());
    }

    private function assertCategoryEnabled(string $category): void
    {
        /** @var array<string, array<string, mixed>> $disabled */
        $disabled = (array) config('phase5.accountability.disabled_categories', []);
        /** @var array<string, array<string, mixed>> $enabled */
        $enabled = (array) config('phase5.accountability.enabled_categories', []);

        if (array_key_exists($category, $disabled)) {
            throw new Phase5GovernanceException(
                'sensitive_category_blocked',
                (string) ($disabled[$category]['blocking_gate'] ?? 'OQ-29'),
            );
        }

        if (! array_key_exists($category, $enabled)) {
            throw new InvalidArgumentException('Unknown accountability category.');
        }
    }

    private function recordDomainTransition(AccountabilityCase $case, ?string $from, string $to, ?int $actorId): void
    {
        DomainTransition::query()->create([
            'resource' => 'accountability_cases',
            'owner_id' => $case->getKey(),
            'status_field' => 'status',
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actorId,
            'reason' => null,
            'evidence_media_id' => null,
            'created_at' => now(),
        ]);
    }
}
