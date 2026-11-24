<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessManagementService;
use App\Services\CmsAccess;
use App\Services\RbacService;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlatformOwnerDashboardFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_receives_universal_technical_access_including_future_permissions(): void
    {
        $owner = User::factory()->platformOwner()->create();
        $rbac = app(RbacService::class);

        $this->assertTrue($rbac->permissionsFor($owner)->contains('*'));
        $this->assertTrue($rbac->has($owner, 'future.module.manage'));
        $this->assertTrue(Gate::forUser($owner)->allows('future.module.manage'));
        $this->assertTrue(Gate::forUser($owner)->allows('update', User::class));
    }

    public function test_normal_users_receive_only_granted_technical_permissions(): void
    {
        $role = Role::create(['key' => 'reviewer', 'name' => 'Reviewer']);
        $reviewPermission = Permission::create([
            'key' => 'content.review',
            'name' => 'Review content',
            'module' => 'content',
        ]);
        $role->permissions()->attach($reviewPermission);

        $user = User::factory()->create();
        app(AccessManagementService::class)->syncRoles($user, [$role->id]);
        $rbac = app(RbacService::class);

        $this->assertTrue($rbac->has($user, 'content.review'));
        $this->assertFalse($rbac->has($user, 'future.module.manage'));
        $this->assertFalse(Gate::forUser($user)->allows('future.module.manage'));
    }

    public function test_authority_like_permissions_are_ignored_even_when_stored_on_a_role(): void
    {
        $role = Role::create(['key' => 'approver', 'name' => 'Approver']);
        $authorityPermission = Permission::create([
            'key' => 'content.approve',
            'name' => 'Approve content',
            'module' => 'content',
        ]);
        $role->permissions()->attach($authorityPermission);

        $user = User::factory()->create();
        app(AccessManagementService::class)->syncRoles($user, [$role->id]);
        $rbac = app(RbacService::class);

        $this->assertFalse($rbac->has($user, 'content.approve'));
        $this->assertFalse($rbac->permissionsFor($user)->contains('content.approve'));
        $this->assertFalse(Gate::forUser($user)->allows('content.approve'));
        $this->assertFalse(app(CmsAccess::class)->allows($user, 'content.approve'));
    }

    public function test_institutional_approval_authority_is_never_granted_to_the_owner(): void
    {
        $owner = User::factory()->platformOwner()->create();
        $rbac = app(RbacService::class);
        $authorityKeys = [
            'content.approve',
            'content.approvals',
            'content.rejection',
            'content.sign-off',
            'institutional.appoint',
            'governance.ratify',
            'authority.signoff',
            'documents.signoff_request',
            'future.module.delegate',
            'future.module.nomination',
            'future.module.ratified',
        ];

        foreach ($authorityKeys as $permission) {
            $this->assertTrue($rbac->isAuthorityPermission($permission), $permission);
            $this->assertFalse($rbac->has($owner, $permission), $permission);
            $this->assertFalse(Gate::forUser($owner)->allows($permission), $permission);
            $this->assertFalse(app(CmsAccess::class)->allows($owner, $permission), $permission);
        }

        foreach (['approve', 'approveDocument', 'institutional approval', 'sign-off', 'signOffRequest', 'approver', 'ratified'] as $ability) {
            $this->assertTrue($rbac->isAuthorityAbility($ability), $ability);
            $this->assertFalse(Gate::forUser($owner)->allows($ability), $ability);
        }

        $this->assertFalse($rbac->isAuthorityPermission('documents.review'));
        $this->assertFalse($rbac->isAuthorityPermission('content.publish'));
        $this->assertFalse($rbac->isAuthorityAbility('publishDocument'));
        $this->assertTrue($rbac->has($owner, 'documents.review'));
        $this->assertTrue($rbac->has($owner, 'content.publish'));
        $this->assertTrue(Gate::forUser($owner)->allows('publishDocument'));
    }

    public function test_unified_access_page_rejects_all_institutional_authority_keys(): void
    {
        $owner = User::factory()->platformOwner()->create();
        $role = Role::create(['key' => 'authority_assignment_target', 'name' => 'Authority assignment target']);

        foreach (['content.approve', 'content.sign-off', 'institutional.appoint', 'governance.ratify', 'future.module.signoff', 'documents.signoff_request', 'content.approvals'] as $key) {
            $this->assertFalse(PermissionCatalog::isSupported($key), $key);

            $permission = Permission::create([
                'key' => $key,
                'name' => 'Reserved authority',
                'module' => 'governance',
            ]);

            $this->actingAs($owner)->put("/ar/admin/roles/{$role->id}/permissions", [
                'permissions' => [$permission->id],
            ])->assertSessionHasErrors('permissions.0');

            $this->assertFalse($role->permissions()->where('permissions.id', $permission->id)->exists());
        }

        foreach (['admin.permissions.create', 'admin.permissions.store', 'admin.permissions.edit', 'admin.permissions.update', 'admin.permissions.destroy'] as $route) {
            $this->assertFalse(Route::has($route), $route);
        }
    }

    public function test_unauthenticated_users_are_redirected_from_every_administrative_surface(): void
    {
        $this->get('/ar/dashboard')->assertRedirect('/ar/login');
        $this->get('/ar/admin/users')->assertRedirect('/ar/login');
        $this->get('/ar/cms')->assertRedirect('/ar/cms/login');
        $this->get('/ar/operations/dashboard')->assertRedirect('/ar/login');
        $this->get('/ar/operations/phase5/dashboard')->assertRedirect('/ar/login');
    }

    public function test_inactive_owner_is_blocked_from_the_dashboard(): void
    {
        $owner = User::factory()->platformOwner()->inactive()->create();

        $this->actingAs($owner)->get('/ar/dashboard')->assertRedirect('/ar/login');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.blocked_inactive',
            'subject_type' => 'users',
            'subject_id' => (string) $owner->id,
        ]);
    }

    public function test_suspended_owner_is_blocked_from_the_dashboard(): void
    {
        $owner = User::factory()->platformOwner()->suspended()->create();

        $this->actingAs($owner)->get('/ar/dashboard')->assertRedirect('/ar/login');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.blocked_inactive',
            'subject_type' => 'users',
            'subject_id' => (string) $owner->id,
        ]);
    }

    public function test_owner_can_reach_representative_pages_in_every_module(): void
    {
        $owner = User::factory()->platformOwner()->create();

        $this->actingAs($owner)->get('/ar/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));

        $this->actingAs($owner)->get('/ar/cms')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Cms/Dashboard'));

        $this->actingAs($owner)->get('/ar/operations/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Operations/Dashboard'));

        $this->actingAs($owner)->get('/ar/operations/phase5/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Phase5/Dashboard'));
    }

    public function test_shared_authority_and_navigation_props_are_server_authoritative(): void
    {
        $owner = User::factory()->platformOwner()->create();

        $this->actingAs($owner)->get('/ar/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('authority.is_platform_owner', true)
                ->where('authority.institutional_approval.granted', false)
                ->where('authority.institutional_approval.reason', fn ($value): bool => is_string($value) && $value !== '')
                ->where('adminNavigation.0.key', 'overview')
                ->where('adminNavigation.1.key', 'content')
                ->where('adminNavigation.2.key', 'communications')
                ->where('adminNavigation.3.key', 'identity')
                ->where('adminNavigation.4.key', 'system')
                ->where('adminNavigation', fn ($groups): bool => $groups->count() === 5
                    && collect($groups->firstWhere('key', 'communications')['items'])
                        ->contains(fn ($item): bool => $item['key'] === 'email_templates'))
                ->missing('adminNavigation.5'));
    }

    public function test_navigation_is_filtered_to_permissions_granted_to_a_normal_user(): void
    {
        $role = Role::create(['key' => 'user_viewer', 'name' => 'User viewer']);
        $permission = Permission::create([
            'key' => 'users.view',
            'name' => 'View users',
            'module' => 'iam',
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        app(AccessManagementService::class)->syncRoles($user, [$role->id]);

        $this->actingAs($user)->get('/ar/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('authority.is_platform_owner', false)
                ->where('authority.institutional_approval.granted', false)
                ->where('adminNavigation.0.key', 'overview')
                ->where('adminNavigation.1.key', 'identity')
                ->where('adminNavigation', fn ($groups): bool => count($groups) === 2));
    }

    public function test_dashboard_statistics_and_latest_event_come_from_the_database(): void
    {
        $owner = User::factory()->platformOwner()->create();
        User::factory()->create();
        Role::create(['key' => 'auditor', 'name' => 'Auditor']);
        Permission::create(['key' => 'audit.view', 'name' => 'View audit', 'module' => 'audit']);

        AuditLog::create([
            'actor_id' => $owner->id,
            'action' => 'security.test_event',
            'subject_type' => 'users',
            'subject_id' => (string) $owner->id,
            'context' => [],
            'created_at' => now(),
        ]);

        $this->actingAs($owner)->get('/ar/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('statistics.users', 2)
                ->where('statistics.active_users', 2)
                ->where('statistics.roles', 1)
                ->where('statistics.permissions', 1)
                ->where('statistics.recent_events', 1)
                ->where('statistics.content_items', 0)
                ->where('statistics.operational_records', 0)
                ->where('statistics.phase5_tasks', 0)
                ->where('statistics.phase5_workflows', 0)
                ->where('latestEvent.action', 'security.test_event'));
    }

    public function test_administrative_layouts_mirror_arabic_rtl_and_english_ltr(): void
    {
        $owner = User::factory()->platformOwner()->create();

        $this->actingAs($owner)->get('/ar/dashboard')
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'ar')
                ->where('direction', 'rtl')
                ->where('translations.ui.admin_dashboard.hero_title', 'منصة واحدة، تحكم تقني كامل')
                ->where('adminNavigation.0.label', 'نظرة عامة'));

        $this->actingAs($owner)->get('/en/dashboard')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'en')
                ->where('direction', 'ltr')
                ->where('translations.ui.admin_dashboard.hero_title', 'One platform, complete technical control')
                ->where('adminNavigation.0.label', 'Overview'));
    }

    public function test_role_mutations_are_audit_logged(): void
    {
        $owner = User::factory()->platformOwner()->create();
        $permission = Permission::create([
            'key' => 'news.view',
            'name' => 'View news',
            'module' => 'website',
        ]);

        $this->actingAs($owner)->post('/ar/admin/roles', [
            'key' => 'audited_role',
            'name' => 'Audited role',
            'permissions' => [$permission->id],
        ])->assertRedirect();

        $role = Role::where('key', 'audited_role')->firstOrFail();

        $this->actingAs($owner)->put("/ar/admin/roles/{$role->id}", [
            'key' => 'audited_role',
            'name' => 'Audited role updated',
        ])->assertRedirect();

        $this->actingAs($owner)->put("/ar/admin/roles/{$role->id}/permissions", [
            'permissions' => [],
        ])->assertRedirect();

        $this->actingAs($owner)->delete("/ar/admin/roles/{$role->id}")->assertRedirect();

        foreach (['role.created', 'role.updated', 'role.permissions_changed', 'role.deleted'] as $action) {
            $this->assertDatabaseHas('audit_logs', [
                'action' => $action,
                'actor_id' => $owner->id,
            ]);
        }
    }

    public function test_admin_brand_uses_the_exact_approved_static_logo_asset(): void
    {
        $logo = public_path('assets/pcdr-homepage/logo/pcdr-logo.png');
        $brandMark = (string) file_get_contents(resource_path('js/Components/BrandMark.vue'));

        $this->assertFileExists($logo);
        $this->assertSame(
            '5834fb72446a75cd56ed6a51a06ae1dafcaa56559c62e6906603978eb27ecb9e',
            (string) hash_file('sha256', $logo),
        );
        $this->assertStringContainsString('/assets/pcdr-homepage/logo/pcdr-logo.png', $brandMark);
    }
}
