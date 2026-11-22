<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\AssignDocumentReviewRequest;
use App\Http\Requests\Phase5\StoreDocumentReviewRequest;
use App\Http\Requests\Phase5\TransitionDocumentReviewRequest;
use App\Models\Phase5\DocumentReview;
use App\Models\User;
use App\Services\Phase5\DocumentReviewService;
use App\Services\Phase5\Phase5Authorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class DocumentReviewController extends Controller
{
    public function __construct(
        private readonly DocumentReviewService $reviews,
        private readonly Phase5Authorization $authorization,
    ) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();
        $canDownload = $this->authorization->allows($user, 'documents.download');

        $reviews = $this->reviews->paginateReviews($user)->toArray();
        $reviews['data'] = array_map(function (array $review) use ($canDownload): array {
            if (! $canDownload) {
                $review['downloadUrl'] = null;
            }

            return $review;
        }, $reviews['data'] ?? []);

        return Inertia::render('Phase5/DocumentReviews/Index', [
            'reviews' => $reviews,
            'statuses' => (array) config('phase5.document_review.statuses', []),
            'transitions' => (array) config('phase5.document_review.transitions', []),
            'permissions' => [
                'review' => $this->authorization->allows($user, 'documents.review'),
                'submit' => $this->authorization->allows($user, 'documents.submit_review'),
                'download' => $canDownload,
            ],
            'notice' => __('phase5.approval_notice'),
        ]);
    }

    public function store(StoreDocumentReviewRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->reviews->create($user, $request->validatedData());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['media_id' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', __('phase5.document_submitted'));
    }

    public function show(string $locale, DocumentReview $review): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $payload = $this->reviews->showReview($user, $review, $locale);

        if (! $this->authorization->allows($user, 'documents.download')) {
            $payload['review']['downloadUrl'] = null;
        }

        return response()->json($payload);
    }

    public function history(string $locale, DocumentReview $review): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(['history' => $this->reviews->history($user, $review)]);
    }

    public function assign(string $locale, DocumentReview $review, AssignDocumentReviewRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->reviews->assign($user, $review, $request->validatedData());

        return back()->with('success', __('phase5.document_assigned'));
    }

    public function transition(string $locale, DocumentReview $review, TransitionDocumentReviewRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->reviews->transition($user, $review, $request->validatedData());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['media_id' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', __('phase5.transitioned'));
    }
}
