<?php

declare(strict_types=1);

return [
    'created' => 'Created successfully.',
    'updated' => 'Saved successfully.',
    'archived' => 'Record archived.',
    'transitioned' => 'Status updated.',
    'assigned' => 'Assignment updated.',
    'checklist_added' => 'Checklist item added.',
    'dependency_added' => 'Dependency added.',
    'comment_added' => 'Comment added.',
    'followed' => 'You are now following this record.',
    'unfollowed' => 'Follow removed.',

    'case_created' => 'Case recorded with a safe reference number.',
    'referral_created' => 'Referral created with a safe reference number.',
    'notification_preferences_saved' => 'Notification preferences saved.',
    'notification_marked_read' => 'Notification marked as read.',
    'notifications_marked_read' => 'Notifications marked as read.',

    'automation_created' => 'Automation rule created and disabled until explicitly enabled.',
    'automation_updated' => 'Automation rule updated.',
    'automation_toggled' => 'Automation state updated.',
    'automation_retried' => 'Execution re-queued.',
    'automation_failed' => 'Automation failed safely.',

    'document_submitted' => 'Document submitted for technical review.',
    'document_assigned' => 'Reviewer assigned.',
    'integration_created' => 'Integration registered and disabled by default.',
    'integration_updated' => 'Integration configuration updated.',
    'endpoint_created' => 'Webhook endpoint registered but inactive until verified.',
    'webhook_retried' => 'Webhook delivery re-queued.',
    'webhook_delivered' => 'Webhook delivered.',
    'webhook_failed' => 'Webhook delivery failed.',

    'invalid_transition' => 'That status transition is not allowed.',
    'governance_disabled' => 'This capability is pending documented institutional authority.',
    'sensitive_category_blocked' => 'This category requires a governance and privacy decision before activation.',
    'privacy_notice' => 'Counts and aggregates only; no personal data or beneficiary names are shown.',
    'approval_notice' => 'Technical step only; not institutional approval. Formal authority is pending documentation.',
    'no_permission' => 'You do not have permission to access this record.',
    'webhook_signature_invalid' => 'Invalid webhook signature.',
    'webhook_replay' => 'Replayed request rejected.',
    'webhook_disabled' => 'Inbound webhooks are not enabled.',
    'ssrf_blocked' => 'Destination rejected for security reasons.',
    'calendar_empty' => 'No authorized events in this range.',
    'not_found' => 'Not found.',
];
