<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\CmsAccess;
use Illuminate\Foundation\Http\FormRequest;

final class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app(CmsAccess::class)->allows($this->user(), 'media.upload');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $max = (int) config('website.uploads.max_file_size_kb', 10240);

        return [
            'file' => ['required', 'file', "max:{$max}"],
            'is_restricted' => ['boolean'],
            'translations' => ['nullable', 'array'],
            'translations.*.alt_text' => ['nullable', 'string', 'max:255'],
            'translations.*.caption' => ['nullable', 'string', 'max:500'],
        ];
    }
}
