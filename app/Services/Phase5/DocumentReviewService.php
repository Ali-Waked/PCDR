<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Events\Phase5Event;
use App\Models\DocumentLink;
use App\Models\DomainTransition;
use App\Models\Media;
use App\Models\Phase5\DocumentReview;
use App\Models\Phase5\DocumentReviewEvent;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OperationalDocumentService;
use App\Support\Phase5Support;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Technical document review service.
 *
 * Private media is referenced only through existing document_links rows;
 * signed URLs reuse the operational download route and private media paths are
 * never serialized. No approval outcome exists in this domain.
 */
final class DocumentReviewService
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly Phase5Authorization $authorization,
        private readonly AuditService $audit,
        private readonly OperationalDocumentService $documents,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): DocumentReview
    {
        $media = $this->restrictedMedia((int) $data['media_id']);
        $this->assertSubject($user, $data['subject_type'] ?? null, $data['subject_id'] ?? null);

        $review = null;

        DB::transaction(function () use (&$review, $user, $data, $media): void {
            /** @var DocumentReview $review */
            $review = DocumentReview::create([
                'reference' => $this->reference('DOC'),
                'media_id' => $media->getKey(),
                'subject_type' => $data['subject_type'] ?? null,
                'subject_id' => isset($data['subject_id']) ? (int) $data['subject_id'] : null,
                'title' => Phase5Support::clean((string) $data['title'], 255),
                'description' => isset($data['description'])
                    ? Phase5Support::clean((string) $data['description'], (int) config('phase5.document_review.max_review_chars', 5000))
                    : null,
                'status' => 'submitted',
                'version' => 1,
                'submitter_id' => $user->id,
                'due_at' => $data['due_at'] ?? null,
            ]);

            $link = $this->resolveLink($data['document_link_id'] ?? null, $media, $user, (int) $review->getKey());
            $review->forceFill(['document_link_id' => $link?->getKey()])->save();

            $this->appendEvent($review, 'submitted', null, 'submitted', $user, null, 1);

            DomainTransition::query()->create([
                'resource' => 'document_reviews',
                'owner_id' => $review->getKey(),
                'status_field' => 'status',
                'from_status' => null,
                'to_status' => 'submitted',
                'actor_id' => $user->id,
                'reason' => null,
                'evidence_media_id' => null,
                'created_at' => now(),
            ]);

            $this->audit->record('document.submitted', 'document_review', (string) $review->getKey(), null, [
                'reference' => $review->reference,
                'status' => 'submitted',
                'version' => 1,
                'media_id' => (int) $media->getKey(),
            ]);

            event(new Phase5Event('document.submitted', [
                'reference' => $review->reference,
                'status' => 'submitted',
                'version' => 1,
            ], 'document_review', (int) $review->getKey(), (int) $user->id));
        });

        return $review->refresh();
    }

    public function paginateReviews(User $user): LengthAwarePaginator
    {
        $query = DocumentReview::query()
            ->with(['media:id,is_restricted', 'documentLink:id,media_id'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! $user->isPlatformOwner()) {
            $query->where(function ($query) use ($user): void {
                $query->where('reviewer_id', $user->id)
                    ->orWhere('submitter_id', $user->id)
                    ->orWhereNull('subject_type');
            });
        }

        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();
        $visible = $paginator->getCollection()
            ->map(fn (DocumentReview $review): ?array => $this->canView($user, $review)
                ? $this->serializeReview($review, $this->downloadUrl($review, app()->getLocale()))
                : null)
            ->filter(static fn (?array $review): bool => $review !== null)
            ->values();

        $paginator->setCollection($visible);

        return $paginator;
    }

    public function showReview(User $user, DocumentReview $review, string $locale): array
    {
        $this->authorization->assertCanViewResource($user, 'document_review', (int) $review->getKey());

        $this->audit->record('document.viewed', 'document_review', (string) $review->getKey(), null, [
            'reference' => $review->reference,
            'version' => (int) $review->version,
        ]);

        return [
            'review' => $this->serializeReview($review, $this->downloadUrl($review, $locale)),
            'history' => $this->history($user, $review),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function history(User $user, DocumentReview $review): array
    {
        $this->authorization->assertCanViewResource($user, 'document_review', (int) $review->getKey());

        $this->audit->record('document.history_viewed', 'document_review', (string) $review->getKey(), null, [
            'reference' => $review->reference,
        ]);

        return $review->events()
            ->orderByDesc('revision')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (DocumentReviewEvent $event): array => [
                'id' => $event->getKey(),
                'action' => $event->action,
                'fromStatus' => $event->from_status,
                'toStatus' => $event->to_status,
                'note' => $event->note,
                'revision' => (int) $event->revision,
                'createdAt' => $event->created_at?->toISOString(),
            ])->all();
    }

    /** @param array<string, mixed> $data */
    public function assign(User $user, DocumentReview $review, array $data): DocumentReview
    {
        $this->authorization->assertCanViewResource($user, 'document_review', (int) $review->getKey());
        $reviewer = User::query()->findOrFail((int) $data['reviewer_id']);

        if (! $this->authorization->allows($reviewer, 'documents.review')) {
            throw ValidationException::withMessages([
                'reviewer_id' => 'The selected user cannot review documents.',
            ]);
        }

        DB::transaction(function () use ($user, $review, $reviewer): void {
            $previous = $review->reviewer_id;
            $review->forceFill(['reviewer_id' => $reviewer->getKey()])->save();

            $this->appendEvent($review, 'assigned', (string) $review->status, (string) $review->status, $user, null, (int) $review->version);

            $this->audit->record('document.assigned', 'document_review', (string) $review->getKey(), [
                'reviewer_id' => $previous,
            ], [
                'reference' => $review->reference,
                'reviewer_id' => (int) $reviewer->getKey(),
            ]);
        });

        return $review->refresh();
    }

    /** @param array<string, mixed> $data */
    public function transition(User $user, DocumentReview $review, array $data): DocumentReview
    {
        $this->authorization->assertCanViewResource($user, 'document_review', (int) $review->getKey());

        $from = (string) $review->status;
        $to = (string) $data['to_status'];
        $this->authorization->assertTransition($user, 'document_review', $from, $to);

        /** @var list<string> $approvalOutcomes */
        $approvalOutcomes = (array) config('phase5.document_review.approval_outcomes', []);
        if (in_array($to, $approvalOutcomes, true)) {
            throw new InvalidArgumentException('Institutional approval outcomes are unavailable.');
        }

        $isResubmission = $to === 'resubmitted';
        $media = null;
        $link = null;

        if ($isResubmission) {
            $media = $this->restrictedMedia((int) ($data['media_id'] ?? 0));
        }

        DB::transaction(function () use ($user, $review, $data, $from, $to, $isResubmission, $media, &$link): void {
            $attributes = ['status' => $to];

            if ($isResubmission && $media !== null) {
                $attributes['media_id'] = (int) $media->getKey();
                $attributes['version'] = (int) $review->version + 1;
                $link = $this->documents->attach(
                    'document_review',
                    (int) $review->getKey(),
                    $media,
                    'review',
                    (int) $user->id,
                );
                $attributes['document_link_id'] = (int) $link->getKey();
            }

            if ($to === 'reviewed') {
                $attributes['reviewed_at'] = now();
            }

            $review->forceFill($attributes)->save();

            $note = isset($data['note'])
                ? Phase5Support::clean((string) $data['note'], (int) config('phase5.document_review.max_review_chars', 5000))
                : null;

            $this->appendEvent(
                $review,
                $isResubmission ? 'resubmitted' : 'status_changed',
                $from,
                $to,
                $user,
                $note,
                (int) $review->version,
            );

            DomainTransition::query()->create([
                'resource' => 'document_reviews',
                'owner_id' => $review->getKey(),
                'status_field' => 'status',
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => $user->id,
                'reason' => null,
                'evidence_media_id' => null,
                'created_at' => now(),
            ]);

            $this->audit->record($isResubmission ? 'document.resubmitted' : 'document.status_changed', 'document_review', (string) $review->getKey(), [
                'status' => $from,
                'version' => (int) $review->version - ($isResubmission ? 1 : 0),
            ], [
                'reference' => $review->reference,
                'status' => $to,
                'version' => (int) $review->version,
            ]);

            event(new Phase5Event($isResubmission ? 'document.submitted' : 'document.status_changed', [
                'reference' => $review->reference,
                'from' => $from,
                'to' => $to,
                'version' => (int) $review->version,
            ], 'document_review', (int) $review->getKey(), (int) $user->id));
        });

        return $review->refresh();
    }

    /** Signed URLs exist only for an existing restricted document link. */
    public function downloadUrl(DocumentReview $review, string $locale): ?string
    {
        if ($review->document_link_id === null) {
            return null;
        }

        $link = DocumentLink::query()
            ->whereKey((int) $review->document_link_id)
            ->where('media_id', (int) $review->media_id)
            ->with('media')
            ->first();

        if ($link === null || $link->media === null || ! $link->media->is_restricted) {
            return null;
        }

        return $this->documents->signedDownloadUrl($link, $locale);
    }

    private function reference(string $prefix): string
    {
        // Cast before uppercasing: Laravel 13 returns a Ulid value object.
        return strtoupper($prefix).'-'.strtoupper((string) Str::ulid());
    }

    private function canView(User $user, DocumentReview $review): bool
    {
        if ($user->isPlatformOwner() && $this->authorization->allows($user, 'documents.review')) {
            return true;
        }

        if ((int) $review->reviewer_id === (int) $user->id || (int) $review->submitter_id === (int) $user->id) {
            return true;
        }

        if ($review->subject_type === null || $review->subject_id === null) {
            return true;
        }

        return $this->authorization->canViewResource($user, (string) $review->subject_type, (int) $review->subject_id);
    }

    private function restrictedMedia(int $mediaId): Media
    {
        /** @var Media|null $media */
        $media = Media::query()->whereKey($mediaId)->where('is_restricted', true)->first();

        if ($media === null) {
            throw new InvalidArgumentException('Only restricted documents may be submitted for review.');
        }

        return $media;
    }

    private function resolveLink(mixed $linkId, Media $media, User $user, int $reviewId): ?DocumentLink
    {
        if ($linkId === null || $linkId === '') {
            return $this->documents->attach('document_review', $reviewId, $media, 'review', (int) $user->id);
        }

        /** @var DocumentLink|null $link */
        $link = DocumentLink::query()
            ->whereKey((int) $linkId)
            ->where('media_id', (int) $media->getKey())
            ->with('media')
            ->first();

        if ($link === null || $link->media === null || ! $link->media->is_restricted) {
            throw new InvalidArgumentException('The selected document link is not valid for this restricted media.');
        }

        return $link;
    }

    private function assertSubject(User $user, mixed $subjectType, mixed $subjectId): void
    {
        if ($subjectType === null && $subjectId === null) {
            return;
        }

        if (! is_string($subjectType) || ! is_numeric($subjectId)) {
            throw new InvalidArgumentException('A document review subject requires both type and identifier.');
        }

        $allowed = array_keys((array) config('phase5.collaboration.resource_permissions', []));

        if (! in_array($subjectType, $allowed, true) || $subjectType === 'operational_record') {
            throw new InvalidArgumentException('Unsupported document review subject.');
        }

        $this->authorization->assertCanViewResource($user, $subjectType, (int) $subjectId);
    }

    private function appendEvent(
        DocumentReview $review,
        string $action,
        ?string $from,
        string $to,
        User $user,
        ?string $note,
        int $revision,
    ): void {
        DocumentReviewEvent::query()->create([
            'review_id' => $review->getKey(),
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $user->id,
            'note' => $note,
            'revision' => $revision,
            'created_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function serializeReview(DocumentReview $review, ?string $downloadUrl): array
    {
        return [
            'reference' => $review->reference,
            'title' => $review->title,
            'description' => $review->description,
            'status' => $review->status,
            'version' => (int) $review->version,
            'subjectType' => $review->subject_type,
            'reviewerId' => $review->reviewer_id,
            'dueAt' => $review->due_at?->toISOString(),
            'reviewedAt' => $review->reviewed_at?->toISOString(),
            'createdAt' => $review->created_at?->toISOString(),
            'updatedAt' => $review->updated_at?->toISOString(),
            'downloadUrl' => $downloadUrl,
        ];
    }
}
