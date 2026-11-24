<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MediaType;
use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDirectoryPartnerRequest extends FormRequest
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
            'directory_partner_category_id' => ['nullable', 'integer', 'exists:directory_partner_categories,id'],
            'logo_media_id' => [
                'nullable', 'integer',
                Rule::exists('media', 'id')->where(static fn ($query) => $query
                    ->where('type', MediaType::Image->value)
                    ->where('is_restricted', false)),
            ],
            'website_url' => ['nullable', 'url', 'max:500'],
            'partnership_year' => ['nullable', 'integer', 'between:1900,'.((int) date('Y') + 1)],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.ar.name' => ['required_with:translations.ar', 'string', 'max:180'],
            'translations.en.name' => ['required_with:translations.en', 'string', 'max:180'],
            'translations.ar.slug' => [
                'nullable', 'string', 'max:180', 'regex:/^[^\s\/]+$/u',
                Rule::unique('directory_partner_translations', 'slug')
                    ->where(static fn ($query) => $query->where('locale', 'ar')),
            ],
            'translations.en.slug' => [
                'nullable', 'string', 'max:180', 'regex:/^[^\s\/]+$/u',
                Rule::unique('directory_partner_translations', 'slug')
                    ->where(static fn ($query) => $query->where('locale', 'en')),
            ],
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

        $this->merge(['translations' => $translations]);
    }
}
