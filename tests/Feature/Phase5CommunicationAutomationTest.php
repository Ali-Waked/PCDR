<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\Phase5Event;
use App\Exceptions\Phase5GovernanceException;
use App\Models\Permission;
use App\Models\Phase5\AutomationExecution;
use App\Models\Phase5\IncomingWebhookEvent;
use App\Models\Phase5\IntegrationRegistry;
use App\Models\Phase5\WebhookDelivery;
use App\Models\Phase5\WebhookEndpoint;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Phase5\InAppNotification;
use App\Services\AccessManagementService;
use App\Services\AuditService;
use App\Services\Phase5\AutomationEngineService;
use App\Services\Phase5\CalendarAggregationService;
use App\Services\Phase5\NotificationCenterService;
use App\Services\Phase5\Phase5ActivityService;
use App\Services\Phase5\Phase5Authorization;
use App\Services\Phase5\WebhookService;
use App\Support\Phase5Support;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use Tests\TestCase;

final class Phase5CommunicationAutomationTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'phase5-webhook-secret-0123456789abcdef';

    private int $sequence = 0;

    /** The test environment has no APP_KEY; encrypted webhook secrets need one. */
    public function createApplication()
    {
        $key = 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
        $_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = $key;
        putenv('APP_KEY='.$key);

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config()->set('inertia.testing.ensure_pages_exist', false);
        config()->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        config()->set('queue.default', 'sync');
        config()->set('phase5.gates.external_channels_enabled', false);
        config()->set('phase5.gates.webhooks_enabled', false);

        Event::fake([Phase5Event::class]);
    }

    public function test_phase5_communication_automation_pages_expose_the_frozen_props(): void
    {
        $user = $this->userWith([
            'notifications.view', 'notifications.preferences', 'automations.view',
            'calendar.view', 'integrations.view', 'dashboards.view',
        ]);
        $this->actingAs($user);

        $this->get('/ar/operations/phase5/notifications')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Notifications/Index')
                ->has('notifications.data')
                ->has('unreadCount')
                ->has('categories')
                ->has('priorities'));

        $this->get('/ar/operations/phase5/notifications/preferences')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Notifications/Preferences')
                ->has('preferences')
                ->has('categories')
                ->has('channels')
                ->has('digestOptions')
                ->has('mandatoryCategories'));

        $this->get('/ar/operations/phase5/automations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Automations/Index')
                ->has('definitions.data')
                ->has('executions.data')
                ->has('triggers')
                ->has('operators')
                ->has('actions')
                ->has('fields')
                ->has('gates'));

        $this->get('/ar/operations/phase5/calendar')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Calendar/Index')
                ->has('events')
                ->has('views')
                ->has('sources')
                ->has('timezone'));

        $this->get('/ar/operations/phase5/integrations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Integrations/Index')
                ->has('integrations.data')
                ->has('categories')
                ->has('gates'));

        $this->get('/ar/operations/phase5/webhooks/endpoints')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Webhooks/Index')
                ->has('endpoints.data')
                ->has('deliveries.data')
                ->has('events')
                ->where('gates.webhooks_enabled', false));

        $this->get('/ar/operations/phase5/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Dashboard')
                ->has('summary')
                ->has('sections')
                ->has('activity')
                ->has('gates')
                ->has('notice'));

        $this->get('/ar/operations/phase5/activity')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Activity/Index')
                ->has('activity')
                ->has('filters.actions'));
    }

    public function test_in_app_notifications_store_only_safe_keys_and_dedupe(): void
    {
        $user = $this->userWith(['notifications.view', 'tasks.view']);
        $this->sequence++;
        $taskId = DB::table('tasks')->insertGetId([
            'code' => 'TASK-NOTIF-'.$this->sequence,
            'title' => 'Notification subject',
            'status' => 'to_do',
            'priority' => 'normal',
            'owner_id' => $user->id,
            'creator_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $notifications = app(NotificationCenterService::class);

        $message = [
            'category' => 'task',
            'priority' => 'high',
            'title_key' => 'phase5.notification.task_created',
            'preview_key' => 'phase5.notification.task_created_preview',
            'resource_type' => 'task',
            'resource_id' => $taskId,
            'narrative' => 'Sensitive complaint narrative must never be stored.',
            'token' => 'secret-token',
            'user_ids' => [$user->id, 99999],
        ];

        $this->assertSame(1, $notifications->notifyUsers([$user->id], $message, 'safe-dedupe-key'));
        $this->assertSame(0, $notifications->notifyUsers([$user->id], $message, 'safe-dedupe-key'));
        $this->assertSame(1, DB::table('notifications')->count());

        $row = DB::table('notifications')->where('notifiable_id', $user->id)->firstOrFail();
        $data = json_decode((string) $row->data, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([
            'category', 'priority', 'title_key', 'preview_key',
            'resource_type', 'resource_id', 'url', 'sensitive',
        ], array_keys($data));
        $this->assertFalse($data['sensitive']);
        $this->assertSame('task', $data['category']);
        $this->assertStringStartsWith('phase5:safe-dedupe-key', (string) $row->type);
        $this->assertStringNotContainsString('Sensitive complaint', (string) $row->data);
        $this->assertStringNotContainsString('secret-token', (string) $row->data);

        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $user->id,
            'channel' => 'database',
            'provider' => 'local',
            'status' => 'sent',
            'delivered_at' => null,
        ]);
        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $user->id,
            'channel' => 'mail',
            'status' => 'disabled',
        ]);

        $this->expectException(InvalidArgumentException::class);
        new InAppNotification('task', 'normal', 'bad key', 'also bad');
    }

    public function test_notification_preferences_keep_system_mandatory_and_read_flow_is_scoped(): void
    {
        $user = $this->userWith(['notifications.view', 'notifications.preferences']);
        $this->actingAs($user);

        $this->put('/ar/operations/phase5/notifications/preferences', ['preferences' => [
            ['category' => 'task', 'channel' => 'database', 'enabled' => false, 'digest' => 'daily'],
            ['category' => 'system', 'channel' => 'database', 'enabled' => false, 'digest' => 'instant'],
        ]])->assertRedirect();

        $notifications = app(NotificationCenterService::class);
        $preferences = $notifications->preferences($user);
        $this->assertFalse($preferences['task']['database']['enabled']);
        $this->assertTrue($preferences['system']['database']['enabled']);

        $this->assertSame(0, $notifications->notifyUsers([$user->id], [
            'category' => 'task', 'title_key' => 'phase5.task', 'preview_key' => 'phase5.task_preview',
        ], 'task-disabled'));
        $this->assertSame(1, $notifications->notifyUsers([$user->id], [
            'category' => 'system', 'title_key' => 'phase5.system', 'preview_key' => 'phase5.system_preview',
        ], 'system-mandatory'));

        $notification = DB::table('notifications')->where('notifiable_id', $user->id)->firstOrFail();
        $this->assertSame(1, $notifications->unreadCount($user));

        $response = $this->post('/ar/operations/phase5/notifications/'.$notification->id.'/read')
            ->assertRedirect();
        $this->assertNotNull(session('success'), json_encode(session()->all(), JSON_THROW_ON_ERROR));
        $this->assertSame(0, $notifications->unreadCount($user));
        $this->assertNotNull(DB::table('notifications')->where('id', $notification->id)->value('read_at'));

        $this->get('/ar/operations/phase5/notifications/deliveries')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'per_page', 'total']);
    }

    public function test_automation_definitions_are_disabled_allow_listed_and_idempotent(): void
    {
        $user = $this->userWith(['automations.view', 'automations.create']);
        $automations = app(AutomationEngineService::class);

        $definition = $automations->create($user, [
            'key' => 'safe-rule',
            'category' => 'generic',
            'trigger_event' => 'task.created',
            'conditions' => [
                ['field' => 'task.priority', 'operator' => 'equals', 'value' => 'high'],
            ],
            'actions' => [
                ['action' => 'log_audit'],
            ],
        ]);

        $this->assertFalse($definition->enabled);
        $this->assertSame(1, $definition->version);
        $automations->toggle($user, $definition, true);

        $event = new Phase5Event(
            'task.created',
            ['priority' => 'high', 'user_ids' => [$user->id]],
            'task',
            42,
            $user->id,
        );

        $this->assertSame(1, $automations->matchAndCreateExecutions($event));
        $this->assertSame(0, $automations->matchAndCreateExecutions($event));
        $this->assertSame(1, AutomationExecution::query()->count());
        $this->assertSame([
            ['field' => 'task.priority', 'operator' => 'equals', 'value' => 'high'],
        ], $definition->fresh()->conditions);
        $this->assertSame(0, $automations->matchAndCreateExecutions(new Phase5Event(
            'task.created',
            ['priority' => 'low'],
            'task',
            43,
        )));

        $execution = AutomationExecution::query()->firstOrFail();
        $automations->execute($execution->refresh());

        $this->assertSame(AutomationExecution::STATUS_SUCCEEDED, $execution->refresh()->status);
        $this->assertSame(1, $execution->attempts);
        $this->assertNull($execution->last_error);
    }

    public function test_automation_rejects_unsupported_configuration_and_dead_letters_failures(): void
    {
        $user = $this->userWith(['automations.create']);
        $automations = app(AutomationEngineService::class);

        try {
            $automations->normalize(['trigger_event' => 'task.created', 'code' => 'phpinfo();']);
            $this->fail('Unsupported automation keys must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Unsupported automation configuration keys', $exception->getMessage());
        }

        try {
            $automations->normalize([
                'trigger_event' => 'task.created',
                'actions' => [['action' => 'run_php', 'code' => 'system("id")']],
            ]);
            $this->fail('Unsupported automation actions must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Unsupported automation action', $exception->getMessage());
        }

        $execution = AutomationExecution::query()->create([
            'definition_id' => null,
            'definition_version' => 1,
            'trigger_event' => 'task.created',
            'subject_type' => 'task',
            'subject_id' => 42,
            'idempotency_key' => 'orphan-execution',
            'status' => AutomationExecution::STATUS_QUEUED,
            'attempts' => 0,
            'context' => Phase5Support::encode(['history' => []]),
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $automations->execute($execution->refresh());
        }

        $execution->refresh();
        $this->assertSame(3, $execution->attempts);
        $this->assertSame(3, (int) config('phase5.automation.dead_letter_after_attempts'));
        $this->assertSame(AutomationExecution::STATUS_DEAD_LETTER, $execution->status);
        $this->assertSame(3, $execution->attempts);
        $this->assertStringStartsWith('ERR_', (string) $execution->last_error);
        $this->assertStringNotContainsString('Missing automation definition', (string) $execution->last_error);

        try {
            $automations->retry($user, $execution);
            $this->fail('Dead-lettered executions must not be retried.');
        } catch (Phase5GovernanceException $exception) {
            $this->assertSame('automation_retry', $exception->gate);
        }
    }

    public function test_webhook_destinations_reject_unsafe_literals(): void
    {
        $cases = [
            'http://hooks.example.com/phase5' => 'invalid_url_scheme',
            'https://127.0.0.1/hook' => 'blocked_host',
            'https://169.254.169.254/latest/meta-data' => 'blocked_host',
            'https://metadata.google.internal/hook' => 'blocked_host',
            'https://user:pass@hooks.example.com/hook' => 'invalid_url',
            'https://hooks.example.com:8443/hook' => 'invalid_url',
        ];

        foreach ($cases as $url => $expectedCode) {
            try {
                WebhookService::validateUrl($url);
                $this->fail("Unsafe URL [{$url}] was accepted.");
            } catch (InvalidArgumentException $exception) {
                $this->assertSame($expectedCode, $exception->getMessage());
            }
        }

        $this->assertSame(
            'https://hooks.example.com/phase5',
            WebhookService::validateUrl('https://hooks.example.com/phase5'),
        );
    }

    public function test_webhook_gate_blocks_endpoint_registration_and_inbound(): void
    {
        $user = $this->userWith(['integrations.view', 'integrations.create']);
        $this->actingAs($user);

        $integration = app(WebhookService::class)->createIntegration($user, [
            'key' => 'gated-integration',
            'name' => 'Gated integration',
            'provider' => 'partner',
            'category' => 'partner_system',
        ]);

        $this->post('/ar/operations/phase5/webhooks/endpoints', [
            'integration_id' => $integration->id,
            'name' => 'Gated endpoint',
            'url' => 'https://hooks.example.com/phase5',
            'events' => ['task.created'],
            'secret' => self::SECRET,
            'active' => true,
        ])->assertRedirect()->assertSessionHas('error', __('phase5.governance_disabled'));

        $this->assertSame(0, WebhookEndpoint::query()->count());

        $response = $this->signedInboundCall($integration, self::SECRET, 'nonce-gated');
        $response->assertStatus(403)->assertExactJson(['error' => 'webhook_disabled']);
        $this->assertSame(0, IncomingWebhookEvent::query()->count());
    }

    public function test_signed_inbound_webhooks_are_replay_protected_and_store_no_payload(): void
    {
        config()->set('phase5.gates.webhooks_enabled', true);

        $user = $this->userWith(['integrations.view', 'integrations.create']);
        $webhooks = app(WebhookService::class);
        $integration = $webhooks->createIntegration($user, [
            'key' => 'inbound-integration',
            'name' => 'Inbound integration',
            'provider' => 'partner',
            'category' => 'partner_system',
        ]);
        $endpoint = $webhooks->createEndpoint($user, $integration, [
            'name' => 'Inbound endpoint',
            'url' => 'https://hooks.example.com/phase5',
            'events' => ['task.created'],
            'secret' => self::SECRET,
            'active' => true,
        ]);

        $this->assertStringNotContainsString(self::SECRET, json_encode($endpoint->toArray(), JSON_THROW_ON_ERROR));

        $response = $this->signedInboundCall($integration, self::SECRET, 'nonce-one');
        $response->assertStatus(202)->assertJson(['status' => 'accepted', 'event' => 'task.created']);

        $stored = IncomingWebhookEvent::query()->firstOrFail();
        $this->assertSame('nonce-one', $stored->nonce);
        $this->assertSame('task.created', $stored->event);
        $this->assertSame(
            ['id', 'integration_id', 'event', 'nonce', 'payload_hash', 'processed', 'received_at', 'created_at'],
            array_keys($stored->getAttributes()),
        );

        $this->signedInboundCall($integration, self::SECRET, 'nonce-one')
            ->assertStatus(409)
            ->assertExactJson(['error' => 'replayed_nonce']);
        $this->assertSame(1, IncomingWebhookEvent::query()->count());

        $this->signedInboundCall($integration, 'wrong-secret-value-0123456789abcdef', 'nonce-two')
            ->assertStatus(400)
            ->assertExactJson(['error' => 'invalid_signature']);
        $this->assertSame(1, IncomingWebhookEvent::query()->count());
    }

    public function test_outbound_webhooks_are_signed_deduplicated_and_bounded(): void
    {
        config()->set('phase5.gates.webhooks_enabled', true);
        Http::preventStrayRequests();
        Http::fake(['https://hooks.example.com/*' => Http::response(['ok' => true], 200)]);

        $user = $this->userWith([
            'integrations.view', 'integrations.create', 'integrations.retry',
        ]);
        $this->actingAs($user);

        $webhooks = app(WebhookService::class);
        $integration = $webhooks->createIntegration($user, [
            'key' => 'outbound-integration',
            'name' => 'Outbound integration',
            'provider' => 'partner',
            'category' => 'partner_system',
        ]);
        $webhooks->toggleIntegration($user, $integration, true);
        $endpoint = $webhooks->createEndpoint($user, $integration, [
            'name' => 'Outbound endpoint',
            'url' => 'https://hooks.example.com/phase5',
            'events' => ['task.created', 'workflow.created'],
            'secret' => self::SECRET,
            'active' => true,
        ]);

        $this->assertTrue($endpoint->fresh()->active);
        $this->assertTrue($integration->fresh()->enabled);
        $this->assertTrue(app(Phase5Authorization::class)->gateEnabled('webhooks_enabled'));
        $this->assertSame(1, WebhookEndpoint::query()
            ->where('active', true)
            ->whereHas('integration', fn ($query) => $query->where('enabled', true))
            ->count());
        $this->assertContains('task.created', (array) config('phase5.integrations.webhook_events'));
        $this->assertSame(['task.created', 'workflow.created'], $endpoint->fresh()->events);
        $this->assertSame(0, WebhookDelivery::query()->count());

        $event = new Phase5Event(
            'task.created',
            ['status' => 'to_do', 'secret' => 'never-exported', 'user_ids' => [$user->id]],
            'task',
            42,
            $user->id,
        );

        $this->assertSame(1, $webhooks->fanout($event));
        $delivery = WebhookDelivery::query()->firstOrFail();
        $this->assertSame(WebhookDelivery::STATUS_DELIVERED, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertSame(200, $delivery->response_code);
        Http::assertSentCount(1);

        Http::assertSent(function (Request $request) use ($endpoint): bool {
            $timestamp = (string) ($request->header('X-Phase5-Timestamp')[0] ?? '');
            $nonce = (string) ($request->header('X-Phase5-Nonce')[0] ?? '');
            $signature = (string) ($request->header('X-Phase5-Signature')[0] ?? '');
            $body = (string) $request->body();

            return hash_equals(
                app(WebhookService::class)->signature($timestamp, $nonce, $body, (string) $endpoint->secret_encrypted),
                $signature,
            ) && str_contains($body, '"event":"task.created"')
                && ! str_contains($body, 'never-exported')
                && ! str_contains($body, 'user_ids');
        });

        $this->assertSame(0, $webhooks->fanout($event));
        Http::assertSentCount(1);

        $failed = WebhookDelivery::query()->create([
            'endpoint_id' => $endpoint->id,
            'event' => 'workflow.created',
            'payload_version' => 1,
            'payload_hash' => hash('sha256', 'retry-payload'),
            'status' => WebhookDelivery::STATUS_FAILED,
            'attempts' => 1,
            'response_code' => 500,
            'error_code' => 'HTTP_500',
            'next_attempt_at' => now()->addMinute(),
            'created_at' => now(),
        ]);

        $this->post('/ar/operations/phase5/webhooks/deliveries/'.$failed->id.'/retry')->assertRedirect();
        $failed->refresh();
        $this->assertSame(WebhookDelivery::STATUS_DELIVERED, $failed->status);
        $this->assertSame(2, $failed->attempts);
        $this->assertNull($failed->error_code);
        Http::assertSentCount(2);
    }

    public function test_dashboard_and_activity_expose_permission_filtered_aggregates_only(): void
    {
        $user = $this->userWith([
            'dashboards.view', 'tasks.view', 'workflows.view', 'accountability.view',
            'referrals.view', 'documents.review', 'automations.view',
            'integrations.view', 'notifications.view',
        ]);
        $this->actingAs($user);

        app(AuditService::class)->record(
            'phase5.task.created',
            'task',
            '55',
            null,
            ['narrative' => 'Private complaint narrative', 'secret' => 'do-not-show'],
        );

        $this->sequence++;
        DB::table('accountability_cases')->insert([
            'reference' => 'ACC-P5-'.$this->sequence,
            'category' => 'generic',
            'summary' => 'Sensitive narrative',
            'is_sensitive' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->sequence++;
        DB::table('accountability_cases')->insert([
            'reference' => 'ACC-P5-'.$this->sequence,
            'category' => 'generic',
            'summary' => 'Routine narrative',
            'is_sensitive' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/ar/operations/phase5/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.accountability', 1)
                ->where('gates.webhooks_enabled', false)
                ->has('notice')
                ->has('activity'));

        $activity = app(Phase5ActivityService::class)->activity($user);
        $this->assertNotEmpty($activity);
        $this->assertSame(
            ['id', 'source', 'action', 'subject_type', 'subject_id', 'status', 'occurred_at'],
            array_keys($activity[0]),
        );

        $serialized = json_encode($activity, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('Private complaint narrative', $serialized);
        $this->assertStringNotContainsString('do-not-show', $serialized);
    }

    public function test_calendar_aggregation_drops_events_the_user_cannot_view(): void
    {
        $viewer = $this->userWith(['calendar.view', 'tasks.view', 'accountability.view']);
        $outsider = $this->userWith(['calendar.view', 'tasks.view']);

        $this->sequence++;
        $caseId = DB::table('accountability_cases')->insertGetId([
            'reference' => 'ACC-CAL-'.$this->sequence,
            'category' => 'generic',
            'summary' => 'Restricted',
            'is_sensitive' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sequence++;
        DB::table('tasks')->insert([
            'code' => 'TASK-CAL-'.$this->sequence,
            'title' => 'Restricted task',
            'status' => 'to_do',
            'priority' => 'normal',
            'due_at' => now()->toDateString().' 10:00:00',
            'owner_id' => null,
            'creator_id' => null,
            'subject_type' => 'accountability_case',
            'subject_id' => $caseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $filters = ['from' => now()->toDateString(), 'to' => now()->toDateString()];
        $calendar = app(CalendarAggregationService::class);

        $this->assertCount(0, $calendar->calendar([...$filters, 'user' => $viewer])['events']);
        $this->assertCount(0, $calendar->calendar([...$filters, 'user' => $outsider])['events']);

        $privileged = $this->userWith(['calendar.view', 'tasks.view', 'accountability.view', 'accountability.view_sensitive']);
        $events = $calendar->calendar([...$filters, 'user' => $privileged])['events'];
        $this->assertCount(1, $events);
        $this->assertSame('task', $events[0]['source']);
        $this->assertSame('task', $events[0]['resource_type']);
    }

    public function test_deadline_scan_emits_stable_idempotent_bounded_events(): void
    {
        $user = $this->userWith(['tasks.view']);

        $this->sequence++;
        DB::table('tasks')->insert([
            'code' => 'TASK-DUE-'.$this->sequence,
            'title' => 'Due soon',
            'status' => 'to_do',
            'priority' => 'normal',
            'due_at' => now()->addHours(2),
            'owner_id' => $user->id,
            'creator_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('phase5:scan-deadlines', ['--limit' => 3]);
        Artisan::call('phase5:scan-deadlines', ['--limit' => 3]);

        Event::assertDispatchedTimes(Phase5Event::class, 2);

        $keys = [];
        Event::assertDispatched(Phase5Event::class, function (Phase5Event $event) use (&$keys, $user): bool {
            $keys[] = $event->idempotencyKey();
            $this->assertSame('task.due_soon', $event->name);
            $this->assertSame(now()->toDateString(), $event->payload['date_bucket']);
            $this->assertSame([$user->id], $event->payload['user_ids']);

            return true;
        });
        $this->assertSame($keys[0], $keys[1]);

        Artisan::call('phase5:scan-deadlines', ['--limit' => 3, '--dry-run' => true]);
        Event::assertDispatchedTimes(Phase5Event::class, 2);
    }

    /** @param list<string> $keys */
    private function userWith(array $keys): User
    {
        $this->sequence++;
        $user = User::factory()->create();
        $role = Role::create(['key' => 'phase5-comm-'.$this->sequence, 'name' => 'Phase 5 communication operator']);
        $catalog = config('permissions', []);

        foreach ($keys as $key) {
            $definition = is_array($catalog[$key] ?? null) ? $catalog[$key] : [];
            $permission = Permission::firstOrCreate([
                'key' => $key,
                'name' => $definition['label'] ?? $key,
                'module' => $definition['module'] ?? 'phase5',
                'is_protected' => false,
                'is_retired' => false,
            ]);
            $role->permissions()->attach($permission);
        }

        app(AccessManagementService::class)->syncRoles($user, [$role->id]);

        return $user->refresh();
    }

    private function signedInboundCall(
        IntegrationRegistry $integration,
        string $secret,
        string $nonce,
    ) {
        $timestamp = (string) time();
        $body = json_encode([
            'event' => 'ignored-body-event',
            'narrative' => 'Do not persist this narrative.',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $this->call(
            'POST',
            '/webhooks/inbound/'.$integration->key,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PHASE5_TIMESTAMP' => $timestamp,
                'HTTP_X_PHASE5_NONCE' => $nonce,
                'HTTP_X_PHASE5_SIGNATURE' => app(WebhookService::class)->signature($timestamp, $nonce, $body, $secret),
                'HTTP_X_PHASE5_EVENT' => 'task.created',
            ],
            $body,
        );
    }
}
