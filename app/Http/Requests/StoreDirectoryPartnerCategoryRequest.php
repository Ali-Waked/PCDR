<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDirectoryPartnerCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && app(CmsAccess::class)->allows($this->user(), 'directory_partners.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/u', Rule::unique('directory_partner_categories', 'slug')],
            'is_active' => ['boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.ar.name' => ['required_with:translations.ar', 'string', 'max:160'],
            'translations.en.name' => ['required_with:translations.en', 'string', 'max:160'],
            'translations.*.description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $translations = $this->input('translations', []);
        if (is_array($translations)) {
            foreach ($translations as $locale => $values) {
                if (is_array($values) && isset($values['name'])) {
                    $translations[$locale]['name'] = trim((string) $values['name']);
                }
            }
        }

        $this->merge([
            'slug' => is_string($this->input('slug')) ? trim((string) $this->input('slug')) : '',
            'translations' => $translations,
        ]);
    }
}
