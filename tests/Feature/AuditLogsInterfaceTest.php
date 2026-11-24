<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessManagementService;
use App\Support\AuditRedactor;
use App\Support\CsvSafeWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AuditLogsInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_audit_log_explorer(): void
    {
        $response = $this->get(route('admin.audit-logs.index', ['locale' => 'en']));

        $response->assertRedirect(route('login.show', ['locale' => 'en']));
    }

    public function test_user_without_audit_view_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index', ['locale' => 'en']))
            ->assertForbidden();
    }

    public function test_platform_owner_receives_the_full_audit_explorer_contract(): void
    {
        $this->travelTo('2026-11-24 12:00:00');

        $owner = User::factory()->platformOwner()->create();
        $actor = User::factory()->create(['name' => 'Critical Actor']);

        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => 'users.delete',
            'subject_type' => 'users',
            'subject_id' => '42',
            'context' => ['description' => 'حذف حساب'],
            'created_at' => now(),
        ]);
        AuditLog::create([
            'actor_id' => null,
            'action' => 'settings.update',
            'subject_type' => 'settings',
            'subject_id' => 'security',
            'context' => ['description' => 'Policy updated'],
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('admin.audit-logs.index', ['locale' => 'ar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AuditLogs/Index')
                ->where('logs.total', 3)
                ->where('stats.total', 3)
                ->where('stats.today', 2)
                ->where('stats.deleted', 1)
                ->where('stats.security', 1)
                ->where('filters.search', '')
                ->where('can.view', true)
                ->where('can.export', true)
                ->where('can.archives', true)
                ->has('logs.data', 3)
                ->has('meta.actors', 2)
                ->has('meta.actions', 3)
                ->where('logs.data.0.action', 'audit_logs.view')
                ->where('logs.data.1.severity', 'critical')
                ->where('logs.data.2.event_class', 'system'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'audit_logs.view',
            'actor_id' => $owner->id,
            'subject_type' => 'audit_logs',
        ]);
    }

    public function test_filters_and_pagination_are_applied_to_the_audit_explorer(): void
    {
        $this->travelTo('2026-11-24 12:00:00');

        $owner = User::factory()->platformOwner()->create();
        $actor = User::factory()->create(['name' => 'Filtered Actor']);

        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '7',
            'context' => [],
            'created_at' => now()->subDays(2),
        ]);
        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => 'users.delete',
            'subject_type' => 'users',
            'subject_id' => '8',
            'context' => [],
            'created_at' => now()->subDays(40),
        ]);

        $this->actingAs($owner)
            ->get(route('admin.audit-logs.index', [
                'locale' => 'en',
                'search' => 'Filtered',
                'actor' => $actor->id,
                'subject' => 'users',
                'from' => '2026-11-01',
                'to' => '2026-11-30',
                'event_class' => 'people',
                'severity' => 'warning',
                'per_page' => 500,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AuditLogs/Index')
                ->where('logs.total', 1)
                ->where('logs.per_page', 100)
                ->where('stats.total', 1)
                ->where('filters.search', 'Filtered')
                ->where('filters.actor', (string) $actor->id)
                ->where('filters.event_class', 'people')
                ->where('filters.severity', 'warning')
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'users.update'));
    }

    public function test_details_endpoint_derives_changes_and_restricts_network_metadata(): void
    {
        $owner = User::factory()->platformOwner()->create();
        $viewer = $this->userWithPermission('audit_logs.view');

        $log = AuditLog::create([
            'actor_id' => $owner->id,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '9',
            'before' => ['name' => 'قبل', 'token' => 'old-secret'],
            'after' => ['name' => 'بعد', 'token' => 'new-secret'],
            'context' => [
                'description' => 'تم التعديل',
                'ip' => '203.0.113.10',
                'user_agent' => 'AuditTest/1.0',
                'api_key' => 'visible-secret',
            ],
            'created_at' => now(),
        ]);

        $viewerResponse = $this->actingAs($viewer)
            ->getJson(route('admin.audit-logs.show', ['locale' => 'en', 'auditLog' => $log->id]))
            ->assertOk()
            ->assertJsonPath('log.description', 'تم التعديل')
            ->assertJsonPath('log.severity', 'warning')
            ->assertJsonPath('log.event_class', 'people')
            ->assertJsonPath('log.context.ip', '[RESTRICTED]')
            ->assertJsonPath('log.context.user_agent', '[RESTRICTED]')
            ->assertJsonPath('log.context.api_key', AuditRedactor::REDACTED)
            ->assertJsonPath('log.before.token', AuditRedactor::REDACTED)
            ->assertJsonPath('log.after.token', AuditRedactor::REDACTED)
            ->assertJsonCount(1, 'log.changed_fields')
            ->assertJsonPath('log.changed_fields.0.field', 'name');

        $this->assertStringNotContainsString('203.0.113.10', $viewerResponse->getContent());

        $this->actingAs($owner)
            ->getJson(route('admin.audit-logs.show', ['locale' => 'en', 'auditLog' => $log->id]))
            ->assertOk()
            ->assertJsonPath('log.context.ip', '203.0.113.10')
            ->assertJsonPath('log.context.user_agent', 'AuditTest/1.0');
    }

    public function test_csv_export_is_redacted_utf8_safe_and_formula_injection_resistant(): void
    {
        $this->travelTo('2026-11-24 12:00:00');

        $owner = User::factory()->platformOwner()->create();

        $log = AuditLog::create([
            'actor_id' => $owner->id,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '@administrator',
            'before' => ['password' => 'do-not-export'],
            'after' => ['title' => 'تقرير نهائي'],
            'context' => ['formula' => '=SUM(1+1)', 'password' => 'do-not-export'],
            'request_id' => '=HYPERLINK("http://evil.test")',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('admin.audit-logs.export', [
                'locale' => 'en',
                'format' => 'csv',
                'action' => 'users.update',
                'from' => '2026-11-01',
                'to' => '2026-11-30',
            ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('تقرير نهائي', $csv);
        $this->assertStringContainsString("'@administrator", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('do-not-export', $csv);
        $this->assertStringContainsString((string) $log->id, $csv);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'audit_logs.export',
            'actor_id' => $owner->id,
        ]);
    }

    public function test_jsonl_export_preserves_arabic_and_redacts_sensitive_fields(): void
    {
        $owner = User::factory()->platformOwner()->create();

        AuditLog::create([
            'actor_id' => $owner->id,
            'action' => 'settings.update',
            'subject_type' => 'settings',
            'subject_id' => 'locale',
            'after' => ['value' => 'العربية'],
            'context' => ['secret' => 'hidden-secret'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('admin.audit-logs.export', ['locale' => 'en', 'format' => 'jsonl']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/x-ndjson; charset=UTF-8')
            ->assertHeader('Cache-Control', 'no-store, private');

        $jsonl = trim($response->streamedContent());
        $rows = array_values(array_filter(explode("\n", $jsonl)));

        $this->assertCount(2, $rows);
        $this->assertStringContainsString('العربية', $jsonl);
        $this->assertStringNotContainsString('hidden-secret', $jsonl);

        $decodedRows = array_map(
            fn (string $row): array => json_decode($row, true, 512, JSON_THROW_ON_ERROR),
            $rows,
        );
        $target = collect($decodedRows)->firstWhere('action', 'settings.update');

        $this->assertNotNull($target);
        $this->assertSame(AuditRedactor::REDACTED, $target['context']['secret']);
    }

    public function test_export_requires_the_export_permission(): void
    {
        $viewer = $this->userWithPermission('audit_logs.view');

        $this->actingAs($viewer)
            ->get(route('admin.audit-logs.export', ['locale' => 'en', 'format' => 'csv']))
            ->assertForbidden();
    }

    public function test_csv_field_writer_neutralizes_leading_formula_characters_without_damaging_numbers(): void
    {
        $this->assertSame("'=SUM(1+1)", CsvSafeWriter::field('=SUM(1+1)'));
        $this->assertSame("'+cmd", CsvSafeWriter::field('+cmd'));
        $this->assertSame("'-cmd", CsvSafeWriter::field('-cmd'));
        $this->assertSame("'@user", CsvSafeWriter::field('@user'));
        $this->assertSame('42', CsvSafeWriter::field('42'));
        $this->assertSame('-7', CsvSafeWriter::field('-7'));
        $this->assertSame('["تقرير"]', CsvSafeWriter::field(['تقرير']));
        $this->assertSame('[REDACTED]', AuditRedactor::redact(['password' => 'secret'])['password']);
    }

    private function userWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $role = Role::create([
            'key' => 'audit_'.$permission.'_role',
            'name' => 'Audit test role',
        ]);
        $record = Permission::query()->where('key', $permission)->first();

        if ($record === null) {
            $record = Permission::create([
                'key' => $permission,
                'name' => $permission,
                'module' => 'platform',
            ]);
        }

        $role->permissions()->attach($record);
        app(AccessManagementService::class)->syncRoles($user, [$role->id]);

        return $user;
    }
}
