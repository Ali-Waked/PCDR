<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Exceptions\InvalidDomainTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\ArchiveTaskRequest;
use App\Http\Requests\Phase5\AssignTaskRequest;
use App\Http\Requests\Phase5\StoreChecklistRequest;
use App\Http\Requests\Phase5\StoreTaskDependencyRequest;
use App\Http\Requests\Phase5\StoreTaskRequest;
use App\Http\Requests\Phase5\TaskIndexRequest;
use App\Http\Requests\Phase5\ToggleChecklistRequest;
use App\Http\Requests\Phase5\TransitionTaskRequest;
use App\Http\Requests\Phase5\UpdateTaskRequest;
use App\Models\Phase5\Task;
use App\Models\Phase5\TaskChecklistItem;
use App\Models\User;
use App\Services\Phase5\Phase5Authorization;
use App\Services\Phase5\TaskService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class TaskController extends Controller
{
    private const VIEWS = [
        'my', 'assigned', 'team', 'overdue', 'upcoming',
        'blocked', 'completed', 'project', 'workflow',
    ];

    public function __construct(
        private readonly TaskService $tasks,
        private readonly Phase5Authorization $authorization,
    ) {}

    public function index(string $locale, TaskIndexRequest $request): Response
    {
        $tasks = $this->tasks->paginate($request->filters(), $this->actor());
        $tasks->setCollection(collect($this->tasks->taskPayloads($tasks->items())));

        return Inertia::render('Phase5/Tasks/Index', [
            'tasks' => $tasks,
            'statuses' => array_values((array) config('phase5.task.statuses', [])),
            'priorities' => array_values((array) config('phase5.task.priorities', [])),
            'views' => self::VIEWS,
            'permissions' => $this->permissions(),
        ]);
    }

    public function store(string $locale, StoreTaskRequest $request): RedirectResponse
    {
        try {
            $this->tasks->create($request->taskInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.created'));
    }

    public function show(string $locale, Task $task): Response
    {
        if (! $this->tasks->canView($task, $this->actor())) {
            abort(404, __('phase5.not_found'));
        }

        $task->loadMissing(['owner:id,name', 'creator:id,name']);

        return Inertia::render('Phase5/Tasks/Show', [
            'task' => $this->tasks->taskPayload($task),
            'checklist' => $this->tasks->checklistPayloads($task),
            'dependencies' => $this->tasks->dependencyPayloads($task),
            'collaborators' => $this->tasks->collaboratorPayloads($task),
            'activity' => $this->tasks->activity($task),
            'statuses' => array_values((array) config('phase5.task.statuses', [])),
            'transitions' => (array) config('phase5.task.transitions', []),
        ]);
    }

    public function update(string $locale, UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        try {
            $this->tasks->update($task, $request->taskInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.updated'));
    }

    public function destroy(string $locale, ArchiveTaskRequest $request, Task $task): RedirectResponse
    {
        $this->tasks->delete($task, $this->actor());

        return back()->with('success', __('phase5.archived'));
    }

    public function transition(string $locale, TransitionTaskRequest $request, Task $task): RedirectResponse
    {
        try {
            $this->tasks->transition($task, $request->transitionInput(), $this->actor());
        } catch (InvalidDomainTransition) {
            return back()->with('error', __('phase5.invalid_transition'));
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.transitioned'));
    }

    public function assign(string $locale, AssignTaskRequest $request, Task $task): RedirectResponse
    {
        try {
            $this->tasks->assign($task, $request->assignmentInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.assigned'));
    }

    public function storeChecklist(string $locale, StoreChecklistRequest $request, Task $task): RedirectResponse
    {
        try {
            $this->tasks->addChecklistItem($task, $request->checklistInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.checklist_added'));
    }

    public function toggleChecklist(
        string $locale,
        ToggleChecklistRequest $request,
        Task $task,
        TaskChecklistItem $item,
    ): RedirectResponse {
        try {
            $this->tasks->toggleChecklistItem($task, $item, $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.updated'));
    }

    public function storeDependency(string $locale, StoreTaskDependencyRequest $request, Task $task): RedirectResponse
    {
        try {
            $this->tasks->addDependency($task, $request->dependencyInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.dependency_added'));
    }

    /** @return array<string, bool> */
    private function permissions(): array
    {
        $actor = $this->actor();

        return [
            'view' => $this->authorization->allows($actor, 'tasks.view'),
            'create' => $this->authorization->allows($actor, 'tasks.create'),
            'update' => $this->authorization->allows($actor, 'tasks.update'),
            'delete' => $this->authorization->allows($actor, 'tasks.delete'),
            'assign' => $this->authorization->allows($actor, 'tasks.assign'),
            'transition' => $this->authorization->allows($actor, 'tasks.transition'),
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
