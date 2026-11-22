<?php

declare(strict_types=1);

namespace Tests\Feature {

    use App\Events\Phase5Event;
    use App\Models\Permission;
    use App\Models\Phase5\DiscussionComment;
    use App\Models\Phase5\Task;
    use App\Models\Phase5\TaskAssignmentHistory;
    use App\Models\Phase5\TaskChecklistItem;
    use App\Models\Phase5\TaskDependency;
    use App\Models\Phase5\WorkflowDefinition;
    use App\Models\Phase5\WorkflowInstance;
    use App\Models\Phase5\WorkflowVersion;
    use App\Models\Role;
    use App\Models\User;
    use App\Services\AccessManagementService;
    use App\Services\Phase5\TaskService;
    use App\Services\Phase5\WorkflowService;
    use App\Support\Phase5Support;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Event;
    use Illuminate\Support\Facades\Schema;
    use Inertia\Testing\AssertableInertia as Assert;
    use Tests\TestCase;

    final class Phase5WorkflowTaskTest extends TestCase
    {
        use RefreshDatabase;

        private int $sequence = 0;

        /** The test environment has no APP_KEY; Encryption needs one for POST redirects. */
        public function createApplication()
        {
            $key = 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
            $_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = $key;
            putenv('APP_KEY='.$key);

            return parent::createApplication();
        }

        protected function setUp(): void
        {
            parent::setUp();

            $this->withoutVite();
            config()->set('inertia.testing.ensure_pages_exist', false);
            Event::fake([Phase5Event::class]);
        }

        public function test_phase5_schema_and_governance_guards_are_in_place(): void
        {
            foreach ([
                'phase5_translations', 'workflow_definitions', 'workflow_versions',
                'workflow_instances', 'workflow_steps', 'workflow_transitions',
                'tasks', 'task_collaborators', 'task_assignment_history',
                'task_checklist_items', 'task_dependencies', 'discussion_threads',
                'discussion_comments', 'comment_mentions', 'resource_followers',
                'domain_transitions',
            ] as $table) {
                $this->assertTrue(Schema::hasTable($table), "Missing Phase 5 table [{$table}].");
            }

            foreach (['institutional_approvals', 'delegations', 'officeholders', 'beneficiaries'] as $table) {
                $this->assertFalse(Schema::hasTable($table), "Prohibited table [{$table}] exists.");
            }

            foreach ([
                'institutional_approvals_enabled', 'beneficiary_records_enabled',
                'sensitive_feedback_categories_enabled', 'external_channels_enabled',
                'webhooks_enabled',
            ] as $gate) {
                $this->assertFalse(config("phase5.gates.{$gate}"));
            }

            $this->assertFalse(config('phase5.governance.approval_action_allowed'));
            $this->assertSame([], config('phase5.governance.approval_statuses'));

            foreach (array_keys(config('permissions', [])) as $key) {
                $this->assertStringNotContainsString('.approve', (string) $key);
            }
        }

        public function test_workflow_pages_render_with_the_frozen_contract_props(): void
        {
            $actor = $this->userWith([
                'workflows.view', 'workflows.create', 'workflows.update', 'workflows.transition',
            ]);
            $this->actingAs($actor);

            $definition = $this->definition($actor);
            $version = $this->version($definition, $actor);
            $instance = $this->workflowInstance($definition, $version, $actor);

            $this->get('/ar/operations/phase5/workflows')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Workflows/Index')
                ->has('definitions')
                ->has('instances')
                ->has('statuses')
                ->has('transitions')
                ->has('categories')
                ->has('permissions')
                ->has('notice'));

            $this->get('/ar/operations/phase5/workflows/instances')->assertOk();

            $this->get("/ar/operations/phase5/workflows/instances/{$instance->getKey()}")
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Phase5/Workflows/Show')
                    ->where('instance.id', $instance->getKey())
                    ->has('steps')
                    ->has('history')
                    ->has('statuses')
                    ->has('stepTransitions')
                    ->has('canTransition')
                    ->has('notice'));

