<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Exceptions\InvalidDomainTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\StoreWorkflowDefinitionRequest;
use App\Http\Requests\Phase5\StoreWorkflowVersionRequest;
use App\Http\Requests\Phase5\TransitionWorkflowInstanceRequest;
use App\Http\Requests\Phase5\TransitionWorkflowStepRequest;
use App\Http\Requests\Phase5\UpdateWorkflowDefinitionRequest;
use App\Http\Requests\Phase5\WorkflowIndexRequest;
use App\Models\Phase5\WorkflowDefinition;
use App\Models\Phase5\WorkflowInstance;
use App\Models\Phase5\WorkflowStep;
use App\Models\User;
use App\Services\Phase5\Phase5Authorization;
use App\Services\Phase5\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflows,
        private readonly Phase5Authorization $authorization,
    ) {}

    public function index(string $locale, WorkflowIndexRequest $request): Response
    {
        $filters = $request->filters();
        $definitions = $this->workflows->paginateDefinitions($filters);
        $definitions->setCollection(collect($this->workflows->definitionPayloads($definitions->items())));

        $instances = $this->workflows->paginateInstances($filters, $this->actor());
        $instances->setCollection(collect($this->workflows->instancePayloads($instances->items())));

        return Inertia::render('Phase5/Workflows/Index', [
            'definitions' => $definitions,
            'instances' => $instances,
            'statuses' => array_values((array) config('phase5.workflow.statuses', [])),
            'transitions' => (array) config('phase5.workflow.transitions', []),
            'categories' => array_values((array) config('phase5.workflow.categories', [])),
            'permissions' => $this->permissions(),
            'notice' => __('phase5.approval_notice'),
        ]);
    }

    public function storeDefinition(string $locale, StoreWorkflowDefinitionRequest $request): RedirectResponse
    {
        try {
            $this->workflows->createDefinition($request->definitionInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.created'));
    }

    public function updateDefinition(
        string $locale,
        UpdateWorkflowDefinitionRequest $request,
        WorkflowDefinition $definition,
    ): RedirectResponse {
        try {
            $this->workflows->updateDefinition($definition, $request->definitionInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.updated'));
    }

    public function storeVersion(
        string $locale,
        StoreWorkflowVersionRequest $request,
        WorkflowDefinition $definition,
    ): RedirectResponse {
        try {
            $this->workflows->createVersion($definition, $request->versionInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.created'));
    }

    public function instances(string $locale, WorkflowIndexRequest $request): Response
    {
        return $this->index($locale, $request);
    }

    public function showInstance(string $locale, WorkflowInstance $instance): Response
    {
        $this->assertInstanceViewable($instance);
        $instance->loadMissing('definition');

        $steps = $instance->steps()
            ->orderBy('ordering')
            ->orderBy('id')
            ->get()
            ->map(fn (WorkflowStep $step): array => $this->workflows->stepPayload($step))
            ->all();

        return Inertia::render('Phase5/Workflows/Show', [
            'instance' => $this->workflows->instancePayload($instance, $instance->definition),
            'steps' => $steps,
            'history' => $this->workflows->instanceHistory($instance),
            'statuses' => array_values((array) config('phase5.workflow.statuses', [])),
            'stepTransitions' => (array) config('phase5.workflow.step_transitions', []),
            'canTransition' => $this->authorization->allows($this->actor(), 'workflows.transition'),
            'notice' => __('phase5.approval_notice'),
        ]);
    }

    public function history(string $locale, WorkflowInstance $instance): JsonResponse
    {
        $this->assertInstanceViewable($instance);

        return response()->json([
            'history' => $this->workflows->instanceHistory($instance),
        ]);
    }

    public function transitionInstance(
        string $locale,
        TransitionWorkflowInstanceRequest $request,
        WorkflowInstance $instance,
    ): RedirectResponse {
        try {
            $this->workflows->transitionInstance($instance, $request->transitionInput(), $this->actor());
        } catch (InvalidDomainTransition) {
            return back()->with('error', __('phase5.invalid_transition'));
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.transitioned'));
    }

    public function transitionStep(
        string $locale,
        TransitionWorkflowStepRequest $request,
        WorkflowInstance $instance,
        WorkflowStep $step,
    ): RedirectResponse {
        try {
            $this->workflows->transitionStep($instance, $step, $request->transitionInput(), $this->actor());
        } catch (InvalidDomainTransition) {
            return back()->with('error', __('phase5.invalid_transition'));
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.transitioned'));
    }

    private function assertInstanceViewable(WorkflowInstance $instance): void
    {
        $this->authorization->assertCanViewResource(
            $this->actor(),
            'workflow_instance',
            (int) $instance->getKey(),
        );
    }

    /** @return array<string, bool> */
    private function permissions(): array
    {
        $actor = $this->actor();

        return [
            'view' => $this->authorization->allows($actor, 'workflows.view'),
            'create' => $this->authorization->allows($actor, 'workflows.create'),
            'update' => $this->authorization->allows($actor, 'workflows.update'),
            'transition' => $this->authorization->allows($actor, 'workflows.transition'),
        ];
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
