<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DocumentLink;
use App\Models\Media;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * Links restricted media to operational records without exposing file paths.
 */
final class OperationalDocumentService
{
    public function attach(
        string $resource,
        int $ownerId,
        Media $media,
        string $usage,
        ?int $uploaderId,
    ): DocumentLink {
        /** @var DocumentLink $link */
        $link = DocumentLink::query()->firstOrCreate([
            'resource' => $resource,
            'owner_id' => $ownerId,
            'media_id' => $media->getKey(),
            'usage' => $usage,
        ], [
            'uploaded_by' => $uploaderId,
            'created_at' => now(),
        ]);

        return $link;
    }

    /** @return Collection<int, DocumentLink> */
    public function forRecord(string $resource, int $ownerId): Collection
    {
        return DocumentLink::query()
            ->with('media')
            ->where('resource', $resource)
            ->where('owner_id', $ownerId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /** @return array<int, array<string, mixed>> */
    public function serializeLinks(string $resource, int $ownerId): array
    {
        return $this->forRecord($resource, $ownerId)
            ->map(fn (DocumentLink $link): array => [
                'id' => $link->getKey(),
                'usage' => $link->usage,
                'original_name' => $link->media?->original_name,
                'mime_type' => $link->media?->mime_type,
                'size_bytes' => $link->media?->size_bytes,
                'created_at' => $link->created_at?->toISOString(),
            ])
            ->all();
    }

    public function signedDownloadUrl(DocumentLink $link, string $locale): string
    {
        return URL::temporarySignedRoute(
            'public.operational-documents.download',
            now()->addMinutes(10),
            ['locale' => $locale, 'document' => $link->getKey()],
        );
    }
}
