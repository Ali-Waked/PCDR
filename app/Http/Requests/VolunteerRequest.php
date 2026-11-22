<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VolunteerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'area_of_interest' => ['required', 'string', 'max:96'],
            'availability' => ['required', 'string', 'in:mornings,afternoons,evenings,weekends,flexible'],
            'message' => ['nullable', 'string', 'max:4000'],
            'consent' => ['required', 'accepted'],
            'website' => ['nullable', 'size:0'],
        ];
    }
}
