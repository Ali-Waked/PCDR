<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

final class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:96', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:email_templates,key'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
            'translations' => ['required', 'array'],
            'translations.*.name' => ['required', 'string', 'max:160'],
            'translations.*.description' => ['nullable', 'string', 'max:500'],
            'translations.*.subject' => ['required', 'string', 'max:255'],
            'translations.*.body' => ['required', 'string', 'max:10000'],
        ];
    }
}
