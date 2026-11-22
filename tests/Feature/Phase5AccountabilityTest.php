<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Events\Phase5Event;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Phase5\AccountabilityCase;
use App\Models\Phase5\AccountabilityEvent;
use App\Models\Phase5\DocumentReview;
use App\Models\Phase5\Referral;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class Phase5AccountabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', '12345678901234567890123456789012');
        $this->withoutVite();
    }

    public function test_disabled_category_is_blocked_without_persisting_a_case(): void
    {
        $user = $this->userWith(['accountability.create']);

        Event::fake([Phase5Event::class]);
        $auditCount = DB::table('audit_logs')->count();

        $this->actingAs($user)->post($this->phase5Url('accountability'), [
            'category' => 'staff_conduct',
            'channel' => 'internal',
            'summary' => 'Blocked category.',
            'preferred_contact_method' => 'email',
        ])->assertConflict()
            ->assertSessionHas('flash.error', 'Blocked by governance gate sensitive_category_blocked (OQ-29)');

        $this->assertDatabaseEmpty('accountability_cases');
        $this->assertDatabaseEmpty('accountability_events');
        $this->assertDatabaseEmpty('domain_transitions');
        $this->assertSame($auditCount, DB::table('audit_logs')->count());
        Event::assertNotDispatched(Phase5Event::class);
    }

    public function test_accountability_submission_and_transition_persist_only_safe_data(): void
    {
        $user = $this->userWith([
            'accountability.create', 'accountability.view', 'accountability.update',
        ]);

        Event::fake([Phase5Event::class]);

        $this->actingAs($user)->post($this->phase5Url('accountability'), [
            'category' => 'general_feedback',
            'channel' => 'internal',
            'summary' => 'Service was responsive.',
            'preferred_contact_method' => 'email',
            'language_preference' => 'en',
            'submitter_id' => $user->id,
            'user_id' => $user->id,
            'actor_id' => $user->id,
            'created_by' => $user->id,
            'name' => 'Revealed Name',
            'email' => 'revealed@example.test',
            'phone' => '+970500000000',
            'national_id' => '123456789',
            'is_sensitive' => true,
        ])->assertRedirect()
            ->assertSessionHas('success', __('phase5.case_created'));

        $case = AccountabilityCase::query()->sole();

        $this->assertSame('general_feedback', $case->category);
        $this->assertSame('internal', $case->channel);
        $this->assertSame('Service was responsive.', $case->summary);
        $this->assertSame('email', $case->preferred_contact_method);
        $this->assertSame('en', $case->language_preference);
        $this->assertFalse($case->is_sensitive);
        $this->assertNull($case->assigned_to);

        $forbiddenColumns = array_intersect(
            ['submitter_id', 'user_id', 'actor_id', 'created_by', 'name', 'email', 'phone', 'national_id'],
            Schema::getColumnListing('accountability_cases'),
        );
        $this->assertSame([], array_values($forbiddenColumns));

        $this->assertDatabaseHas('accountability_events', [
            'case_id' => $case->id,
            'action' => 'created',
            'actor_id' => null,
            'is_sensitive' => 0,
        ]);
        $this->assertDatabaseHas('domain_transitions', [
            'resource' => 'accountability_cases',
            'owner_id' => $case->id,
            'actor_id' => null,
            'to_status' => 'received',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'accountability.created',
            'subject_type' => 'accountability_case',
            'subject_id' => (string) $case->id,
        ]);

        $this->actingAs($user)->post($this->phase5Url("accountability/{$case->id}/transition"), [
            'to_status' => 'acknowledged',
            'note' => 'Reviewed safely.',
        ])->assertRedirect()
            ->assertSessionHas('success', __('phase5.transitioned'));

        $this->assertSame('acknowledged', $case->fresh()->status);
        $this->assertSame(2, DB::table('accountability_events')->where('case_id', $case->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'accountability.status_changed',
            'subject_type' => 'accountability_case',
            'subject_id' => (string) $case->id,
        ]);

        Event::assertDispatched(Phase5Event::class, static fn (Phase5Event $event): bool => $event->name === 'accountability.created'
            && $event->payload['status'] === 'received'
            && ! array_key_exists('summary', $event->payload)
            && ! array_key_exists('submitter_id', $event->payload));
        Event::assertDispatched(Phase5Event::class, static fn (Phase5Event $event): bool => $event->name === 'accountability.status_changed'
            && $event->payload['to'] === 'acknowledged'
            && ! array_key_exists('note', $event->payload));
    }

    public function test_sensitive_case_is_hidden_from_accountability_listing_and_detail(): void
    {
        $viewer = $this->userWith(['accountability.view']);
        $sensitiveViewer = $this->userWith(['accountability.view', 'accountability.view_sensitive']);

        $case = $this->createCase('AC-SENSITIVE-001', [
            'summary' => 'SENSITIVE-CASE-MARKER',
            'is_sensitive' => true,
        ]);

        $this->actingAs($viewer)->get($this->phase5Url('accountability'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Accountability/Index')
                ->where('cases.total', 0)
                ->where('cases.data', [])
                ->where('enabledCategories', static fn ($value): bool => in_array('general_feedback', $value->all(), true)
                    && ! in_array('staff_conduct', $value->all(), true))
                ->where('disabledCategories', static fn ($value): bool => in_array('staff_conduct', $value->all(), true))
                ->where('statuses', static fn ($value): bool => in_array('received', $value->all(), true))
                ->where('gates', static fn ($value): bool => isset($value['complaint_public_tracking_enabled']))
                ->where('notice', static fn (mixed $value): bool => is_string($value) && $value !== ''));

        $this->actingAs($viewer)->get($this->phase5Url("accountability/{$case->id}"))->assertNotFound();

        $this->actingAs($sensitiveViewer)->get($this->phase5Url("accountability/{$case->id}"))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Accountability/Show')
                ->where('caseData.reference', $case->reference)
                ->where('caseData.summary', 'SENSITIVE-CASE-MARKER')
                ->where('events', [])
                ->where('statuses', static fn ($value): bool => in_array('received', $value->all(), true))
                ->where('transitions', static fn ($value): bool => isset($value['received'])
                    && in_array('acknowledged', (array) $value['received'], true))
                ->where('canViewSensitive', true)
                ->where('notice', static fn (mixed $value): bool => is_string($value) && $value !== ''));
    }

    public function test_sensitive_events_are_hidden_without_view_sensitive_permission(): void
    {
        $viewer = $this->userWith(['accountability.view']);

        $case = $this->createCase('AC-EVENTS-001');

        AccountabilityEvent::query()->create([
            'case_id' => $case->id,
            'action' => 'status_changed',
            'from_status' => 'received',
            'to_status' => 'under_triage',
            'actor_id' => $viewer->id,
            'note' => 'SENSITIVE-EVENT-MARKER',
            'is_sensitive' => true,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get($this->phase5Url("accountability/{$case->id}"))->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Phase5/Accountability/Show')
            ->where('caseData.reference', $case->reference)
            ->where('events', [])
            ->where('canViewSensitive', false));

        $this->assertStringNotContainsString('SENSITIVE-EVENT-MARKER', (string) $response->getContent());
    }

    public function test_public_tracking_is_closed_by_default_and_returns_only_safe_fields_when_enabled(): void
    {
        $this->assertFalse(config('phase5.gates.complaint_public_tracking_enabled'));

        $case = $this->createCase('AC-TRACK-001', [
            'status' => 'acknowledged',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-02 03:04:05',
        ]);

        $url = $this->phase5Url("accountability/track/{$case->reference}");

        $this->get($url)->assertNotFound();

        config()->set('phase5.gates.complaint_public_tracking_enabled', true);

        $this->get($url)->assertOk()->assertExactJson([
            'reference' => 'AC-TRACK-001',
            'status' => 'acknowledged',
            'updated_at' => '2026-01-02T03:04:05.000000Z',
        ]);
    }

    public function test_referral_source_authorization_blocks_unauthorized_and_missing_sources(): void
    {
        $source = $this->createCase('AC-REF-SOURCE-001', [
            'summary' => 'SOURCE-NARRATIVE-MARKER',
        ]);
        $destination = User::factory()->create();

        $unauthorized = $this->userWith(['referrals.create']);

        $this->actingAs($unauthorized)->post($this->phase5Url('referrals'), [
            'source_type' => 'accountability_case',
            'source_id' => $source->id,
            'destination_type' => 'internal_team',
            'destination_id' => $destination->id,
            'reason' => 'Reason marker.',
        ])->assertNotFound();

        $this->assertDatabaseEmpty('referrals');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'referral.created']);

        $authorized = $this->userWith(['referrals.create', 'accountability.view']);

        $this->actingAs($authorized)->post($this->phase5Url('referrals'), [
            'source_type' => 'accountability_case',
            'source_id' => 999999,
            'destination_type' => 'internal_team',
            'destination_id' => $destination->id,
            'reason' => 'Reason marker.',
        ])->assertNotFound();

        $this->assertDatabaseEmpty('referrals');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'referral.created']);
    }

    public function test_referral_index_and_show_expose_only_non_identifying_fields(): void
    {
        $user = $this->userWith(['referrals.view', 'referrals.create', 'accountability.view']);
        $source = $this->createCase('AC-REF-SOURCE-002', [
            'summary' => 'SOURCE-NARRATIVE-DO-NOT-LEAK',
        ]);
        $destination = User::factory()->create();
        $reasonMarker = 'REFERRAL-REASON-DO-NOT-LEAK';

        $this->actingAs($user)->post($this->phase5Url('referrals'), [
            'source_type' => 'accountability_case',
            'source_id' => $source->id,
            'destination_type' => 'internal_team',
            'destination_id' => $destination->id,
            'reason' => $reasonMarker,
        ])->assertRedirect()
            ->assertSessionHas('success', __('phase5.referral_created'));

        $referral = Referral::query()->sole();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'referral.created',
            'subject_type' => 'referral',
            'subject_id' => (string) $referral->id,
        ]);

        $this->actingAs($user)->get($this->phase5Url('referrals'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Referrals/Index')
                ->where('referrals.data.0.reference', $referral->reference)
                ->where('referrals.data.0', static fn ($item): bool => array_keys($item->all()) === [
                    'reference', 'status', 'sourceType', 'destinationType', 'createdAt', 'updatedAt', 'dueAt',
                ])
                ->where('statuses', static fn ($value): bool => in_array('draft', $value->all(), true))
                ->where('transitions', static fn ($value): bool => isset($value['draft'])
                    && in_array('submitted', (array) $value['draft'], true))
                ->where('sourceTypes', static fn ($value): bool => in_array('accountability_case', $value->all(), true))
                ->where('destinationTypes', static fn ($value): bool => in_array('internal_team', $value->all(), true))
                ->where('notice', static fn (mixed $value): bool => is_string($value) && $value !== ''));

        $response = $this->actingAs($user)->get($this->phase5Url("referrals/{$referral->id}"))->assertOk();

        $this->assertSame([
            'reference', 'status', 'sourceType', 'destinationType', 'createdAt', 'updatedAt', 'dueAt',
        ], array_keys($response->json('referral')));
        $this->assertStringNotContainsString($reasonMarker, (string) $response->getContent());
        $this->assertStringNotContainsString('SOURCE-NARRATIVE-DO-NOT-LEAK', (string) $response->getContent());

        $otherUser = $this->userWith(['referrals.view']);

        $this->actingAs($otherUser)->get($this->phase5Url("referrals/{$referral->id}"))->assertNotFound();
    }

    public function test_referral_transition_appends_event_and_audit_without_source_data(): void
    {
        $user = $this->userWith([
            'referrals.view', 'referrals.create', 'referrals.transition', 'accountability.view',
        ]);
        $source = $this->createCase('AC-REF-SOURCE-003', [
            'summary' => 'SOURCE-NARRATIVE-TRANSITION-MARKER',
        ]);
        $destination = User::factory()->create();

        Event::fake([Phase5Event::class]);

        $this->actingAs($user)->post($this->phase5Url('referrals'), [
            'source_type' => 'accountability_case',
            'source_id' => $source->id,
            'destination_type' => 'internal_team',
            'destination_id' => $destination->id,
            'reason' => 'Route to the team.',
        ])->assertRedirect();

        $referralId = (int) Referral::query()->value('id');

        $this->actingAs($user)->post($this->phase5Url("referrals/{$referralId}/transition"), [
            'to_status' => 'submitted',
        ])->assertRedirect()
            ->assertSessionHas('success', __('phase5.transitioned'));

        $referral = Referral::query()->findOrFail((int) $referralId);

        $this->assertSame('submitted', $referral->status);
        $this->assertSame(2, DB::table('referral_events')->where('referral_id', $referral->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'referral.status_changed',
            'subject_type' => 'referral',
            'subject_id' => (string) $referral->id,
        ]);

        Event::assertDispatched(Phase5Event::class, static function (Phase5Event $event): bool {
            $payload = json_encode($event->payload, JSON_THROW_ON_ERROR);

            return $event->name === 'referral.status_changed'
                && $event->payload['to'] === 'submitted'
                && ! str_contains($payload, 'SOURCE-NARRATIVE-TRANSITION-MARKER')
                && ! array_key_exists('source_id', $event->payload);
        });
    }

    public function test_document_review_rejects_public_media_without_writing_audit_or_event(): void
    {
        $user = $this->userWith(['documents.submit_review']);
        $publicMedia = $this->createMedia($user, 'public-document', false);

        Event::fake([Phase5Event::class]);

        $this->actingAs($user)->post($this->phase5Url('document-reviews'), [
            'media_id' => $publicMedia->id,
            'title' => 'Public document',
        ])->assertSessionHasErrors('media_id');

        $this->assertDatabaseEmpty('document_reviews');
        $this->assertDatabaseEmpty('document_review_events');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'document.submitted']);
        Event::assertNotDispatched(Phase5Event::class);
    }

    public function test_document_review_resubmission_increments_version_and_appends_revision_history(): void
    {
        Storage::fake('private');

        $user = $this->userWith([
            'documents.review', 'documents.submit_review', 'documents.download',
        ]);
        $firstMedia = $this->createMedia($user, 'review-document-v1');
        $secondMedia = $this->createMedia($user, 'review-document-v2');
        Storage::disk('private')->put($firstMedia->path, 'version one');
        Storage::disk('private')->put($secondMedia->path, 'version two');

        Event::fake([Phase5Event::class]);

        $this->actingAs($user)->post($this->phase5Url('document-reviews'), [
            'media_id' => $firstMedia->id,
            'title' => 'Annual report',
            'description' => 'Technical review only.',
        ])->assertRedirect()
            ->assertSessionHas('success', __('phase5.document_submitted'));

        $review = DocumentReview::query()->sole();
        $firstLinkId = (int) $review->document_link_id;

        $this->assertSame('submitted', $review->status);
        $this->assertSame(1, $review->version);
        $this->assertNotNull($review->document_link_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.submitted',
            'subject_type' => 'document_review',
            'subject_id' => (string) $review->id,
        ]);

        $this->actingAs($user)->post($this->phase5Url("document-reviews/{$review->id}/transition"), [
            'to_status' => 'changes_requested',
            'note' => 'Please revise.',
        ])->assertRedirect()
            ->assertSessionHas('success', __('phase5.transitioned'));

        $this->actingAs($user)->post($this->phase5Url("document-reviews/{$review->id}/transition"), [
            'to_status' => 'resubmitted',
            'note' => 'Revised document attached.',
            'media_id' => $secondMedia->id,
        ])->assertRedirect()
            ->assertSessionHas('success', __('phase5.transitioned'));

        $review->refresh();

        $this->assertSame('resubmitted', $review->status);
        $this->assertSame(2, $review->version);
        $this->assertSame($secondMedia->id, $review->media_id);
        $this->assertNotSame($firstLinkId, (int) $review->document_link_id);
        $this->assertSame(3, DB::table('document_review_events')->where('review_id', $review->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.resubmitted',
            'subject_type' => 'document_review',
            'subject_id' => (string) $review->id,
        ]);

        $history = $this->actingAs($user)->get($this->phase5Url("document-reviews/{$review->id}/history"))->assertOk();

        $this->assertCount(3, $history->json('history'));
        $this->assertSame('resubmitted', $history->json('history.0.action'));
        $this->assertSame(2, $history->json('history.0.revision'));
        $this->assertSame('status_changed', $history->json('history.1.action'));
        $this->assertSame('submitted', $history->json('history.2.action'));
        $this->assertSame(1, $history->json('history.2.revision'));

        Event::assertDispatched(Phase5Event::class, static fn (Phase5Event $event): bool => $event->name === 'document.submitted'
            && $event->payload['version'] === 2);
        Event::assertDispatched(Phase5Event::class, static fn (Phase5Event $event): bool => $event->name === 'document.status_changed'
            && $event->payload['to'] === 'changes_requested');
    }

    public function test_document_review_rejects_institutional_approval_outcome(): void
    {
        $user = $this->userWith(['documents.review', 'documents.submit_review']);
        $media = $this->createMedia($user, 'approval-document');

        $this->actingAs($user)->post($this->phase5Url('document-reviews'), [
            'media_id' => $media->id,
            'title' => 'Technical review',
        ])->assertRedirect();

        $review = DocumentReview::query()->sole();

        $this->actingAs($user)->post($this->phase5Url("document-reviews/{$review->id}/transition"), [
            'to_status' => 'approved',
        ])->assertSessionHasErrors('to_status');

        $this->assertSame('submitted', $review->fresh()->status);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'document.status_changed',
            'subject_id' => (string) $review->id,
        ]);
    }

    public function test_document_download_url_requires_existing_link_and_permission(): void
    {
        Storage::fake('private');

        $user = $this->userWith([
            'documents.review', 'documents.submit_review', 'documents.download',
        ]);
        $media = $this->createMedia($user, 'download-document');
        Storage::disk('private')->put($media->path, 'downloadable');

        $this->actingAs($user)->post($this->phase5Url('document-reviews'), [
            'media_id' => $media->id,
            'title' => 'Downloadable review',
        ])->assertRedirect();

        $review = DocumentReview::query()->sole();

        $show = $this->actingAs($user)->get($this->phase5Url("document-reviews/{$review->id}"))->assertOk();
        $downloadUrl = $show->json('review.downloadUrl');

        $this->assertIsString($downloadUrl);
        $this->assertStringContainsString('/ar/operational-documents/', $downloadUrl);
        $this->actingAs($user)->get($downloadUrl)->assertOk();

        $reviewerWithoutDownload = $this->userWith(['documents.review']);

        $this->actingAs($reviewerWithoutDownload)->get($this->phase5Url('document-reviews'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/DocumentReviews/Index')
                ->where('reviews.data.0.downloadUrl', null)
                ->where('permissions', [
                    'review' => true,
                    'submit' => false,
                    'download' => false,
                ])
                ->where('statuses', static fn ($value): bool => in_array('submitted', $value->all(), true))
                ->where('transitions', static fn ($value): bool => isset($value['submitted'])
                    && in_array('under_review', (array) $value['submitted'], true))
                ->where('notice', static fn (mixed $value): bool => is_string($value) && $value !== ''));

        DB::table('document_links')->where('id', $review->document_link_id)->delete();

        $this->actingAs($user)->get($this->phase5Url("document-reviews/{$review->id}"))
            ->assertOk()
            ->assertJsonPath('review.downloadUrl', null);

        $this->actingAs($user)->get($downloadUrl)->assertNotFound();
    }

    /** @param array<string, mixed> $attributes */
    private function createCase(string $reference, array $attributes = []): AccountabilityCase
    {
        $timestamps = [];

        foreach (['created_at', 'updated_at'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $timestamps[$column] = $attributes[$column];
                unset($attributes[$column]);
            }
        }

        $case = AccountabilityCase::query()->create(array_merge([
            'reference' => $reference,
            'category' => 'general_feedback',
            'channel' => 'internal',
            'status' => 'received',
            'summary' => 'Safe summary.',
            'language_preference' => 'ar',
            'is_sensitive' => false,
        ], $attributes));

        if ($timestamps !== []) {
            DB::table('accountability_cases')
                ->where('id', $case->getKey())
                ->update($timestamps);
            $case->refresh();
        }

        return $case;
    }

    private function createMedia(User $user, string $uuid, bool $restricted = true): Media
    {
        return Media::query()->create([
            'uuid' => $uuid,
            'disk' => 'private',
            'path' => 'media/private/'.$uuid.'.pdf',
            'type' => MediaType::Document,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 16,
            'checksum' => hash('sha256', $uuid),
            'original_name' => $uuid.'.pdf',
            'is_restricted' => $restricted,
            'uploaded_by' => $user->id,
        ]);
    }

    private function phase5Url(string $path = ''): string
    {
        return '/ar/operations/phase5'.($path === '' ? '' : '/'.$path);
    }

    /** @param list<string> $keys */
    private function userWith(array $keys): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'key' => 'phase5-'.($user->id),
            'name' => 'Phase 5 Operator',
        ]);

        $permissionCatalog = config('permissions', []);

        foreach ($keys as $key) {
            $definition = is_array($permissionCatalog[$key] ?? null) ? $permissionCatalog[$key] : [];
            $permission = Permission::query()->firstOrCreate([
                'key' => $key,
                'name' => $definition['label'] ?? $key,
                'module' => $definition['module'] ?? 'phase5',
                'is_protected' => false,
                'is_retired' => false,
            ]);
            $role->permissions()->attach($permission);
        }

        app(AccessManagementService::class)->syncRoles($user, [$role->id]);

        return $user;
    }
}
