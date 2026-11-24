<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\RolePermissionInput;
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

        /** @var list<int> $current */
        $current = $role->permissions()->pluck('permissions.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        return RolePermissionInput::canAssign(
            $this->user(),
            RolePermissionInput::normalize($this->input('permissions', [])),
            $current,
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permissions' => RolePermissionInput::normalize($this->input('permissions', [])),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return RolePermissionInput::rules(required: true);
    }
}
