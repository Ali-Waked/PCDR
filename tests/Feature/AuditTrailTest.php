<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_service_redacts_sensitive_keys_recursively(): void
    {
        $log = app(AuditService::class)->record(
            'security.test',
            'users',
            '1',
            [
                'password' => 'do-not-log-me',
                'nested' => ['api_token' => 'token-value'],
            ],
            [
                'authorization' => 'Bearer token-value',
                'safe' => 'visible',
            ],
            ['session_id' => 'session-value', 'note' => 'done'],
        );

        $this->assertSame('[REDACTED]', $log->before['password']);
        $this->assertSame('[REDACTED]', $log->before['nested']['api_token']);
        $this->assertSame('[REDACTED]', $log->after['authorization']);
        $this->assertSame('visible', $log->after['safe']);
        $this->assertSame('[REDACTED]', $log->context['session_id']);
        $this->assertSame('done', $log->context['note']);

        $serialized = json_encode($log, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('do-not-log-me', $serialized);
        $this->assertStringNotContainsString('Bearer token-value', $serialized);
        $this->assertStringNotContainsString('session-value', $serialized);
    }

    public function test_audit_logs_cannot_be_updated_or_deleted(): void
    {
        $log = AuditLog::create([
            'actor_id' => null,
            'action' => 'security.immutable',
            'subject_type' => 'users',
            'subject_id' => '1',
            'context' => [],
            'created_at' => now(),
        ]);

        $log->action = 'security.tampered';
        $log->save();
        $log->delete();

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'security.immutable',
        ]);
    }

    public function test_audit_log_viewing_and_export_require_authorization(): void
    {
        $outsider = User::factory()->create();
        $owner = User::factory()->platformOwner()->create();

        $this->actingAs($outsider)->get('/ar/admin/audit-logs')->assertForbidden();

        $this->actingAs($owner)->get('/ar/admin/audit-logs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/AuditLogs/Index'));

        $export = $this->actingAs($owner)->get('/ar/admin/audit-logs/export')->assertOk();

        $this->assertStringContainsString('text/csv', $export->headers->get('Content-Type') ?? '');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'audit_logs.view',
            'actor_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'audit_logs.export',
            'actor_id' => $owner->id,
        ]);

        $viewEntry = AuditLog::where('action', 'audit_logs.view')->latest()->firstOrFail();
        $exportEntry = AuditLog::where('action', 'audit_logs.export')->latest()->firstOrFail();

        $this->assertNull($viewEntry->before);
        $this->assertNull($viewEntry->after);
        $this->assertNull($exportEntry->before);
        $this->assertNull($exportEntry->after);
    }
}
