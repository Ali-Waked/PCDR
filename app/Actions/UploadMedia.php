<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MediaType;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class UploadMedia
{
    public function handle(UploadedFile $file, bool $restricted, ?User $uploader, array $translations = []): Media
    {
        $allowed = $restricted
            ? config('website.uploads.allowed_document_mimes', [])
            : config('website.uploads.allowed_image_mimes', []);

        $detected = strtolower((string) $file->getMimeType());
        if (! in_array($detected, $allowed, true)) {
            throw new \InvalidArgumentException('The file type is not allowed.');
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        if (! preg_match('/^[a-z0-9]{1,8}$/', $extension)) {
            throw new \InvalidArgumentException('The file extension is not allowed.');
        }

        $maxKb = (int) config('website.uploads.max_file_size_kb', 10240);
        if ($file->getSize() > $maxKb * 1024) {
            throw new \InvalidArgumentException("The file may not exceed {$maxKb} KB.");
        }

        $disk = $restricted
            ? (string) config('website.uploads.private_disk')
            : (string) config('website.uploads.public_disk');

        $uuid = (string) Str::ulid();
        $path = 'media/'.now()->format('Y/m')."/{$uuid}.{$extension}";
        $dimensions = @getimagesize($file->getRealPath());

        Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path), $restricted ? 'private' : 'public');

        $media = DB::transaction(function () use ($uuid, $disk, $path, $detected, $extension, $file, $restricted, $uploader, $dimensions, $translations): Media {
            $media = Media::create([
                'uuid' => $uuid,
                'disk' => $disk,
                'path' => $path,
                'type' => $restricted ? MediaType::Document : MediaType::Image,
                'mime_type' => $detected,
                'extension' => $extension,
                'size_bytes' => (int) $file->getSize(),
                'width' => is_array($dimensions) ? ($dimensions[0] ?? null) : null,
                'height' => is_array($dimensions) ? ($dimensions[1] ?? null) : null,
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'original_name' => $file->getClientOriginalName(),
                'is_restricted' => $restricted,
                'uploaded_by' => $uploader?->id,
            ]);

            foreach ($translations as $locale => $values) {
                if (in_array($locale, config('website.locales', ['ar', 'en']), true)) {
                    $media->translations()->create([
                        'locale' => $locale,
                        'alt_text' => $values['alt_text'] ?? null,
                        'caption' => $values['caption'] ?? null,
                    ]);
                }
            }

            return $media;
        });

        return $media->load('translations');
    }
}
