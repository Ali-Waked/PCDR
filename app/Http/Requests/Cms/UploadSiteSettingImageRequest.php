<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use App\Services\CmsAccess;
use App\Support\SiteSettingsCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UploadSiteSettingImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(CmsAccess::class)->allows($user, 'settings.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $max = (int) config('website.uploads.max_file_size_kb', 10240);
        $mimes = implode(',', (array) config('website.uploads.allowed_image_mimes', ['image/jpeg', 'image/png', 'image/webp']));

        return [
            'key' => ['required', 'string', Rule::in(SiteSettingsCatalog::imageKeys())],
            'file' => ['required', 'file', "mimetypes:{$mimes}", "max:{$max}"],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'key.in' => __('website.cms.setting_image_key_invalid'),
            'file.mimetypes' => __('website.cms.setting_image_type_invalid'),
            'file.max' => __('website.cms.setting_image_too_large'),
        ];
    }
}
