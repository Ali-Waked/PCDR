<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PartnershipInquiry;
use App\Models\VolunteerInquiry;
use App\Services\CmsAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InquiryController extends Controller
{
    private function authorize(string $permission): void
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $permission), 403);
    }

    public function index(string $locale, Request $request): Response
    {
        $this->authorize('inquiries.view');

        $status = $request->string('status')->toString();

        $contact = ContactMessage::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20, ['id', 'reference', 'category', 'name', 'email', 'subject', 'status', 'created_at'])
            ->withQueryString();
        $volunteer = VolunteerInquiry::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20, ['id', 'reference', 'name', 'email', 'area_of_interest', 'status', 'created_at'])
            ->withQueryString();
        $partnership = PartnershipInquiry::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20, ['id', 'reference', 'organization', 'contact_name', 'email', 'interest', 'status', 'created_at'])
            ->withQueryString();

        return Inertia::render('Cms/Inquiries/Index', [
            'inquiries' => [
                'contact' => $contact->items(),
                'volunteer' => $volunteer->items(),
                'partnership' => $partnership->items(),
            ],
            'pagination' => [
                'contact' => $this->pagination($contact),
                'volunteer' => $this->pagination($volunteer),
                'partnership' => $this->pagination($partnership),
            ],
            'filters' => ['status' => $status],
            'statuses' => config('cms.inquiry_statuses', ['new', 'in_progress', 'resolved', 'closed']),
        ]);
    }

    public function status(string $locale, string $type, int $inquiry, Request $request): RedirectResponse
    {
        $this->authorize('inquiries.update');

        $statuses = (array) config('cms.inquiry_statuses', ['new', 'in_progress', 'resolved', 'closed']);
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', $statuses)],
        ]);

        $model = match ($type) {
            'contact' => ContactMessage::class,
            'volunteer' => VolunteerInquiry::class,
            'partnership' => PartnershipInquiry::class,
            default => abort(404),
        };

        /** @var Model $record */
        $record = $model::query()->findOrFail($inquiry);
        $record->update(['status' => $validated['status']]);

        return back()->with('flash', ['success' => __('website.cms.inquiry_status_updated')]);
    }

    /** @return array{currentPage: int, lastPage: int, total: int} */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }
}
