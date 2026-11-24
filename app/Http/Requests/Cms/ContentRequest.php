<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use App\Enums\ContentStatus;
use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;

abstract class ContentRequest extends FormRequest
{
    protected string $permission = 'pillars.view';

    public function authorize(): bool
    {
        $permission = $this->isMethod('POST') && str_ends_with($this->permission, '.update')
            ? str_replace('.update', '.create', $this->permission)
            : $this->permission;

        return $this->user() !== null && app(CmsAccess::class)->allows($this->user(), $permission);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', ContentStatus::values())],
            'published_at' => ['nullable', 'date'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.ar.title' => ['required_with:translations.ar', 'string', 'max:255'],
            'translations.en.title' => ['required_with:translations.en', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:180', 'regex:/^[^\s\/]+$/u'],
            'translations.*.summary' => ['nullable', 'string', 'max:1000'],
            'translations.*.seo_title' => ['nullable', 'string', 'max:255'],
            'translations.*.seo_description' => ['nullable', 'string', 'max:500'],
            'translations.*.robots' => ['nullable', 'string', 'max:64'],
            'translations.*.body' => ['nullable', 'string', 'max:100000'],
            'translations.*.description' => ['nullable', 'string', 'max:100000'],
            'translations.*.objectives' => ['nullable', 'string', 'max:20000'],
            'translations.*.target_groups' => ['nullable', 'string', 'max:20000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'translations.ar.title.required_with' => __('website.cms.translation_required'),
            'translations.en.title.required_with' => __('website.cms.translation_required'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $translations = $this->input('translations', []);
        if (is_array($translations)) {
            foreach ($translations as $locale => $values) {
                if (is_array($values) && isset($values['title'])) {
                    $translations[$locale]['title'] = trim((string) $values['title']);
                }
            }
            $this->merge(['translations' => $translations]);
        }
    }
}
