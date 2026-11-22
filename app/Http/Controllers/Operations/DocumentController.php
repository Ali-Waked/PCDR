<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\DocumentLink;
use App\Models\Media;
use App\Services\CmsAccess;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentController extends Controller
{
    public function __construct(private readonly CmsAccess $access) {}

    public function download(string $locale, int $document): StreamedResponse
    {
        abort_unless(request()->hasValidSignature(), 403);

        $user = auth()->user();
        abort_unless($user !== null && $this->access->allows($user, 'documents.download'), 403);

        /** @var DocumentLink $link */
        $link = DocumentLink::query()->with('media')->findOrFail($document);

        /** @var Media|null $media */
        $media = $link->media;
        abort_unless($media !== null && $media->is_restricted, 404);

        return Storage::disk($media->disk)->response(
            $media->path,
            $this->safeFileName($media->original_name, $media->extension),
            ['Content-Type' => $media->mime_type, 'Cache-Control' => 'private, no-store'],
        );
    }

    private function safeFileName(string $name, string $extension): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $clean = preg_replace('/[^\p{L}\p{N}\-_ ]/u', '', $base) ?: 'document';

        return mb_substr($clean, 0, 100).'.'.$extension;
    }
}
