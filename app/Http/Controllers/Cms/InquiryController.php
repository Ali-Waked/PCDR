<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PartnershipInquiry;
use App\Models\VolunteerInquiry;
use App\Services\CmsAccess;
use Inertia\Inertia;
use Inertia\Response;

final class InquiryController extends Controller
{
    public function index(string $locale): Response
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), 'inquiries.view'), 403);

        return Inertia::render('Cms/Inquiries/Index', [
            'inquiries' => [
                'contact' => ContactMessage::query()->latest()->limit(30)
                    ->get(['id', 'reference', 'category', 'name', 'email', 'subject', 'status', 'created_at'])->all(),
                'volunteer' => VolunteerInquiry::query()->latest()->limit(30)
                    ->get(['id', 'reference', 'name', 'email', 'area_of_interest', 'status', 'created_at'])->all(),
                'partnership' => PartnershipInquiry::query()->latest()->limit(30)
                    ->get(['id', 'reference', 'organization', 'contact_name', 'email', 'interest', 'status', 'created_at'])->all(),
            ],
        ]);
    }
}
