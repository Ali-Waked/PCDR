<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 institutional operating layer.
 *
 * Deliberately absent: beneficiaries, households, identifiable case records,
 * sensitive personal data, approvals, delegations, officeholders and
 * institutional-signature tables. OQ-24..OQ-29 and OQ-49 remain unresolved.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createSharedAndWorkflowTables();
        $this->createTaskTables();
        $this->createCollaborationTables();
        $this->createAccountabilityAndReferralTables();
        $this->createCommunicationAndAutomationTables();
        $this->createDocumentReviewTables();
        $this->createIntegrationTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_webhook_events');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('integration_registries');
        Schema::dropIfExists('document_review_events');
        Schema::dropIfExists('document_reviews');
        Schema::dropIfExists('automation_executions');
        Schema::dropIfExists('automation_definitions');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('referral_events');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('accountability_events');
        Schema::dropIfExists('accountability_cases');
        Schema::dropIfExists('resource_followers');
        Schema::dropIfExists('comment_mentions');
        Schema::dropIfExists('discussion_comments');
        Schema::dropIfExists('discussion_threads');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('task_checklist_items');
        Schema::dropIfExists('task_assignment_history');
        Schema::dropIfExists('task_collaborators');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('workflow_transitions');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflow_definitions');
        Schema::dropIfExists('phase5_translations');
    }

    private function createSharedAndWorkflowTables(): void
    {
        Schema::create('phase5_translations', function (Blueprint $table): void {
            $table->id();
            $table->string('resource', 64);
            $table->unsignedBigInteger('owner_id');
            $table->string('locale', 8);
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['resource', 'owner_id', 'locale'], 'phase5_translations_owner_locale_unique');
            $table->index(['resource', 'locale'], 'phase5_translations_resource_locale_index');
        });

        Schema::create('workflow_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 96)->unique();
            $table->string('category', 48);
            $table->string('status', 32)->default('draft');
            $table->string('trigger_event', 96)->default('manual');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category'], 'workflow_definitions_status_category_index');
        });

        Schema::create('workflow_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('config');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['definition_id', 'version'], 'workflow_versions_definition_version_unique');
        });

        Schema::create('workflow_instances', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->foreignId('definition_id')->constrained('workflow_definitions')->restrictOnDelete();
            $table->foreignId('version_id')->constrained('workflow_versions')->restrictOnDelete();
            $table->string('subject_type', 96)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('current_step_order')->default(0);
            $table->foreignId('initiator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'workflow_instances_status_created_index');
            $table->index(['subject_type', 'subject_id'], 'workflow_instances_subject_index');
        });

        Schema::create('workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->string('step_key', 96);
            $table->string('label', 255);
            $table->unsignedInteger('ordering');
            $table->string('assignment_type', 32)->default('unassigned');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role_key', 96)->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('result')->nullable();
            $table->timestamps();

            $table->unique(['instance_id', 'step_key'], 'workflow_steps_instance_step_unique');
            $table->index(['instance_id', 'status'], 'workflow_steps_instance_status_index');
            $table->index(['assignee_id', 'status'], 'workflow_steps_assignee_status_index');
        });

        Schema::create('workflow_transitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('action', 96);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->text('context')->nullable();
            $table->timestamp('created_at');

            $table->index(['instance_id', 'created_at'], 'workflow_transitions_instance_created_index');
        });
    }

    private function createTaskTables(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('to_do');
            $table->string('priority', 16)->default('normal');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 96)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_at'], 'tasks_status_due_index');
            $table->index(['owner_id', 'status'], 'tasks_owner_status_index');
            $table->index(['creator_id', 'status'], 'tasks_creator_status_index');
            $table->index(['subject_type', 'subject_id'], 'tasks_subject_index');
            $table->index(['workflow_instance_id'], 'tasks_workflow_instance_index');
        });

        Schema::create('task_collaborators', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id'], 'task_collaborators_task_user_unique');
        });

        Schema::create('task_assignment_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['task_id', 'created_at'], 'task_assignment_history_task_created_index');
        });

        Schema::create('task_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('title', 255);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_done')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'position'], 'task_checklist_items_task_position_index');
        });

        Schema::create('task_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('type', 32)->default('finish_to_start');
            $table->timestamp('created_at');

            $table->unique(['task_id', 'depends_on_task_id'], 'task_dependencies_pair_unique');
        });
    }

    private function createCollaborationTables(): void
    {
        Schema::create('discussion_threads', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type', 96);
            $table->unsignedBigInteger('resource_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['resource_type', 'resource_id'], 'discussion_threads_resource_unique');
        });

        Schema::create('discussion_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('discussion_threads')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('discussion_comments')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'created_at'], 'discussion_comments_thread_created_index');
        });

        Schema::create('comment_mentions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->constrained('discussion_comments')->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['comment_id', 'mentioned_user_id'], 'comment_mentions_comment_user_unique');
        });

        Schema::create('resource_followers', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type', 96);
            $table->unsignedBigInteger('resource_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['resource_type', 'resource_id', 'user_id'], 'resource_followers_resource_user_unique');
        });
    }

    private function createAccountabilityAndReferralTables(): void
    {
        Schema::create('accountability_cases', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->string('category', 64);
            $table->string('channel', 32)->default('internal');
            $table->string('status', 32)->default('received');
            $table->text('summary');
            $table->string('preferred_contact_method', 32)->nullable();
            $table->string('language_preference', 8)->default('ar');
            $table->boolean('is_sensitive')->default(false);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at'], 'accountability_cases_status_created_index');
            $table->index(['assigned_to', 'status'], 'accountability_cases_assignee_status_index');
        });

        Schema::create('accountability_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('accountability_cases')->cascadeOnDelete();
            $table->string('action', 96);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->timestamp('created_at');

            $table->index(['case_id', 'created_at'], 'accountability_events_case_created_index');
        });

        Schema::create('referrals', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->string('source_type', 96);
            $table->unsignedBigInteger('source_id');
            $table->string('destination_type', 96);
            $table->unsignedBigInteger('destination_id');
            $table->text('reason');
            $table->string('status', 32)->default('draft');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->text('outcome')->nullable();
            $table->text('closure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_at'], 'referrals_status_due_index');
            $table->index(['source_type', 'source_id'], 'referrals_source_index');
            $table->index(['assigned_to', 'status'], 'referrals_assignee_status_index');
        });

        Schema::create('referral_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('referral_id')->constrained('referrals')->cascadeOnDelete();
            $table->string('action', 96);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at');

            $table->index(['referral_id', 'created_at'], 'referral_events_referral_created_index');
        });
    }

    private function createCommunicationAndAutomationTables(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 48);
            $table->string('channel', 32);
            $table->boolean('enabled')->default(true);
            $table->string('digest', 16)->default('instant');
            $table->string('quiet_start', 5)->nullable();
            $table->string('quiet_end', 5)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'category', 'channel'], 'notification_preferences_user_category_channel_unique');
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 32);
            $table->string('provider', 96)->default('none');
            $table->string('status', 32)->default('pending');
            $table->string('provider_reference', 255)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('error_code', 96)->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'notification_deliveries_user_status_index');
            $table->index(['status', 'created_at'], 'notification_deliveries_status_created_index');
        });

        Schema::create('automation_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 96)->unique();
            $table->string('category', 48)->default('generic');
            $table->string('trigger_event', 96);
            $table->boolean('enabled')->default(false);
            $table->text('conditions');
            $table->text('actions');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedTinyInteger('retry_limit')->default(3);
            $table->unsignedSmallInteger('timeout_seconds')->default(20);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enabled', 'trigger_event'], 'automation_definitions_enabled_trigger_index');
        });

        Schema::create('automation_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('definition_id')->nullable()->constrained('automation_definitions')->nullOnDelete();
            $table->unsignedInteger('definition_version');
            $table->string('trigger_event', 96);
            $table->string('subject_type', 96)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('idempotency_key', 128)->unique();
            $table->string('status', 32)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->text('context')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'automation_executions_status_created_index');
            $table->index(['definition_id', 'created_at'], 'automation_executions_definition_created_index');
        });
    }

    private function createDocumentReviewTables(): void
    {
        Schema::create('document_reviews', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->foreignId('document_link_id')->nullable()->constrained('document_links')->nullOnDelete();
            $table->string('subject_type', 96)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('outcome_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'reviewer_id'], 'document_reviews_status_reviewer_index');
            $table->index(['subject_type', 'subject_id'], 'document_reviews_subject_index');
        });

        Schema::create('document_review_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_id')->constrained('document_reviews')->cascadeOnDelete();
            $table->string('action', 96);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('created_at');

            $table->index(['review_id', 'created_at'], 'document_review_events_review_created_index');
        });
    }

    private function createIntegrationTables(): void
    {
        Schema::create('integration_registries', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 96)->unique();
            $table->string('name', 255);
            $table->string('provider', 96);
            $table->string('category', 48);
            $table->text('metadata')->nullable();
            $table->boolean('enabled')->default(false);
            $table->string('health_status', 32)->default('unknown');
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enabled', 'category'], 'integration_registries_enabled_category_index');
        });

        Schema::create('webhook_endpoints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('integration_registries')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('url');
            $table->text('events');
            $table->text('secret_encrypted');
            $table->boolean('active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamps();

            $table->index(['integration_id', 'active'], 'webhook_endpoints_integration_active_index');
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->string('event', 96);
            $table->unsignedTinyInteger('payload_version')->default(1);
            $table->string('payload_hash', 128);
            $table->string('status', 32)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->string('error_code', 96)->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['endpoint_id', 'status'], 'webhook_deliveries_endpoint_status_index');
            $table->index(['status', 'next_attempt_at'], 'webhook_deliveries_status_next_attempt_index');
        });

        Schema::create('incoming_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('integration_registries')->cascadeOnDelete();
            $table->string('event', 96);
            $table->string('nonce', 128)->unique();
            $table->string('payload_hash', 128);
            $table->boolean('processed')->default(false);
            $table->timestamp('received_at');
            $table->timestamp('created_at');
        });
    }
};
