<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', Setting::class);
    }

    /** @return array<string, mixed> */
    public function rules(SettingsService $service): array
    {
        $rules = [];

        foreach ($service->definitions() as $key => $definition) {
            if (array_key_exists($key, $this->all())) {
                $rules[str_replace('.', '\\.', $key)] = $definition['rules'] ?? ['nullable'];
            }
        }

        return $rules;
    }

    /**
     * Return only validated, known settings keys in their original dotted form.
     *
     * @return array<string, mixed>
     */
    public function settingsValues(SettingsService $service): array
    {
        $validated = $this->validated();
        $values = [];

        foreach ($service->definitions() as $key => $definition) {
            if (array_key_exists($key, $validated)) {
                $values[$key] = $validated[$key];
            }
        }

        return $values;
    }
}
