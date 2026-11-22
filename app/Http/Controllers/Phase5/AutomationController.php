<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\AutomationIndexRequest;
use App\Http\Requests\Phase5\StoreAutomationRequest;
use App\Http\Requests\Phase5\UpdateAutomationRequest;
use App\Models\Phase5\AutomationDefinition;
use App\Models\Phase5\AutomationExecution;
use App\Models\User;
use App\Services\Phase5\AutomationEngineService;
use App\Services\Phase5\Phase5Authorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class AutomationController extends Controller
{
    public function __construct(
        private readonly AutomationEngineService $automations,
        private readonly Phase5Authorization $authorization,
    ) {}

    public function index(AutomationIndexRequest $request): Response
    {
        return Inertia::render('Phase5/Automations/Index', [
            'definitions' => $this->automations->definitions($request->filters()),
            'executions' => $this->automations->executions($request->filters()),
            'triggers' => array_values((array) config('phase5.automation.triggers', [])),
            'operators' => (array) config('phase5.automation.operators', []),
            'actions' => array_keys((array) config('phase5.automation.actions', [])),
            'fields' => (array) config('phase5.automation.fields', []),
            'gates' => $this->authorization->gates(),
        ]);
    }

    public function executions(AutomationIndexRequest $request): Response
    {
        return $this->index($request);
    }

    public function store(StoreAutomationRequest $request): RedirectResponse
    {
        try {
            $this->automations->create($this->actor(), $request->automationInput());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.automation_created'));
    }

    public function update(UpdateAutomationRequest $request, AutomationDefinition $automation): RedirectResponse
    {
        try {
            $this->automations->update($this->actor(), $automation, $request->automationInput());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.automation_updated'));
    }

    public function toggle(Request $request, AutomationDefinition $automation): RedirectResponse
    {
        $this->automations->toggle($this->actor(), $automation, $request->boolean('enabled'));

        return back()->with('success', __('phase5.automation_toggled'));
    }

    public function retry(AutomationExecution $execution): RedirectResponse
    {
        try {
            $this->automations->retry($this->actor(), $execution);
        } catch (InvalidArgumentException) {
            return back()->with('error', __('phase5.automation_failed'));
        }

        return back()->with('success', __('phase5.automation_retried'));
    }

    private function actor(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403, __('phase5.no_permission'));
        }

        return $user;
    }
}
