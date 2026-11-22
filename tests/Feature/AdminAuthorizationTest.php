<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permissions_cannot_view_admin_pages(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/ar/admin/users')
            ->assertForbidden();
    }

    public function test_role_permission_grants_admin_page_access(): void
    {
        $role = Role::create(['key' => 'user_viewer', 'name' => 'User viewer']);
        $permission = Permission::create([
            'key' => 'users.view',
            'name' => 'View users',
            'module' => 'iam',
            'is_protected' => true,
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        app(AccessManagementService::class)->syncRoles($user, [$role->id]);

        $this->actingAs($user)->get('/ar/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Users/Index'));
    }

    public function test_owner_can_create_update_and_change_status_of_users(): void
    {
        $owner = User::factory()->platformOwner()->create();
        $role = Role::create(['key' => 'operator', 'name' => 'Operator']);

        $this->actingAs($owner)->post('/ar/admin/users', [
            'name' => 'Managed User',
            'email' => 'managed@example.test',
            'password' => 'managed-password-123',
            'status' => 'active',
            'locale' => 'en',
            'roles' => [$role->id],
        ])->assertRedirect('/ar/admin/users');

        $user = User::where('email', 'managed@example.test')->firstOrFail();

        $this->assertTrue($user->roles()->where('roles.id', $role->id)->exists());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'subject_id' => (string) $user->id,
            'actor_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.roles_changed',
            'subject_id' => (string) $user->id,
            'actor_id' => $owner->id,
        ]);

        $this->actingAs($owner)->patch("/ar/admin/users/{$user->id}/status", [
            'status' => 'suspended',
        ])->assertRedirect();

        $this->assertSame('suspended', $user->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.status_changed',
            'subject_id' => (string) $user->id,
        ]);
    }

    public function test_user_creation_validates_password_strength(): void
    {
        $owner = User::factory()->platformOwner()->create();

        $this->actingAs($owner)->from('/ar/admin/users/create')->post('/ar/admin/users', [
            'name' => 'Weak Password',
            'email' => 'weak@example.test',
            'password' => 'short',
            'status' => 'active',
            'locale' => 'ar',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.test']);
    }

    public function test_user_cannot_assign_roles_to_self_even_with_the_permission(): void
    {
        $assignerRole = Role::create(['key' => 'assigner', 'name' => 'Assigner']);
        $assignPermission = Permission::create([
            'key' => 'users.assign_roles',
            'name' => 'Assign roles',
            'module' => 'iam',
            'is_protected' => true,
        ]);
        $assignerRole->permissions()->attach($assignPermission);

        $user = User::factory()->create();
        app(AccessManagementService::class)->syncRoles($user, [$assignerRole->id]);

        $targetRole = Role::create(['key' => 'escalation', 'name' => 'Escalation']);

        $this->actingAs($user)->put("/ar/admin/users/{$user->id}/roles", [
            'roles' => [$targetRole->id],
        ])->assertForbidden();

        $this->assertSame(
            [$assignerRole->id],
            $user->roles()->pluck('roles.id')->all(),
        );
    }

    public function test_protected_permission_cannot_be_renamed_or_deleted_by_owner(): void
    {
        $owner = User::factory()->platformOwner()->create();
        $permission = Permission::create([
            'key' => 'users.view',
            'name' => 'View users',
            'module' => 'iam',
            'is_protected' => true,
        ]);

        $this->actingAs($owner)->delete("/ar/admin/permissions/{$permission->id}")->assertStatus(422);
        $this->actingAs($owner)->put("/ar/admin/permissions/{$permission->id}", [
            'key' => 'users.view.changed',
            'name' => 'Changed',
            'module' => 'iam',
        ])->assertStatus(422);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'key' => 'users.view',
        ]);
    }

    public function test_non_owner_without_grant_protected_cannot_assign_protected_permissions(): void
    {
        $role = Role::create(['key' => 'role_editor', 'name' => 'Role editor']);
        $updateRolePermission = Permission::create([
            'key' => 'roles.update',
            'name' => 'Update roles',
            'module' => 'iam',
            'is_protected' => true,
        ]);
        $protectedPermission = Permission::create([
            'key' => 'users.view',
            'name' => 'View users',
            'module' => 'iam',
            'is_protected' => true,
        ]);
        $role->permissions()->attach($updateRolePermission);

        $user = User::factory()->create();
        app(AccessManagementService::class)->syncRoles($user, [$role->id]);

        $this->actingAs($user)->put("/ar/admin/roles/{$role->id}/permissions", [
            'permissions' => [$protectedPermission->id],
        ])->assertForbidden();

        $this->assertSame(0, $role->permissions()->where('permissions.id', $protectedPermission->id)->count());
    }
}
