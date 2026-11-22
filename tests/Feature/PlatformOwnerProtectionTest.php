<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AccessManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class PlatformOwnerProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['PCDR_OWNER_EMAIL', 'PCDR_OWNER_NAME', 'PCDR_OWNER_PASSWORD'] as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }

    public function test_bootstrap_command_creates_one_owner_from_secure_interactive_input(): void
    {
        $this->artisan('pcdr:platform-owner:bootstrap')
            ->expectsQuestion('Platform Owner email', 'owner@example.test')
            ->expectsQuestion('Platform Owner display name', 'Platform Owner')
            ->expectsQuestion('Platform Owner password (minimum 12 characters)', 'owner-password-123')
            ->assertExitCode(0);

        $owner = User::where('is_platform_owner', true)->firstOrFail();

        $this->assertSame('owner@example.test', $owner->email);
        $this->assertTrue($owner->isPlatformOwner());
        $this->assertSame(User::STATUS_ACTIVE, $owner->status);
        $this->assertNotNull($owner->email_verified_at);
        $this->assertTrue(Hash::check('owner-password-123', $owner->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'platform_owner.bootstrapped',
            'subject_id' => (string) $owner->id,
        ]);
    }

    public function test_bootstrap_command_refuses_to_create_a_second_owner(): void
    {
        User::factory()->platformOwner()->create();

        $this->artisan('pcdr:platform-owner:bootstrap')->assertExitCode(1);

        $this->assertSame(1, User::where('is_platform_owner', true)->count());
    }

    public function test_last_owner_cannot_be_deactivated_or_deleted(): void
    {
        $owner = User::factory()->platformOwner()->create();
        $service = app(AccessManagementService::class);

        try {
            $service->setStatus($owner, User::STATUS_INACTIVE);
            $this->fail('The last active Platform Owner was deactivated.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        try {
            $service->delete($owner);
            $this->fail('The Platform Owner was deleted.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        $this->assertSame(User::STATUS_ACTIVE, $owner->fresh()->status);
        $this->assertNotNull(User::find($owner->id));
    }

    public function test_owner_bootstrap_never_writes_the_password_to_audit_logs(): void
    {
        $this->artisan('pcdr:platform-owner:bootstrap')
            ->expectsQuestion('Platform Owner email', 'owner@example.test')
            ->expectsQuestion('Platform Owner display name', 'Platform Owner')
            ->expectsQuestion('Platform Owner password (minimum 12 characters)', 'owner-password-123')
            ->assertExitCode(0);

        $serialized = json_encode(AuditLog::all(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('owner-password-123', $serialized);
        $this->assertStringNotContainsString('PCDR_OWNER_PASSWORD', $serialized);
    }
}
