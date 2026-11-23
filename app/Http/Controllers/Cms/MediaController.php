<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Actions\DeleteMedia;
use App\Actions\UploadMedia;
use App\Exceptions\MediaInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadMediaRequest;
use App\Models\Media;
use App\Services\CmsAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

final class MediaController extends Controller
{
    private function authorize(string $permission): void
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $permission), 403);
    }

    public function index(string $locale, Request $request): Response
    {
        $this->authorize('media.view');

        $type = $request->string('type')->toString();
        $search = $request->string('q')->toString();

        $media = Media::query()
            ->with('translations')
            ->when($type === 'image', fn ($q) => $q->images())
            ->when($type === 'document', fn ($q) => $q->documents())
            ->when($search !== '', fn ($q) => $q->where('original_name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(24);

        return Inertia::render('Cms/Media/Index', [
            'items' => $media->through(fn (Media $item) => [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'name' => $item->original_name,
                'type' => $item->type->value,
                'mime' => $item->mime_type,
                'size' => $item->humanSize(),
                'restricted' => $item->is_restricted,
                'url' => $item->is_restricted ? null : $item->url(),
                'width' => $item->width,
                'height' => $item->height,
                'alt' => $item->t('alt_text', $locale),
                'caption' => $item->t('caption', $locale),
                'translations' => collect(config('website.locales', ['ar', 'en']))
                    ->mapWithKeys(fn (string $code) => [$code => [
                        'alt_text' => $item->t('alt_text', $code) ?: '',
                        'caption' => $item->t('caption', $code) ?: '',
                    ]])
                    ->all(),
            ])->items(),
            'pagination' => [
                'currentPage' => $media->currentPage(),
                'lastPage' => $media->lastPage(),
                'total' => $media->total(),
            ],
            'filters' => ['type' => $type, 'q' => $search],
        ]);
    }

    public function store(UploadMediaRequest $request, UploadMedia $action): RedirectResponse
    {
        try {
            $media = $action->handle(
                $request->file('file'),
                (bool) $request->boolean('is_restricted'),
                $request->user(),
                $request->input('translations', []),
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->with('flash', ['error' => $exception->getMessage()]);
        }

        return back()->with('flash', [
            'success' => __('website.cms.media_uploaded', ['name' => $media->original_name]),
        ]);
    }

    public function update(string $locale, Request $request, int $media): RedirectResponse
    {
        $this->authorize('media.update');

        $validated = $request->validate([
            'translations' => ['required', 'array'],
            'translations.*.alt_text' => ['nullable', 'string', 'max:255'],
            'translations.*.caption' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var Media $record */
        $record = Media::query()->findOrFail($media);
        foreach ($validated['translations'] as $code => $values) {
            if (! in_array($code, config('website.locales', ['ar', 'en']), true)) {
                continue;
            }
            $record->translations()->updateOrCreate(['locale' => $code], $values);
        }
        $record->unsetRelation('translations');

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }

    public function destroy(string $locale, int $media): RedirectResponse
    {
        $this->authorize('media.delete');

        /** @var Media $record */
        $record = Media::query()->findOrFail($media);

        try {
            app(DeleteMedia::class)->handle($record);
        } catch (MediaInUseException) {
            return back()->with('flash', ['error' => __('website.cms.media_in_use')]);
        }

        return back()->with('flash', ['success' => __('website.cms.deleted')]);
    }

    public function signed(string $locale, int $media): JsonResponse
    {
        $this->authorize('media.view');

        /** @var Media $record */
        $record = Media::query()->findOrFail($media);

        return response()->json([
            'url' => URL::temporarySignedRoute(
                'public.media.download', now()->addMinutes(30), [
                    'locale' => $locale,
                    'uuid' => $record->uuid,
                ],
            ),
        ]);
    }
}
