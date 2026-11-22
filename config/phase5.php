<?php

declare(strict_types=1);
use App\Services\Phase5\DisabledNotificationAdapter;
use App\Services\Phase5\MailNotificationAdapter;

/**
 * Phase 5 institutional operating layer contract.
 *
 * This file is the code-owned boundary for workflows, tasks, accountability,
 * communications, automation, calendar aggregation, document review and
 * integrations. It deliberately contains no institutional authority claims:
 * approval statuses, approver roles, delegations and officeholders are absent
 * and all high-risk routes fail closed while their governance gates are off.
 */
return [
    'gates' => [
        'institutional_approvals_enabled' => env('PHASE5_INSTITUTIONAL_APPROVALS_ENABLED', false),
        'beneficiary_records_enabled' => env('PHASE5_BENEFICIARY_RECORDS_ENABLED', false),
        'sensitive_feedback_categories_enabled' => env('PHASE5_SENSITIVE_FEEDBACK_CATEGORIES_ENABLED', false),
        'complaint_public_tracking_enabled' => env('PHASE5_COMPLAINT_PUBLIC_TRACKING_ENABLED', false),
        'external_channels_enabled' => env('PHASE5_EXTERNAL_CHANNELS_ENABLED', false),
        'webhooks_enabled' => env('PHASE5_WEBHOOKS_ENABLED', false),
    ],

    'governance' => [
        // OQ-49: technical workflow capability is never institutional approval.
        'approval_action_allowed' => false,
        'approval_statuses' => [],
        'auto_publish_allowed' => false,
        'approval_notice' => [
            'ar' => 'هذهخطوة تقنية فقط ولا تمثل موافقة مؤسسية؛ الموافقة الرسمية معلقة حتى توثيق الصلاحية.',
            'en' => 'This is a technical step only and does not represent institutional approval; formal authority is pending documentation.',
        ],
    ],

    'timezone' => env('PHASE5_TIMEZONE', 'Asia/Gaza'),

    'workflow' => [
        'statuses' => ['draft', 'active', 'in_progress', 'waiting', 'completed', 'rejected', 'cancelled', 'suspended', 'archived'],
        'transitions' => [
            'draft' => ['active', 'cancelled', 'archived'],
            'active' => ['in_progress', 'waiting', 'suspended', 'cancelled'],
            'in_progress' => ['waiting', 'completed', 'rejected', 'suspended', 'cancelled'],
            'waiting' => ['in_progress', 'completed', 'rejected', 'suspended', 'cancelled'],
            'suspended' => ['in_progress', 'waiting', 'cancelled'],
            'rejected' => ['draft', 'archived'],
            'completed' => ['archived'],
            'cancelled' => ['archived'],
            'archived' => [],
        ],
        'step_statuses' => ['pending', 'in_progress', 'waiting', 'completed', 'rejected', 'skipped', 'cancelled'],
        'step_transitions' => [
            'pending' => ['in_progress', 'skipped', 'cancelled'],
            'in_progress' => ['waiting', 'completed', 'rejected', 'cancelled'],
            'waiting' => ['in_progress', 'completed', 'rejected', 'cancelled'],
            'completed' => [],
            'rejected' => [],
            'skipped' => [],
            'cancelled' => [],
        ],
        'categories' => ['generic', 'task_follow_up', 'document_review', 'data_quality', 'service_delivery'],
        'triggers' => ['manual', 'task_created', 'document_submitted', 'record_status_changed', 'scheduled_review'],
        'assignment_types' => ['user', 'role', 'owner', 'unassigned'],
        'max_steps' => 25,
        'max_version_config_bytes' => 65536,
        'allowed_step_keys' => [
            'key', 'label', 'label_ar', 'label_en', 'order', 'assignment_type', 'assignee_id',
            'role_key', 'due_days', 'due_at', 'completion_condition', 'notification_event',
            'allow_rejection', 'allow_return', 'requires_comment', 'metadata',
        ],
        'allowed_condition_keys' => ['field', 'operator', 'value'],
    ],

    'task' => [
        'statuses' => ['backlog', 'to_do', 'in_progress', 'blocked', 'under_review', 'completed', 'cancelled'],
        'transitions' => [
            'backlog' => ['to_do', 'cancelled'],
            'to_do' => ['in_progress', 'blocked', 'cancelled'],
            'in_progress' => ['blocked', 'under_review', 'completed', 'cancelled'],
            'blocked' => ['to_do', 'in_progress', 'cancelled'],
            'under_review' => ['in_progress', 'completed', 'cancelled'],
            'completed' => ['in_progress'],
            'cancelled' => [],
        ],
        'priorities' => ['low', 'normal', 'high', 'urgent'],
        'linkable_resources' => [
            'task', 'workflow_instance', 'program', 'project', 'activity', 'operational_record',
            'document_review', 'accountability_case', 'referral',
        ],
        'max_checklist_items' => 100,
        'max_collaborators' => 20,
        'max_description_chars' => 10000,
    ],

    'collaboration' => [
        'resource_permissions' => [
            'task' => 'tasks.view',
            'workflow_instance' => 'workflows.view',
            'document_review' => 'documents.review',
            'accountability_case' => 'accountability.view',
            'referral' => 'referrals.view',
            'operational_record' => 'reports.view',
            'project' => 'projects.view',
        ],
        'commentable_resources' => ['task', 'workflow_instance', 'document_review', 'accountability_case', 'referral'],
        'max_comment_chars' => 5000,
        'edit_window_minutes' => 15,
        'max_mentions_per_comment' => 10,
    ],

    'accountability' => [
        'enabled_categories' => [
            'general_feedback' => ['sensitive' => false],
            'service_quality' => ['sensitive' => false],
            'project_feedback' => ['sensitive' => false],
            'accessibility_concern' => ['sensitive' => false],
        ],
        'disabled_categories' => [
            'staff_conduct' => ['blocking_gate' => 'OQ-29'],
            'safeguarding_concern' => ['blocking_gate' => 'OQ-29'],
            'assistance_related' => ['blocking_gate' => 'OQ-29'],
        ],
        'channels' => ['internal', 'website'],
        'statuses' => ['received', 'acknowledged', 'under_triage', 'assigned', 'under_review', 'waiting_information', 'referred', 'resolved', 'closed', 'reopened'],
        'transitions' => [
            'received' => ['acknowledged', 'under_triage', 'closed'],
            'acknowledged' => ['under_triage', 'assigned', 'closed'],
            'under_triage' => ['assigned', 'waiting_information', 'referred', 'closed'],
            'assigned' => ['under_review', 'waiting_information', 'referred', 'closed'],
            'under_review' => ['waiting_information', 'referred', 'resolved', 'closed'],
            'waiting_information' => ['under_review', 'closed'],
            'referred' => ['under_review', 'resolved', 'closed'],
            'resolved' => ['closed', 'reopened'],
            'reopened' => ['under_triage', 'assigned', 'closed'],
            'closed' => [],
        ],
        'public_tracking_fields' => ['reference', 'status', 'updated_at'],
        'max_summary_chars' => 4000,
        'contact_details_enabled' => false,
        'attachments_enabled' => true,
        'anonymous_submission_enabled' => false,
    ],

    'referral' => [
        'statuses' => ['draft', 'submitted', 'accepted', 'rejected', 'in_progress', 'waiting', 'completed', 'closed', 'cancelled'],
        'transitions' => [
            'draft' => ['submitted', 'cancelled'],
            'submitted' => ['accepted', 'rejected', 'cancelled'],
            'accepted' => ['in_progress', 'cancelled'],
            'rejected' => ['closed'],
            'in_progress' => ['waiting', 'completed', 'cancelled'],
            'waiting' => ['in_progress', 'completed', 'cancelled'],
            'completed' => ['closed'],
            'closed' => [],
            'cancelled' => [],
        ],
        // Only source types with a concrete backing table are accepted.
        'source_types' => ['project', 'activity', 'accountability_case'],
        'destination_types' => ['partner', 'internal_team', 'service'],
        'max_reason_chars' => 4000,
        'allow_personal_data' => false,
    ],

    'notification' => [
        'categories' => ['task', 'workflow', 'collaboration', 'document', 'accountability', 'referral', 'automation', 'integration', 'system'],
        'priorities' => ['low', 'normal', 'high', 'urgent'],
        'delivery_statuses' => ['pending', 'queued', 'sending', 'sent', 'delivered', 'failed', 'unknown', 'disabled'],
        'channels' => ['database', 'mail', 'sms', 'whatsapp'],
        'default_preferences' => ['database' => true, 'mail' => true, 'sms' => false, 'whatsapp' => false],
        'mandatory_categories' => ['system'],
        'digest_options' => ['instant', 'daily', 'weekly'],
        'quiet_hours_enabled' => true,
        'safe_preview_max_chars' => 160,
        'adapters' => [
            'mail' => MailNotificationAdapter::class,
            'sms' => DisabledNotificationAdapter::class,
            'whatsapp' => DisabledNotificationAdapter::class,
        ],
    ],

    'automation' => [
        'operators' => [
            'equals' => ['string', 'number', 'boolean'],
            'not_equals' => ['string', 'number', 'boolean'],
            'contains' => ['string'],
            'is_empty' => ['string', 'date', 'number'],
            'is_not_empty' => ['string', 'date', 'number'],
            'greater_than' => ['number', 'date'],
            'less_than' => ['number', 'date'],
            'date_before' => ['date'],
            'date_after' => ['date'],
            'in_list' => ['string'],
        ],
        'fields' => [
            'task.status' => ['type' => 'string', 'values' => ['backlog', 'to_do', 'in_progress', 'blocked', 'under_review', 'completed', 'cancelled']],
            'task.priority' => ['type' => 'string', 'values' => ['low', 'normal', 'high', 'urgent']],
            'task.due_at' => ['type' => 'date'],
            'workflow.status' => ['type' => 'string', 'values' => ['draft', 'active', 'in_progress', 'waiting', 'completed', 'rejected', 'cancelled', 'suspended', 'archived']],
            'workflow.due_at' => ['type' => 'date'],
            'document_review.status' => ['type' => 'string', 'values' => ['draft', 'submitted', 'under_review', 'changes_requested', 'resubmitted', 'reviewed', 'rejected', 'archived']],
        ],
        'actions' => [
            'create_notification' => ['category', 'priority', 'title_key', 'preview_key'],
            'create_task' => ['title', 'priority', 'due_days'],
            'add_internal_comment' => ['body'],
            'log_audit' => ['action'],
        ],
        'triggers' => [
            'task.created', 'task.assigned', 'task.status_changed', 'task.due_soon', 'task.overdue',
            'workflow.created', 'workflow.step_assigned', 'workflow.status_changed',
            'workflow.step_due_soon', 'workflow.step_overdue',
            'referral.due_soon', 'referral.overdue',
            'document.submitted', 'document.status_changed',
            'accountability.created', 'referral.status_changed', 'integration.failed', 'automation.failed',
        ],
        'max_conditions' => 10,
        'max_actions' => 5,
        'max_retries' => 3,
        'timeout_seconds' => 20,
        'dead_letter_after_attempts' => 3,
    ],

    'calendar' => [
        'sources' => [
            'task' => ['permission' => 'tasks.view', 'table' => 'tasks', 'date_column' => 'due_at', 'label_key' => 'task'],
            'workflow_step' => ['permission' => 'workflows.view', 'table' => 'workflow_steps', 'date_column' => 'due_at', 'label_key' => 'workflow_step'],
            'referral' => ['permission' => 'referrals.view', 'table' => 'referrals', 'date_column' => 'due_at', 'label_key' => 'referral'],
            'activity' => ['permission' => 'activities.view', 'table' => 'activities', 'date_column' => 'planned_start', 'label_key' => 'activity'],
            'meeting' => ['permission' => 'committees.view', 'table' => 'committee_meetings', 'date_column' => 'meeting_date', 'label_key' => 'meeting'],
        ],
        'views' => ['month', 'week', 'agenda'],
        'max_events' => 500,
    ],

    'document_review' => [
        'statuses' => ['draft', 'submitted', 'under_review', 'changes_requested', 'resubmitted', 'reviewed', 'rejected', 'archived'],
        'transitions' => [
            'draft' => ['submitted', 'archived'],
            'submitted' => ['under_review', 'changes_requested', 'rejected', 'archived'],
            'under_review' => ['changes_requested', 'reviewed', 'rejected'],
            'changes_requested' => ['resubmitted', 'archived'],
            'resubmitted' => ['under_review', 'changes_requested', 'rejected'],
            'reviewed' => ['archived'],
            'rejected' => ['archived'],
            'archived' => [],
        ],
        'technical_outcomes' => ['reviewed', 'changes_requested', 'rejected'],
        'approval_outcomes' => [],
        'max_review_chars' => 5000,
    ],

    'integrations' => [
        'categories' => ['communication', 'reporting', 'partner_system', 'utility'],
        'webhook_events' => [
            'task.created', 'task.assigned', 'task.status_changed',
            'workflow.created', 'workflow.status_changed',
            'workflow.step_due_soon', 'workflow.step_overdue',
            'referral.due_soon', 'referral.overdue',
            'document.submitted', 'document.status_changed',
            'accountability.created', 'referral.status_changed',
            'automation.failed', 'integration.failed',
        ],
        'payload_versions' => [1],
        'allowed_schemes' => ['https'],
        'connect_timeout_seconds' => 3,
        'timeout_seconds' => 8,
        'max_retries' => 3,
        'max_payload_bytes' => 262144,
        'inbound_timestamp_tolerance_seconds' => 300,
        'allowed_private_hosts' => [],
        'secret_min_length' => 32,
    ],

    'dashboard' => [
        'sections' => [
            'workflows' => 'workflows.view',
            'tasks' => 'tasks.view',
            'documents' => 'documents.review',
            'accountability' => 'accountability.view',
            'referrals' => 'referrals.view',
            'automation' => 'automations.view',
            'integrations' => 'integrations.view',
            'notifications' => 'notifications.view',
        ],
        'max_activity' => 25,
        'cache_ttl_seconds' => 0,
    ],
];
