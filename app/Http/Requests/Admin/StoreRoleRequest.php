<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\RolePermissionInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! Gate::allows('create', Role::class)) {
            return false;
        }

        if (! $this->has('permissions')) {
            return true;
        }

        return RolePermissionInput::canAssign(
            $this->user(),
            RolePermissionInput::normalize($this->input('permissions', [])),
        );
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('permissions')) {
            $this->merge([
                'permissions' => RolePermissionInput::normalize($this->input('permissions', [])),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_.-]+$/', 'unique:roles,key'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            ...RolePermissionInput::rules(),
        ];
    }
}
