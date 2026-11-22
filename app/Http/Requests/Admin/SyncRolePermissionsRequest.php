<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Role $role */
        $role = $this->route('role');

        if (! Gate::allows('update', $role)) {
            return false;
        }

        $requested = array_map('intval', $this->input('permissions', []));
        $current = $role->permissions()->pluck('permissions.id')->all();
        $changed = array_values(array_unique(array_merge($requested, $current)));

        $touchesProtected = Permission::whereIn('id', $changed)->where('is_protected', true)->exists();

        return ! $touchesProtected || $this->user()?->isPlatformOwner() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
