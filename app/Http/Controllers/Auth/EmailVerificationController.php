<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EmailVerificationController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function notice(): Response
    {
        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(string $locale, EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', ['locale' => $request->route('locale')], false));
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            $this->audit->record('auth.email_verified', 'users', (string) $request->user()->id);
        }

        return redirect()->intended(route('dashboard', ['locale' => $request->route('locale')], false))
            ->with('success', __('auth.verified'));
    }

    public function resend(string $locale, Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard', ['locale' => $request->route('locale')]);
        }

        $request->user()->sendEmailVerificationNotification();
        $this->audit->record('auth.verification_resent', 'users', (string) $request->user()->id);

        return back()->with('success', __('auth.verification_sent'));
    }
}
