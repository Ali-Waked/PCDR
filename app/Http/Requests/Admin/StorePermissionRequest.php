<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Permission::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9_.-]+$/', 'unique:permissions,key'],
            'name' => ['required', 'string', 'max:160'],
            'module' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_.-]+$/'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
