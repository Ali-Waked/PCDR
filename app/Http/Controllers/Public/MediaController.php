<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Media;
use App\Services\CmsAccess;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MediaController extends PublicController
{
    public function download(string $locale, string $uuid): StreamedResponse
    {
        abort_unless(request()->hasValidSignature(), 403);

        /** @var Media $media */
        $media = Media::query()->where('uuid', $uuid)->firstOrFail();

        if ($media->is_restricted) {
            abort_unless(auth()->check(), 403);
            abort_unless(app(CmsAccess::class)->allows(auth()->user(), 'media.view'), 403);
        }

        return Storage::disk($media->disk)->response(
            $media->path,
            $this->safeFileName($media->original_name, $media->extension),
            ['Content-Type' => $media->mime_type, 'Cache-Control' => 'private, max-age=300'],
        );
    }

    private function safeFileName(string $name, string $extension): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $clean = preg_replace('/[^\p{L}\p{N}\-_ ]/u', '', $base) ?: 'file';

        return mb_substr($clean, 0, 100).'.'.$extension;
    }
}
