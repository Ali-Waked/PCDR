<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Permission;
use App\Services\RbacService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Permission $permission */
        $permission = $this->route('permission');

        return Gate::allows('update', $permission);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Permission $permission */
        $permission = $this->route('permission');

        return [
            'key' => [
                'required',
                'string',
                'max:160',
                'regex:/^[a-z0-9_.-]+$/',
                'unique:permissions,key,'.$permission->id,
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (app(RbacService::class)->isAuthorityPermission((string) $value)) {
                        $fail(__('ui.authority_permission_reserved'));
                    }
                },
            ],
            'name' => ['required', 'string', 'max:160'],
            'module' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_.-]+$/'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
