<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\StoreInquiry;
use App\Http\Requests\ContactRequest;
use App\Http\Requests\VolunteerRequest;
use Illuminate\Http\RedirectResponse;

final class InquiryController extends PublicController
{
    public function contact(ContactRequest $request, StoreInquiry $action): RedirectResponse
    {
        $record = $action->handle('contact', $request->validated(), $request);

        return back()->with('flash', [
            'success' => __('website.forms.received', ['reference' => $record->reference]),
        ]);
    }

    public function volunteer(VolunteerRequest $request, StoreInquiry $action): RedirectResponse
    {
        $record = $action->handle('volunteer', $request->validated(), $request);

        return back()->with('flash', [
            'success' => __('website.forms.received', ['reference' => $record->reference]),
        ]);
    }
}
