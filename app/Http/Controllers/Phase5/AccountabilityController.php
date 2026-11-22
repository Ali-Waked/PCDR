<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\AssignAccountabilityCaseRequest;
use App\Http\Requests\Phase5\StoreAccountabilityCaseRequest;
use App\Http\Requests\Phase5\TransitionAccountabilityCaseRequest;
use App\Models\Phase5\AccountabilityCase;
use App\Models\User;
use App\Services\Phase5\AccountabilityService;
use App\Services\Phase5\Phase5Authorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class AccountabilityController extends Controller
{
    public function __construct(
        private readonly AccountabilityService $accountability,
        private readonly Phase5Authorization $authorization,
    ) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();
        $cases = $this->accountability->paginateCases($user)->toArray();

        /** @var array<string, array<string, mixed>> $disabled */
        $disabled = (array) config('phase5.accountability.disabled_categories', []);

        return Inertia::render('Phase5/Accountability/Index', [
            'cases' => $cases,
            'enabledCategories' => array_keys((array) config('phase5.accountability.enabled_categories', [])),
            'disabledCategories' => array_keys($disabled),
            'statuses' => (array) config('phase5.accountability.statuses', []),
            'gates' => $this->authorization->gates(),
            'notice' => __('phase5.privacy_notice'),
        ]);
    }

    public function store(StoreAccountabilityCaseRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->accountability->create($user, $request->validatedData());

        return back()->with('success', __('phase5.case_created'));
    }

    public function show(string $locale, AccountabilityCase $case): Response
    {
        /** @var User $user */
        $user = auth()->user();

        return Inertia::render('Phase5/Accountability/Show', $this->accountability->showCase($user, $case));
    }

    public function track(string $locale, string $reference): JsonResponse
    {
        abort_unless($this->authorization->gateEnabled('complaint_public_tracking_enabled'), 404, __('phase5.not_found'));

        $tracking = $this->accountability->track($reference);
        abort_if($tracking === null, 404, __('phase5.not_found'));

        return response()->json($tracking);
    }

    public function transition(string $locale, AccountabilityCase $case, TransitionAccountabilityCaseRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->accountability->transition($user, $case, $request->validatedData());

        return back()->with('success', __('phase5.transitioned'));
    }

    public function assign(string $locale, AccountabilityCase $case, AssignAccountabilityCaseRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->accountability->assign($user, $case, $request->validatedData());

        return back()->with('success', __('phase5.assigned'));
    }
}
