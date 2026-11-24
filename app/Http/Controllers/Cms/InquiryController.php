<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\EmailDeliveryLog;
use App\Models\EmailTemplate;
use App\Models\InquiryNote;
use App\Models\InquiryReply;
use App\Models\User;
use App\Models\VolunteerInquiry;
use App\Services\AuditService;
use App\Services\CmsAccess;
use App\Services\InquiryMailService;
use App\Services\TemplateRenderer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InquiryController extends Controller
{
    private const TYPES = ['contact', 'volunteer'];

    public function __construct(
        private AuditService $audit,
        private InquiryMailService $mail,
        private TemplateRenderer $renderer,
    ) {}

    private function authorize(string $permission): void
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $permission), 403);
    }

    private function has(string $permission): bool
    {
        $user = auth()->user();

        return $user !== null && app(CmsAccess::class)->allows($user, $permission);
    }

    public function index(string $locale, Request $request): Response
    {
        $this->authorize('inquiries.view');

        $status = $this->normalizeStatus($request->string('status')->toString());
        $search = mb_strcut(trim($request->string('search')->toString()), 0, 120);
        $assigned = $request->string('assigned')->toString();
        $restrictToAssigned = ! $this->has('inquiries.view_all') && $this->has('inquiries.view_assigned');

        /** @var array<string, int> $counts */
        $counts = array_fill_keys(array_merge(['total', 'unassigned'], $this->statuses()), 0);
        $lists = [];
        $pagination = [];

        foreach (self::TYPES as $type) {
            $baseQuery = $this->modelClass($type)::query();

            if ($restrictToAssigned) {
                $baseQuery->where('assigned_to', auth()->id());
            }

            $counts = $this->accumulateCounts($counts, $baseQuery);
            $query = clone $baseQuery;

            if ($status !== '') {
                $query->whereIn('status', $this->statusValuesFor($status));
            }

            if ($search !== '') {
                $query->where(function ($query) use ($type, $search): void {
                    foreach ($this->searchColumns($type) as $column) {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                    $query->orWhere('reference', 'like', '%'.$search.'%');
                });
            }

            if ($assigned === 'mine') {
                $query->where('assigned_to', auth()->id());
            } elseif ($assigned === 'unassigned') {
                $query->whereNull('assigned_to');
            }

            $paginator = $query->with('assignee:id,name')->latest()
                ->paginate(20, ['*'], $type.'Page')
                ->withQueryString();
            $lists[$type] = $paginator->map(fn (Model $record) => $this->summary($type, $record))->all();
            $pagination[$type] = $this->pagination($paginator);
        }

        return Inertia::render('Cms/Inquiries/Index', [
            'inquiries' => $lists,
            'pagination' => $pagination,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'assigned' => $assigned,
            ],
            'statuses' => $this->statuses(),
            'canonicalStatuses' => (array) config('cms.inquiry_statuses', ['new', 'in_progress', 'resolved', 'closed']),
            'counts' => $counts,
            'can' => [
                'viewAll' => $this->has('inquiries.view_all'),
                'update' => $this->has('inquiries.update'),
                'assign' => $this->has('inquiries.assign'),
                'reply' => $this->has('inquiries.reply'),
                'note' => $this->has('inquiries.add_internal_note'),
            ],
        ]);
    }

    public function show(string $locale, string $type, int $inquiry): Response
    {
        $this->authorize('inquiries.view');
        $record = $this->find($type, $inquiry);
        $this->ensureVisible($record);

        $notes = InquiryNote::query()->where('inquiry_type', $type)->where('inquiry_id', $inquiry)
            ->with('author:id,name')->latest()->get()
            ->map(fn (InquiryNote $note) => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->author?->name,
                'createdAt' => $note->created_at?->toISOString(),
            ])->all();
        $replies = InquiryReply::query()->where('inquiry_type', $type)->where('inquiry_id', $inquiry)
            ->with('sender:id,name')->latest()->get()
            ->map(fn (InquiryReply $reply) => [
                'id' => $reply->id,
                'subject' => $reply->subject,
                'body' => $reply->body,
                'status' => $reply->status,
                'sender' => $reply->sender?->name,
                'sentAt' => $reply->sent_at?->toISOString(),
                'error' => $reply->error,
            ])->all();
        $deliveryLog = EmailDeliveryLog::query()->where('inquiry_type', $type)->where('inquiry_id', $inquiry)
            ->latest()->get()
            ->map(fn (EmailDeliveryLog $log) => [
                'id' => $log->id,
                'purpose' => $log->purpose,
                'status' => $log->status,
                'locale' => $log->locale,
                'sentAt' => $log->sent_at?->toISOString(),
                'failedAt' => $log->failed_at?->toISOString(),
            ])->all();
        $templates = EmailTemplate::query()->where('is_active', true)->with('translations')
            ->orderBy('sort_order')->get()
            ->map(fn (EmailTemplate $template) => [
                'id' => $template->id,
                'key' => $template->key,
                'name' => $template->translationFor($locale)?->name ?: $template->key,
                'subject' => $template->translationFor($locale)?->subject ?: '',
                'body' => $template->translationFor($locale)?->body ?: '',
            ])->all();

        return Inertia::render('Cms/Inquiries/Show', [
            'type' => $type,
            'inquiry' => $this->details($type, $record),
            'notes' => $notes,
            'replies' => $replies,
            'deliveryLog' => $deliveryLog,
            'templates' => $templates,
            'variables' => $this->renderer->availableVariables(),
            'statuses' => $this->statuses(),
            'can' => [
                'update' => $this->has('inquiries.update'),
                'assign' => $this->has('inquiries.assign'),
                'note' => $this->has('inquiries.add_internal_note'),
                'reply' => $this->has('inquiries.reply'),
            ],
        ]);
    }

    public function status(string $locale, string $type, int $inquiry, Request $request): RedirectResponse
    {
        $this->authorize('inquiries.update');
        $record = $this->find($type, $inquiry);
        $this->ensureVisible($record);
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', $this->statuses())],
        ]);
        $before = ['status' => $record->status];
        $record->update(['status' => $this->normalizeStatus($validated['status'])]);
        $this->audit->record('inquiries.status_updated', $type, (string) $inquiry, $before, ['status' => $record->status]);

        return back()->with('flash', ['success' => __('website.cms.inquiry_status_updated')]);
    }

    public function assign(string $locale, string $type, int $inquiry, Request $request): RedirectResponse
    {
        $this->authorize('inquiries.assign');
        $record = $this->find($type, $inquiry);
        $this->ensureVisible($record);
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $before = ['assigned_to' => $record->assigned_to];
        $record->update(['assigned_to' => $validated['assigned_to'] ?? null]);
        $this->audit->record('inquiries.assigned', $type, (string) $inquiry, $before, ['assigned_to' => $record->assigned_to]);

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }

    public function note(string $locale, string $type, int $inquiry, Request $request): RedirectResponse
    {
        $this->authorize('inquiries.add_internal_note');
        $record = $this->find($type, $inquiry);
        $this->ensureVisible($record);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);
        $note = InquiryNote::create([
            'inquiry_type' => $type,
            'inquiry_id' => $record->id,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);
        $this->audit->record('inquiries.note_added', 'inquiry_notes', (string) $note->id, extra: [
            'inquiry_type' => $type,
            'inquiry_id' => $record->id,
        ]);

        return back()->with('flash', ['success' => __('website.cms.saved')]);
    }

    public function reply(string $locale, string $type, int $inquiry, Request $request): RedirectResponse
    {
        $this->authorize('inquiries.reply');
        $record = $this->find($type, $inquiry);
        $this->ensureVisible($record);
        $validated = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:email_templates,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'locale' => ['required', 'string', 'in:'.implode(',', config('website.locales', ['ar', 'en']))],
        ]);
        $reply = $this->mail->composeReply(
            $record,
            $type,
            $validated['subject'],
            $validated['message'],
            $validated['locale'],
            $validated['template_id'] ?? null,
            auth()->id(),
        );
        $log = $this->mail->sendReply($reply);
        $this->audit->record('inquiries.replied', $type, (string) $inquiry, extra: [
            'reply_id' => $reply->id,
            'delivery_status' => $log->status,
            'template_key' => $log->template_key,
        ]);

        $flash = $log->status === 'sent'
            ? ['success' => __('website.cms.reply_sent')]
            : ['error' => __('website.cms.reply_delivery_failed')];

        return back()->with('flash', $flash);
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function accumulateCounts(array $counts, Builder $query): array
    {
        $counts['total'] += (int) (clone $query)->count();
        $counts['unassigned'] += (int) (clone $query)->whereNull('assigned_to')->count();

        $grouped = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        foreach ($grouped as $status => $count) {
            $canonical = $this->normalizeStatus((string) $status);
            $counts[$canonical] = ($counts[$canonical] ?? 0) + (int) $count;
        }

        return $counts;
    }

    /** @return list<string> */
    private function statuses(): array
    {
        $statuses = (array) config('cms.inquiry_statuses', ['new', 'in_progress', 'resolved', 'closed']);
        $aliases = (array) config('cms.inquiry_status_aliases', []);

        return array_values(array_unique(array_merge($statuses, array_keys($aliases))));
    }

    private function normalizeStatus(string $status): string
    {
        $aliases = (array) config('cms.inquiry_status_aliases', []);

        return $aliases[$status] ?? $status;
    }

    /** @return list<string> */
    private function statusValuesFor(string $status): array
    {
        $values = [$status];

        foreach ((array) config('cms.inquiry_status_aliases', []) as $alias => $canonical) {
            if ($canonical === $status) {
                $values[] = (string) $alias;
            }
        }

        return array_values(array_unique($values));
    }

    private function modelClass(string $type): string
    {
        return match ($type) {
            'contact' => ContactMessage::class,
            'volunteer' => VolunteerInquiry::class,
            default => abort(404),
        };
    }

    private function find(string $type, int $id): Model
    {
        $class = $this->modelClass($type);
        /** @var Model $record */
        $record = $class::query()->findOrFail($id);

        return $record;
    }

    private function ensureVisible(Model $record): void
    {
        if (! $this->has('inquiries.view_all') && $this->has('inquiries.view_assigned')
            && (int) $record->getAttribute('assigned_to') !== (int) auth()->id()) {
            abort(403);
        }
    }

    /** @return list<string> */
    private function searchColumns(string $type): array
    {
        return match ($type) {
            'contact' => ['name', 'email', 'subject', 'category'],
            'volunteer' => ['name', 'email', 'area_of_interest'],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function summary(string $type, Model $record): array
    {
        $subject = $record->getAttribute('subject')
            ?: $record->getAttribute('area_of_interest')
            ?: $record->getAttribute('interest')
            ?: $record->getAttribute('organization')
            ?: $record->getAttribute('name');

        return [
            'id' => $record->getAttribute('id'),
            'type' => $type,
            'reference' => $record->getAttribute('reference'),
            'label' => $subject,
            'subject' => $subject,
            'senderName' => $record->getAttribute('contact_name')
                ?: $record->getAttribute('name')
                ?: $record->getAttribute('organization'),
            'context' => $record->getAttribute('category')
                ?: $record->getAttribute('availability')
                ?: $record->getAttribute('organization'),
            'email' => $record->getAttribute('email'),
            'status' => $record->getAttribute('status'),
            'assignedTo' => $record->relationLoaded('assignee') ? $record->getRelation('assignee')?->name : null,
            'createdAt' => $record->getAttribute('created_at')?->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    private function details(string $type, Model $record): array
    {
        $fields = match ($type) {
            'contact' => [
                'name' => $record->getAttribute('name'),
                'email' => $record->getAttribute('email'),
                'subject' => $record->getAttribute('subject'),
                'message' => $record->getAttribute('message'),
                'category' => $record->getAttribute('category'),
            ],
            'volunteer' => [
                'name' => $record->getAttribute('name'),
                'email' => $record->getAttribute('email'),
                'phone' => $record->getAttribute('phone'),
                'areaOfInterest' => $record->getAttribute('area_of_interest'),
                'availability' => $record->getAttribute('availability'),
                'message' => $record->getAttribute('message'),
            ],
            default => [],
        };

        return array_merge([
            'id' => $record->getAttribute('id'),
            'type' => $type,
            'reference' => $record->getAttribute('reference'),
            'status' => $record->getAttribute('status'),
            'locale' => $record->getAttribute('locale'),
            'assignedTo' => $record->getAttribute('assigned_to'),
            'assignedToName' => $record->relationLoaded('assignee') ? $record->getRelation('assignee')?->name : null,
            'createdAt' => $record->getAttribute('created_at')?->toISOString(),
        ], $fields, [
            'users' => User::query()->orderBy('name')->limit(200)->get(['id', 'name'])->all(),
        ]);
    }

    /** @return array{currentPage: int, lastPage: int, perPage: int, total: int, from: int|null, to: int|null, pageName: string} */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'pageName' => $paginator->getPageName(),
        ];
    }
}
