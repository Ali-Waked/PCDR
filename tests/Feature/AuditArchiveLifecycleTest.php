<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditArchive;
use App\Models\AuditCleanupRun;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AuditLifecycleNotification;
use App\Services\AccessManagementService;
use App\Services\AuditArchiveService;
use App\Services\AuditRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

final class AuditArchiveLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_retention_is_disabled_and_scheduled_cleanup_is_report_only_by_default(): void
    {
        $retention = app(AuditRetentionService::class);

        $this->assertFalse((bool) config('audit_archive.retention.enabled'));
        $this->assertFalse($retention->isEnabled());
        $this->assertSame(0, $retention->hotMonths());

        $runs = $retention->dryRun();

        $this->assertCount(1, $runs);
        $this->assertSame(AuditCleanupRun::MODE_DRY_RUN, $runs->first()->mode);
        $this->assertSame(AuditCleanupRun::STATUS_COMPLETED, $runs->first()->status);
        $this->assertFalse($runs->first()->report['enabled']);
        $this->assertSame([], $runs->first()->report['archives']);

        $this->artisan('pcdr:audit:cleanup-due')
            ->expectsOutputToContain('No records were deleted.')
            ->assertSuccessful();

        $this->assertSame(0, AuditCleanupRun::query()->where('mode', AuditCleanupRun::MODE_EXECUTE)->count());
    }

    public function test_monthly_archive_is_private_verified_and_preserves_utf8_safely(): void
    {
        $this->travelTo('2026-11-24 12:00:00');

        $owner = User::factory()->platformOwner()->create();
        Notification::fake();
        Storage::fake('private');

        $archivedLog = AuditLog::create([
            'actor_id' => $owner->id,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '@administrator',
            'before' => ['password' => 'do-not-export'],
            'after' => ['title' => 'تقرير نهائي'],
            'context' => ['description' => 'سجل عربي', 'api_key' => 'do-not-export'],
            'created_at' => '2026-10-15 09:30:00',
        ]);
        $secondLog = AuditLog::create([
            'actor_id' => null,
            'action' => 'audit_logs.view',
            'subject_type' => 'audit_logs',
            'context' => [],
            'created_at' => '2026-10-31 23:59:59',
        ]);
        $futureLog = AuditLog::create([
            'actor_id' => null,
            'action' => 'settings.update',
            'subject_type' => 'settings',
            'context' => [],
            'created_at' => '2026-11-01 00:00:00',
        ]);

        $archive = app(AuditArchiveService::class)->createArchive('2026-10', $owner->id);

        $this->assertSame('audit-logs-2026-10.zip', $archive->name);
        $this->assertSame('2026-10', $archive->period_key);
        $this->assertSame(AuditArchive::STATUS_VERIFIED, $archive->status);
        $this->assertSame(2, $archive->record_count);
        $this->assertSame(1, $archive->attempts);
        $this->assertSame($owner->id, $archive->initiated_by);
        $this->assertNotNull($archive->verified_at);
        $this->assertSame('private', config('audit_archive.disk'));
        $this->assertTrue(Storage::disk('private')->exists($archive->file_path));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('private')->path($archive->file_path)) === true);

        try {
            $csv = $zip->getFromName('audit-logs.csv');
            $jsonl = $zip->getFromName('audit-logs.jsonl');
            $manifestJson = $zip->getFromName('manifest.json');
        } finally {
            $zip->close();
        }

        $this->assertIsString($csv);
        $this->assertIsString($jsonl);
        $this->assertIsString($manifestJson);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('تقرير نهائي', $csv);
        $this->assertStringContainsString('سجل عربي', $csv);
        $this->assertStringContainsString("'@administrator", $csv);
        $this->assertStringNotContainsString('do-not-export', $csv);

        $jsonlRows = array_values(array_filter(explode("\n", trim($jsonl))));
        $this->assertCount(2, $jsonlRows);
        $this->assertStringContainsString('تقرير نهائي', $jsonl);
        $this->assertStringContainsString('سجل عربي', $jsonl);
        $this->assertStringContainsString('[REDACTED]', $jsonl);
        $this->assertStringNotContainsString('do-not-export', $jsonl);

        $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('2026-10', $manifest['period']);
        $this->assertSame(2, $manifest['record_count']);
        $this->assertSame('1.0', $manifest['schema_version']);
        $this->assertSame('verified', $manifest['verification_status']);
        $this->assertSame(hash('sha256', $csv), $archive->checksums['audit-logs.csv']);
        $this->assertSame(hash('sha256', $jsonl), $archive->checksums['audit-logs.jsonl']);
        $this->assertSame(hash('sha256', $manifestJson), $archive->checksums['manifest.json']);

        $this->assertSame($archive->id, $archivedLog->fresh()->archive_id);
        $this->assertNotNull($archivedLog->fresh()->archived_at);
        $this->assertSame($archive->id, $secondLog->fresh()->archive_id);
        $this->assertNull($futureLog->fresh()->archive_id);
        $this->assertNull($futureLog->fresh()->archived_at);

        $this->assertFalse($archivedLog->fresh()->update(['action' => 'users.delete']));
        $this->assertSame('users.update', $archivedLog->fresh()->action);
        $this->assertFalse($archivedLog->fresh()->delete());
        $this->assertModelExists($archivedLog);

        $inspection = app(AuditArchiveService::class)->verifyArchive($archive);
        $this->assertSame(2, $inspection['record_count']);
        $this->assertSame(AuditArchive::STATUS_VERIFIED, $archive->fresh()->status);

        Notification::assertSentTo($owner, AuditLifecycleNotification::class);
    }

    public function test_duplicate_archives_are_rejected_and_failed_archives_can_be_retried(): void
    {
        $this->travelTo('2026-11-24 12:00:00');

        $owner = User::factory()->platformOwner()->create();
        Notification::fake();
        Storage::fake('private');

        AuditLog::create([
            'actor_id' => $owner->id,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '17',
            'context' => [],
            'created_at' => '2026-10-04 10:00:00',
        ]);

        $failed = AuditArchive::factory()->create([
            'period_key' => '2026-10',
            'name' => 'audit-logs-2026-10.zip',
            'period_start' => '2026-10-01 00:00:00',
            'period_end' => '2026-11-01 00:00:00',
            'cutoff_at' => '2026-10-31 23:59:59',
            'file_path' => 'audit-archives/2026-10/audit-logs-2026-10.zip',
            'status' => AuditArchive::STATUS_FAILED,
            'attempts' => 2,
            'verified_at' => null,
        ]);

        $this->assertArchiveFailure(
            fn () => app(AuditArchiveService::class)->createArchive('2026-10'),
            'Use --force to retry a failed attempt.',
        );

        $archive = app(AuditArchiveService::class)->createArchive('2026-10', $owner->id, true);

        $this->assertSame($failed->id, $archive->id);
        $this->assertSame(3, $archive->attempts);
        $this->assertSame(AuditArchive::STATUS_VERIFIED, $archive->status);
        $this->assertSame(1, $archive->record_count);
        $this->assertSame($owner->id, $archive->initiated_by);

        $this->assertArchiveFailure(
            fn () => app(AuditArchiveService::class)->createArchive('2026-10'),
            'An archive already exists for this period.',
        );
        $this->assertArchiveFailure(
            fn () => app(AuditArchiveService::class)->createArchive('2026-10', null, true),
            'An archive already exists for this period.',
        );
    }

    public function test_integrity_verification_detects_checksum_tampering(): void
    {
        $this->travelTo('2026-11-24 12:00:00');

        $owner = User::factory()->platformOwner()->create();
        Notification::fake();
        Storage::fake('private');

        AuditLog::create([
            'actor_id' => $owner->id,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '31',
            'context' => [],
            'created_at' => '2026-10-10 10:00:00',
        ]);

        $archive = app(AuditArchiveService::class)->createArchive('2026-10');
        $checksums = $archive->checksums;
        $checksums['audit-logs.csv'] = str_repeat('0', 64);
        $archive->update(['checksums' => $checksums]);

        $this->assertArchiveFailure(
            fn () => app(AuditArchiveService::class)->verifyArchive($archive, false),
            'Checksum verification failed for audit-logs.csv.',
        );

        $this->assertSame(AuditArchive::STATUS_VERIFIED, $archive->fresh()->status);
    }

    public function test_cleanup_requires_enabled_retention_confirmation_and_fresh_dry_run(): void
    {
        $this->travelTo('2026-12-24 12:00:00');

        $owner = User::factory()->platformOwner()->create();
        Notification::fake();
        Storage::fake('private');

        $log = AuditLog::create([
            'actor_id' => $owner->id,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '55',
            'context' => [],
            'created_at' => '2026-10-08 10:00:00',
        ]);

        $archive = app(AuditArchiveService::class)->createArchive('2026-10');
        $retention = app(AuditRetentionService::class);

        $this->assertArchiveFailure(
            fn () => $retention->execute($archive, AuditRetentionService::CONFIRMATION_PHRASE),
            'Audit retention is disabled.',
        );
        $this->assertSame(0, AuditCleanupRun::query()->where('mode', AuditCleanupRun::MODE_EXECUTE)->count());

        config()->set('audit_archive.retention.enabled', true);
        config()->set('audit_archive.retention.hot_months', 1);
        config()->set('audit_archive.retention.min_archive_age_days', 0);

        $this->assertArchiveFailure(
            fn () => $retention->execute($archive, 'DELETE'),
            'explicit confirmation',
        );
        $this->assertArchiveFailure(
            fn () => $retention->execute($archive, AuditRetentionService::CONFIRMATION_PHRASE),
            'dry-run report is required',
        );
        $this->assertSame(0, AuditCleanupRun::query()->where('mode', AuditCleanupRun::MODE_EXECUTE)->count());
        $this->assertModelExists($log);

        $retention->dryRun($archive->id);
        $run = $retention->execute($archive, AuditRetentionService::CONFIRMATION_PHRASE, $owner->id);

        $this->assertSame(AuditCleanupRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(1, $run->deleted_count);
        $this->assertSame(1, $run->eligible_count);
        $this->assertSame(0, $run->skipped_hold_count);
        $this->assertModelMissing($log);
        $this->assertSame(AuditArchive::STATUS_PRUNED, $archive->fresh()->status);
        $this->assertNotNull($archive->fresh()->pruned_at);

        $this->assertArchiveFailure(
            fn () => $retention->execute($archive, AuditRetentionService::CONFIRMATION_PHRASE),
            'Only a verified archive may be cleaned up.',
        );
        $this->assertSame(1, AuditCleanupRun::query()->where('mode', AuditCleanupRun::MODE_EXECUTE)->count());
    }

    public function test_legal_holds_protect_records_and_global_hold_blocks_cleanup(): void
    {
        $this->travelTo('2026-12-24 12:00:00');

        $owner = User::factory()->platformOwner()->create();
        $actor = User::factory()->create();
        Notification::fake();
        Storage::fake('private');

        $heldSubject = AuditLog::create([
            'actor_id' => null,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '101',
            'context' => [],
            'created_at' => '2026-10-02 10:00:00',
        ]);
        $heldActor = AuditLog::create([
            'actor_id' => $actor->id,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '102',
            'context' => [],
            'created_at' => '2026-10-03 10:00:00',
        ]);
        $eligible = AuditLog::create([
            'actor_id' => null,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '103',
            'context' => [],
            'created_at' => '2026-10-04 10:00:00',
        ]);

        $archive = app(AuditArchiveService::class)->createArchive('2026-10');

        config()->set('audit_archive.retention.enabled', true);
        config()->set('audit_archive.retention.hot_months', 1);
        config()->set('audit_archive.retention.min_archive_age_days', 0);

        $retention = app(AuditRetentionService::class);
        $subjectHold = $retention->createHold([
            'scope_type' => 'subject',
            'subject_type' => 'users',
            'subject_id' => '101',
            'label' => 'Subject litigation hold',
            'reason' => 'Active legal review of subject 101.',
        ], $owner->id);
        $actorHold = $retention->createHold([
            'scope_type' => 'actor',
            'actor_id' => $actor->id,
            'label' => 'Actor litigation hold',
            'reason' => 'Active legal review of this actor.',
        ], $owner->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'audit_log_holds.created',
            'subject_type' => 'audit_log_holds',
            'subject_id' => (string) $subjectHold->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'audit_log_holds.created',
            'subject_type' => 'audit_log_holds',
            'subject_id' => (string) $actorHold->id,
        ]);

        $report = $retention->dryRun($archive->id)->first();
        $this->assertSame(1, $report->eligible_count);
        $this->assertSame(2, $report->skipped_hold_count);
        $this->assertSame('verified', $report->report['integrity']);

        $globalHold = $retention->createHold([
            'scope_type' => 'global',
            'label' => 'Global evidence hold',
            'reason' => 'Institutional investigation.',
        ], $owner->id);

        $this->assertArchiveFailure(
            fn () => $retention->execute($archive, AuditRetentionService::CONFIRMATION_PHRASE),
            'global legal hold',
        );
        $this->assertModelExists($heldSubject);
        $this->assertModelExists($heldActor);
        $this->assertModelExists($eligible);

        $retention->releaseHold($globalHold->id, $owner->id);
        $this->assertNotNull($globalHold->fresh()->released_at);
        $this->assertSame($owner->id, $globalHold->fresh()->released_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'audit_log_holds.released',
            'subject_id' => (string) $globalHold->id,
        ]);

        $retention->dryRun($archive->id);
        $run = $retention->execute($archive, AuditRetentionService::CONFIRMATION_PHRASE, $owner->id);

        $this->assertSame(1, $run->deleted_count);
        $this->assertSame(2, $run->skipped_hold_count);
        $this->assertModelExists($heldSubject);
        $this->assertModelExists($heldActor);
        $this->assertModelMissing($eligible);
        $this->assertSame(AuditArchive::STATUS_VERIFIED, $archive->fresh()->status);
        $this->assertNull($archive->fresh()->pruned_at);

        $retention->dryRun($archive->id);
        $repeat = $retention->execute($archive, AuditRetentionService::CONFIRMATION_PHRASE, $owner->id);
        $this->assertSame(0, $repeat->deleted_count);
        $this->assertModelExists($heldSubject);
        $this->assertModelExists($heldActor);
    }

    public function test_archive_policy_and_private_download_are_authorized_separately(): void
    {
        $this->travelTo('2026-11-24 12:00:00');

        $owner = User::factory()->platformOwner()->create();
        Notification::fake();
        Storage::fake('private');

        AuditLog::create([
            'actor_id' => null,
            'action' => 'users.update',
            'subject_type' => 'users',
            'subject_id' => '71',
            'context' => [],
            'created_at' => '2026-10-05 10:00:00',
        ]);

        $archive = app(AuditArchiveService::class)->createArchive('2026-10');
        $viewer = $this->userWithPermission('audit_archives.view');
        $downloader = $this->userWithPermission('audit_archives.download');
        $unprivileged = User::factory()->create();

        $this->assertTrue(Gate::forUser($owner)->allows('viewAny', AuditArchive::class));
        $this->assertTrue(Gate::forUser($owner)->allows('download', $archive));
        $this->assertTrue(Gate::forUser($owner)->allows('manage', $archive));
        $this->assertTrue(Gate::forUser($owner)->allows('cleanup', $archive));

        $this->assertTrue(Gate::forUser($viewer)->allows('viewAny', AuditArchive::class));
        $this->assertFalse(Gate::forUser($viewer)->allows('download', $archive));
        $this->assertFalse(Gate::forUser($viewer)->allows('manage', $archive));
        $this->assertFalse(Gate::forUser($viewer)->allows('cleanup', $archive));

        $this->assertTrue(Gate::forUser($downloader)->allows('download', $archive));
        $this->assertFalse(Gate::forUser($downloader)->allows('manage', $archive));

        $this->assertFalse(Gate::forUser($unprivileged)->allows('viewAny', AuditArchive::class));
        $this->assertFalse(Gate::forUser($unprivileged)->allows('download', $archive));

        $downloadUrl = route('admin.audit-archives.download', [
            'locale' => 'en',
            'archive' => $archive->id,
        ]);

        $this->actingAs($viewer)->get($downloadUrl)->assertForbidden();

        $response = $this->actingAs($owner)->get($downloadUrl);
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/zip')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('PK', $response->streamedContent());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'audit_archives.download',
            'subject_type' => 'audit_archives',
            'subject_id' => (string) $archive->id,
        ]);
    }

    /** @param callable(): mixed $callback */
    private function assertArchiveFailure(callable $callback, string $expectedMessage): void
    {
        try {
            $callback();
            $this->fail('Expected the archive lifecycle operation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString($expectedMessage, $exception->getMessage());
        }
    }

    private function userWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $role = Role::create([
            'key' => 'audit_archive_'.$permission.'_role',
            'name' => 'Archive test role',
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