            $this->get("/ar/operations/phase5/workflows/instances/{$instance->getKey()}/history")
                ->assertOk()
                ->assertJsonStructure(['history']);
        }

        public function test_version_snapshots_are_immutable_and_reject_unknown_or_approval_configuration(): void
        {
            $actor = $this->userWith(['workflows.view', 'workflows.create', 'workflows.update']);
            $this->actingAs($actor);

            $this->post('/ar/operations/phase5/workflows/definitions', [
                'key' => 'stable-flow',
                'category' => 'generic',
                'translations' => ['ar' => ['name' => 'تدفق ثابت']],
            ])->assertRedirect();

            $definition = WorkflowDefinition::query()->where('key', 'stable-flow')->firstOrFail();

            $this->assertDatabaseHas('phase5_translations', [
                'resource' => 'workflow_definition',
                'owner_id' => $definition->getKey(),
                'locale' => 'ar',
                'name' => 'تدفق ثابت',
            ]);

            $this->post("/ar/operations/phase5/workflows/definitions/{$definition->getKey()}/versions", [
                'steps' => [
                    [
                        'key' => 'intake',
                        'label' => 'Intake',
                        'order' => 1,
                        'assignment_type' => 'user',
                        'assignee_id' => $actor->getKey(),
                        'completion_condition' => ['field' => 'status', 'operator' => '=', 'value' => 'done'],
                        'metadata' => ['field' => 'source'],
                        'notification_event' => 'task.created',
                    ],
                    [
                        'key' => 'review',
                        'label' => 'Review',
                        'order' => 2,
                        'assignment_type' => 'unassigned',
                    ],
                ],
            ])->assertRedirect();

            $version = $definition->versions()->firstOrFail();
            $config = Phase5Support::decode((string) DB::table('workflow_versions')
                ->where('id', $version->getKey())
                ->value('config'));

            $this->assertSame(['steps'], array_keys($config));
            $this->assertCount(2, $config['steps']);

            $storedKeys = array_keys($config['steps'][0]);
            $expectedKeys = [
                'key', 'label', 'order', 'assignment_type', 'assignee_id',
                'completion_condition', 'metadata', 'notification_event',
            ];
            sort($storedKeys);
            sort($expectedKeys);
            $this->assertSame($expectedKeys, $storedKeys);
            $this->assertSame(
                ['field' => 'status', 'operator' => '=', 'value' => 'done'],
                $config['steps'][0]['completion_condition'],
            );
            $this->assertSame(1, $definition->versions()->count());

            $this->post("/ar/operations/phase5/workflows/definitions/{$definition->getKey()}/versions", [
                'steps' => [
                    ['key' => 'bad-step', 'label' => 'Bad step', 'approval_role' => 'manager'],
                ],
            ])->assertSessionHasErrors('steps');

            $this->post("/ar/operations/phase5/workflows/definitions/{$definition->getKey()}/versions", [
                'steps' => [
                    ['key' => 'bad-step', 'label' => 'Bad step', 'notification_event' => 'approval_granted'],
                ],
            ])->assertSessionHasErrors('steps.0.notification_event');

            $this->assertSame(1, $definition->versions()->count());

            $originalConfig = (string) DB::table('workflow_versions')
                ->where('id', $version->getKey())
                ->value('config');

            $this->put("/ar/operations/phase5/workflows/definitions/{$definition->getKey()}", [
                'key' => 'stable-flow-v2',
                'category' => 'task_follow_up',
                'status' => 'active',
            ])->assertRedirect();

            $this->assertSame('stable-flow-v2', $definition->refresh()->key);
            $this->assertSame(
                $originalConfig,
                DB::table('workflow_versions')->where('id', $version->getKey())->value('config'),
            );

            $instance = $this->workflowInstance($definition, $version, $actor);
            $this->assertSame($version->getKey(), (int) $instance->version_id);
        }

        public function test_workflow_instance_and_step_transitions_append_both_ledgers_and_events(): void
        {
            $actor = $this->userWith([
                'workflows.view', 'workflows.create', 'workflows.update', 'workflows.transition',
            ]);
            $this->actingAs($actor);

            $definition = $this->definition($actor);
            $version = $this->version($definition, $actor);
            $instance = $this->workflowInstance($definition, $version, $actor);

            Event::fake([Phase5Event::class]);

            $this->post("/ar/operations/phase5/workflows/instances/{$instance->getKey()}/transition", [
                'to_status' => 'in_progress',
                'action' => 'advance',
            ])->assertRedirect()->assertSessionHas('success');

            $this->assertSame('in_progress', $instance->refresh()->status);
            $this->assertDatabaseHas('workflow_transitions', [
                'instance_id' => $instance->getKey(),
                'from_status' => 'active',
                'to_status' => 'in_progress',
                'action' => 'advance',
            ]);
            $this->assertDatabaseHas('domain_transitions', [
                'resource' => 'workflow_instances',
                'owner_id' => $instance->getKey(),
                'to_status' => 'in_progress',
            ]);

            $step = $instance->steps()->orderBy('ordering')->firstOrFail();

            $this->post(
                "/ar/operations/phase5/workflows/instances/{$instance->getKey()}/steps/{$step->getKey()}/transition",
                ['to_status' => 'in_progress'],
            )->assertRedirect()->assertSessionHas('success');

            $this->assertSame('in_progress', $step->refresh()->status);
            $this->assertNotNull($step->started_at);
            $this->assertDatabaseHas('workflow_transitions', [
                'instance_id' => $instance->getKey(),
                'to_status' => 'in_progress',
                'action' => 'step_advance',
            ]);
            $this->assertDatabaseHas('domain_transitions', [
                'resource' => 'workflow_steps',
                'owner_id' => $step->getKey(),
                'to_status' => 'in_progress',
            ]);

            Event::assertDispatched(Phase5Event::class, function (Phase5Event $event) use ($instance): bool {
                return $event->name === 'workflow.status_changed'
                    && $event->subjectType === 'workflow_instance'
                    && $event->subjectId === $instance->getKey()
                    && $event->payload['to_status'] === 'in_progress'
                    && isset($event->payload['user_ids']);
            });

            $otherInstance = $this->workflowInstance($definition, $version, $actor);
            $otherStep = $otherInstance->steps()->firstOrFail();

            $this->post(
                "/ar/operations/phase5/workflows/instances/{$instance->getKey()}/steps/{$otherStep->getKey()}/transition",
                ['to_status' => 'in_progress'],
            )->assertNotFound();
        }

        public function test_approval_tokens_and_invalid_transitions_are_rejected(): void
        {
            $actor = $this->userWith([
                'workflows.view', 'workflows.create', 'workflows.update', 'workflows.transition',
                'tasks.view', 'tasks.create', 'tasks.transition',
            ]);
            $this->actingAs($actor);

            $definition = $this->definition($actor);
            $version = $this->version($definition, $actor);
            $instance = $this->workflowInstance($definition, $version, $actor);
            $step = $instance->steps()->firstOrFail();

            $this->post("/ar/operations/phase5/workflows/instances/{$instance->getKey()}/transition", [
                'to_status' => 'approved',
            ])->assertSessionHasErrors('to_status');

            $this->post(
                "/ar/operations/phase5/workflows/instances/{$instance->getKey()}/steps/{$step->getKey()}/transition",
                ['to_status' => 'approved'],
            )->assertSessionHasErrors('to_status');

            $this->post('/ar/operations/phase5/tasks', [
                'title' => 'Approval attempt',
                'status' => 'approved',
            ])->assertSessionHasErrors('status');

            $this->assertDatabaseMissing('tasks', ['title' => 'Approval attempt']);

            $task = $this->task($actor, ['title' => 'Guarded task']);

            $this->post("/ar/operations/phase5/tasks/{$task->getKey()}/transition", [
                'to_status' => 'approved',
            ])->assertSessionHasErrors('to_status');

            $this->post("/ar/operations/phase5/tasks/{$task->getKey()}/transition", [
                'to_status' => 'completed',
            ])->assertSessionHasErrors('to_status');

            $this->assertSame('backlog', $task->refresh()->status);
            $this->assertDatabaseMissing('domain_transitions', [
                'resource' => 'tasks',
                'owner_id' => $task->getKey(),
                'to_status' => 'approved',
            ]);
        }

        public function test_unviewable_workflow_instance_returns_not_found_on_transition(): void
        {
            $initiator = $this->userWith(['projects.view']);
            $actor = $this->userWith(['workflows.view', 'workflows.transition']);

            $this->actingAs($initiator);
            $definition = $this->definition($initiator);
            $version = $this->version($definition, $initiator);
            $instance = $this->workflowInstance($definition, $version, $initiator, [
                'subject_type' => 'project',
                'subject_id' => 991,
            ]);

            $this->actingAs($actor);

            $this->get("/ar/operations/phase5/workflows/instances/{$instance->getKey()}")->assertNotFound();

            $this->post("/ar/operations/phase5/workflows/instances/{$instance->getKey()}/transition", [
                'to_status' => 'in_progress',
            ])->assertNotFound();

            $this->assertSame('active', $instance->refresh()->status);
        }

        public function test_task_lifecycle_assignment_checklist_and_events(): void
        {
            $actor = $this->userWith([
                'tasks.view', 'tasks.create', 'tasks.update', 'tasks.assign', 'tasks.transition',
            ]);
            $this->actingAs($actor);

            Event::fake([Phase5Event::class]);

            $this->post('/ar/operations/phase5/tasks', [
                'title' => 'Lifecycle task',
                'status' => 'backlog',
                'priority' => 'normal',
                'owner_id' => $actor->getKey(),
            ])->assertRedirect();

            $task = Task::query()->where('title', 'Lifecycle task')->firstOrFail();

            Event::assertDispatched(Phase5Event::class, function (Phase5Event $event) use ($actor, $task): bool {
                return $event->name === 'task.created'
                    && $event->subjectType === 'task'
                    && $event->subjectId === $task->getKey()
                    && $event->payload['task_id'] === $task->getKey()
                    && in_array($actor->getKey(), $event->payload['user_ids'] ?? [], true);
            });

            $colleague = $this->userWith(['tasks.view']);

            Event::fake([Phase5Event::class]);

            $this->post("/ar/operations/phase5/tasks/{$task->getKey()}/assign", [
                'assignee_id' => $colleague->getKey(),
            ])->assertRedirect();

            $this->assertSame($colleague->getKey(), (int) $task->refresh()->owner_id);
            $this->assertSame(2, TaskAssignmentHistory::query()->where('task_id', $task->getKey())->count());

            Event::assertDispatched(Phase5Event::class, function (Phase5Event $event) use ($colleague, $task): bool {
                return $event->name === 'task.assigned'
                    && $event->payload['task_id'] === $task->getKey()
                    && in_array($colleague->getKey(), $event->payload['user_ids'] ?? [], true);
            });

            $this->post("/ar/operations/phase5/tasks/{$task->getKey()}/checklist", [
                'title' => 'Review notes',
            ])->assertRedirect();

            $item = TaskChecklistItem::query()->where('task_id', $task->getKey())->firstOrFail();

            $this->post("/ar/operations/phase5/tasks/{$task->getKey()}/checklist/{$item->getKey()}/toggle")
                ->assertRedirect();

            $this->assertTrue((bool) $item->refresh()->is_done);
            $this->assertSame($actor->getKey(), (int) $item->completed_by);

            Event::fake([Phase5Event::class]);

            foreach (['to_do', 'in_progress', 'completed'] as $status) {
                $this->post("/ar/operations/phase5/tasks/{$task->getKey()}/transition", [
                    'to_status' => $status,
                ])->assertRedirect();
            }

            $this->assertSame('completed', $task->refresh()->status);
            $this->assertNotNull($task->completed_at);
            $this->assertDatabaseHas('domain_transitions', [
                'resource' => 'tasks',
                'owner_id' => $task->getKey(),
                'to_status' => 'completed',
            ]);

            Event::assertDispatched(Phase5Event::class, fn (Phase5Event $event): bool => $event->name === 'task.status_changed');

            $this->post("/ar/operations/phase5/tasks/{$task->getKey()}/transition", [
                'to_status' => 'in_progress',
            ])->assertSessionHasErrors('reason');

            $this->post("/ar/operations/phase5/tasks/{$task->getKey()}/transition", [
                'to_status' => 'in_progress',
                'reason' => 'Reopened for clarification',
            ])->assertRedirect();

            $this->assertSame('in_progress', $task->refresh()->status);
            $this->assertNull($task->completed_at);
            $this->assertDatabaseHas('audit_logs', [
                'action' => 'phase5.task.status_changed',
                'subject_type' => 'task',
                'subject_id' => (string) $task->getKey(),
            ]);
        }

        public function test_task_dependency_cycles_are_rejected_and_foreign_items_return_not_found(): void
        {
            $actor = $this->userWith(['tasks.view', 'tasks.create', 'tasks.update']);
            $this->actingAs($actor);

            $first = $this->task($actor, ['title' => 'Dependency A']);
            $second = $this->task($actor, ['title' => 'Dependency B']);

            $this->post("/ar/operations/phase5/tasks/{$first->getKey()}/dependencies", [
                'depends_on_task_id' => $second->getKey(),
            ])->assertRedirect();

            $this->post("/ar/operations/phase5/tasks/{$second->getKey()}/dependencies", [
                'depends_on_task_id' => $first->getKey(),
            ])->assertSessionHasErrors('depends_on_task_id');

            $this->assertSame(1, TaskDependency::query()->count());
            $this->assertDatabaseHas('task_dependencies', [
                'task_id' => $first->getKey(),
                'depends_on_task_id' => $second->getKey(),
            ]);

            $third = $this->task($actor, ['title' => 'Dependency C']);
            $foreignItem = app(TaskService::class)->addChecklistItem($third, ['title' => 'Foreign item'], $actor);

            $this->post(
                "/ar/operations/phase5/tasks/{$first->getKey()}/checklist/{$foreignItem->getKey()}/toggle",
            )->assertNotFound();
        }

        public function test_task_index_views_and_sort_fields_are_allow_listed(): void
        {
            $actor = $this->userWith(['tasks.view']);
            $this->actingAs($actor);

            $this->task($actor, [
                'title' => 'Assigned task',
                'status' => 'to_do',
                'owner_id' => $actor->getKey(),
            ]);

            $this->get('/ar/operations/phase5/tasks?view=assigned&sort=title&direction=asc')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Phase5/Tasks/Index')
                    ->has('tasks')
                    ->has('statuses')
                    ->has('priorities')
                    ->where('views', [
                        'my', 'assigned', 'team', 'overdue', 'upcoming',
                        'blocked', 'completed', 'project', 'workflow',
                    ])
                    ->has('permissions'));

            $this->get('/ar/operations/phase5/tasks?sort=not_a_column')->assertSessionHasErrors('sort');
        }

        public function test_discussion_threads_comments_follow_and_timeline(): void
        {
            $actor = $this->userWith([
                'tasks.view', 'collaboration.view', 'collaboration.comment', 'collaboration.moderate',
            ]);
            $this->actingAs($actor);

            $task = $this->task($actor, ['title' => 'Discussion task']);
            $base = "/ar/operations/phase5/discussions/task/{$task->getKey()}";

            $this->get($base)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Phase5/Collaboration/Index')
                ->where('resource.type', 'task')
                ->where('resource.id', $task->getKey())
                ->where('thread', null)
                ->has('comments')
                ->has('followers')
                ->has('canComment')
                ->has('canMention')
                ->has('canModerate'));

            $this->post("{$base}/comments", ['body' => 'First note'])->assertRedirect();

            $first = DiscussionComment::query()->where('body', 'First note')->firstOrFail();

            $this->post("{$base}/comments", [
                'body' => 'Reply',
                'parent_id' => $first->getKey(),
            ])->assertRedirect();

            $reply = DiscussionComment::query()->where('body', 'Reply')->firstOrFail();
            $this->assertSame($first->getKey(), (int) $reply->parent_id);

            $this->put("/ar/operations/phase5/discussions/comments/{$first->getKey()}", [
                'body' => 'Edited note',
            ])->assertRedirect();

            $this->assertNotNull($first->refresh()->edited_at);
            $this->assertSame('Edited note', $first->body);

            $this->delete("/ar/operations/phase5/discussions/comments/{$reply->getKey()}")->assertRedirect();
            $this->assertSoftDeleted('discussion_comments', ['id' => $reply->getKey()]);

            $this->post("{$base}/follow")->assertRedirect();
            $this->assertSame(1, DB::table('resource_followers')
                ->where('resource_type', 'task')
                ->where('resource_id', $task->getKey())
                ->count());

            $this->post("{$base}/follow")->assertRedirect();
            $this->assertSame(0, DB::table('resource_followers')
                ->where('resource_type', 'task')
                ->where('resource_id', $task->getKey())
                ->count());

            $this->get("{$base}/timeline")->assertOk()->assertJsonStructure(['timeline']);
            $timeline = $this->get("{$base}/timeline")->json('timeline');

            $this->assertIsArray($timeline);
            $this->assertLessThanOrEqual(50, count($timeline));
            $this->assertTrue(collect($timeline)->contains(
                static fn (array $event): bool => str_starts_with((string) $event['id'], 'comment-'),
            ));
            $this->assertSame(1, DB::table('discussion_threads')
                ->where('resource_type', 'task')
                ->where('resource_id', $task->getKey())
                ->count());
        }

        public function test_authorized_mention_dispatches_a_minimal_event_payload(): void
        {
            $actor = $this->userWith([
                'tasks.view', 'tasks.create', 'collaboration.view',
                'collaboration.comment', 'collaboration.mention',
            ]);
            $recipient = $this->userWith(['tasks.view']);

            $this->actingAs($actor);
            $task = $this->task($actor, ['title' => 'Mention task']);

            Event::fake([Phase5Event::class]);

            $this->post("/ar/operations/phase5/discussions/task/{$task->getKey()}/comments", [
                'body' => 'Please review this note',
                'mentions' => [$recipient->getKey()],
            ])->assertRedirect();

            $comment = DiscussionComment::query()->where('body', 'Please review this note')->firstOrFail();

            $this->assertDatabaseHas('comment_mentions', [
                'comment_id' => $comment->getKey(),
                'mentioned_user_id' => $recipient->getKey(),
            ]);

            Event::assertDispatched(Phase5Event::class, function (Phase5Event $event) use ($comment, $recipient, $task): bool {
                return $event->name === 'collaboration.mention'
                    && $event->subjectType === 'task'
                    && $event->subjectId === $task->getKey()
                    && $event->actorId === $comment->author_id
                    && $event->payload == [
                        'resource_type' => 'task',
                        'resource_id' => $task->getKey(),
                        'comment_id' => $comment->getKey(),
                        'user_ids' => [$recipient->getKey()],
                    ];
            });
        }

        public function test_mention_of_user_without_source_permission_and_cross_resource_comment_are_rejected(): void
        {
            $actor = $this->userWith([
                'tasks.view', 'tasks.create', 'projects.view', 'collaboration.view',
                'collaboration.comment', 'collaboration.mention',
            ]);
            $recipient = $this->userWith(['tasks.view']);

            $this->actingAs($actor);
            $restrictedTask = $this->task($actor, [
                'title' => 'Restricted task',
                'subject_type' => 'project',
                'subject_id' => 777,
            ]);

            Event::fake([Phase5Event::class]);

            $this->post("/ar/operations/phase5/discussions/task/{$restrictedTask->getKey()}/comments", [
                'body' => 'Restricted mention',
                'mentions' => [$recipient->getKey()],
            ])->assertNotFound();

            $this->assertSame(0, DiscussionComment::query()->count());
            $this->assertSame(0, DB::table('comment_mentions')->count());
            Event::assertNotDispatched(
                Phase5Event::class,
                static fn (Phase5Event $event): bool => $event->name === 'collaboration.mention',
            );

            $this->post("/ar/operations/phase5/discussions/task/{$restrictedTask->getKey()}/comments", [
                'body' => 'Moderator note',
            ])->assertRedirect();

            $comment = DiscussionComment::query()->where('body', 'Moderator note')->firstOrFail();
            $viewer = $this->userWith(['collaboration.view', 'collaboration.comment', 'tasks.view']);

            $this->actingAs($viewer);

            $this->put("/ar/operations/phase5/discussions/comments/{$comment->getKey()}", [
                'body' => 'Should fail',
            ])->assertNotFound();

            $this->assertSame('Moderator note', $comment->refresh()->body);
        }

        /** @param array<string, mixed> $attributes */
        private function definition(User $actor, array $attributes = []): WorkflowDefinition
        {
            $this->sequence++;

            return WorkflowDefinition::query()->create(array_merge([
                'key' => 'flow-'.$this->sequence,
                'category' => 'generic',
                'status' => 'draft',
                'trigger_event' => 'manual',
                'owner_id' => $actor->getKey(),
                'created_by' => $actor->getKey(),
            ], $attributes));
        }

        /** @param list<array<string, mixed>>|null $steps */
        private function version(WorkflowDefinition $definition, User $actor, ?array $steps = null): WorkflowVersion
        {
            return app(WorkflowService::class)->createVersion($definition, [
                'steps' => $steps ?? [
                    ['key' => 'triage', 'label' => 'Triage', 'order' => 1, 'assignment_type' => 'unassigned'],
                    ['key' => 'review', 'label' => 'Review', 'order' => 2, 'assignment_type' => 'unassigned'],
                ],
            ], $actor);
        }

        /** @param array<string, mixed> $data */
        private function workflowInstance(
            WorkflowDefinition $definition,
            WorkflowVersion $version,
            User $actor,
            array $data = [],
        ): WorkflowInstance {
            return app(WorkflowService::class)->startInstance($definition, array_merge([
                'version_id' => $version->getKey(),
            ], $data), $actor);
        }

        /** @param array<string, mixed> $attributes */
        private function task(User $actor, array $attributes = []): Task
        {
            $this->sequence++;

            return app(TaskService::class)->create(array_merge([
                'title' => 'Task '.$this->sequence,
                'status' => 'backlog',
                'priority' => 'normal',
            ], $attributes), $actor);
        }

        /** @param list<string> $keys */
        private function userWith(array $keys): User
        {
            $user = User::factory()->create();
            $role = Role::create(['key' => 'phase5-'.($user->id), 'name' => 'Phase 5 operator']);
            $catalog = config('permissions', []);

            foreach ($keys as $key) {
                $definition = is_array($catalog[$key] ?? null) ? $catalog[$key] : [];
                $permission = Permission::firstOrCreate([
                    'key' => $key,
                    'name' => $definition['label'] ?? $key,
                    'module' => $definition['module'] ?? 'phase5',
                    'is_protected' => false,
                    'is_retired' => false,
                ]);
                $role->permissions()->attach($permission);
            }

            app(AccessManagementService::class)->syncRoles($user, [$role->id]);

            return $user->refresh();
        }
    }
}
