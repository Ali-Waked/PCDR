<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\StoreReferralRequest;
use App\Http\Requests\Phase5\TransitionReferralRequest;
use App\Models\Phase5\Referral;
use App\Models\User;
use App\Services\Phase5\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referrals) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        return Inertia::render('Phase5/Referrals/Index', [
            'referrals' => $this->referrals->paginateReferrals($user)->toArray(),
            'statuses' => (array) config('phase5.referral.statuses', []),
            'transitions' => (array) config('phase5.referral.transitions', []),
            'sourceTypes' => (array) config('phase5.referral.source_types', []),
            'destinationTypes' => (array) config('phase5.referral.destination_types', []),
            'notice' => __('phase5.privacy_notice'),
        ]);
    }

    public function store(StoreReferralRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->referrals->create($user, $request->validatedData());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['destination_id' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', __('phase5.referral_created'));
    }

    public function show(string $locale, Referral $referral): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json($this->referrals->showReferral($user, $referral));
    }

    public function transition(string $locale, Referral $referral, TransitionReferralRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->referrals->transition($user, $referral, $request->validatedData());

        return back()->with('success', __('phase5.transitioned'));
    }
}
