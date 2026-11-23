<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Permission catalog
|--------------------------------------------------------------------------
|
| This catalog is the code-owned source of truth for known permissions. The
| `pcdr:permissions:sync` command inserts missing keys into the database and
| marks keys removed from code as retired. Custom permissions created by the
| Platform Owner remain fully dynamic and are managed in the database.
|
| "protected" permissions may not be renamed or deleted. They remain
| assignable by the Platform Owner, who is the technical authority for the
| platform. Institutional approval permissions are never seeded or assigned.
|
*/

return [
    // Identity and access management.
    'users.view' => ['module' => 'iam', 'label' => 'View users', 'protected' => true],
    'users.create' => ['module' => 'iam', 'label' => 'Create users', 'protected' => true],
    'users.update' => ['module' => 'iam', 'label' => 'Update users', 'protected' => true],
    'users.delete' => ['module' => 'iam', 'label' => 'Delete users', 'protected' => true],
    'users.assign_roles' => ['module' => 'iam', 'label' => 'Assign user roles', 'protected' => true],
    'users.reset_password' => ['module' => 'iam', 'label' => 'Reset user passwords', 'protected' => true],

    'roles.view' => ['module' => 'iam', 'label' => 'View roles', 'protected' => true],
    'roles.create' => ['module' => 'iam', 'label' => 'Create roles', 'protected' => true],
    'roles.update' => ['module' => 'iam', 'label' => 'Update roles', 'protected' => true],
    'roles.delete' => ['module' => 'iam', 'label' => 'Delete roles', 'protected' => true],
    'roles.grant_protected' => ['module' => 'iam', 'label' => 'Grant protected permissions', 'protected' => true],

    'permissions.view' => ['module' => 'iam', 'label' => 'View permissions', 'protected' => true],
    'permissions.create' => ['module' => 'iam', 'label' => 'Create permissions', 'protected' => true],
    'permissions.update' => ['module' => 'iam', 'label' => 'Update permissions', 'protected' => true],
    'permissions.delete' => ['module' => 'iam', 'label' => 'Delete permissions', 'protected' => true],

    // Platform infrastructure.
    'settings.view' => ['module' => 'platform', 'label' => 'View settings', 'protected' => true],
    'settings.update' => ['module' => 'platform', 'label' => 'Update settings', 'protected' => true],
    'audit_logs.view' => ['module' => 'platform', 'label' => 'View audit logs', 'protected' => true],
    'audit_logs.export' => ['module' => 'platform', 'label' => 'Export audit logs', 'protected' => true],

    // Reserved workflow permissions. They are intentionally not seeded into
    // any role and require the Platform Owner's explicit technical decision.
    'content.view' => ['module' => 'content', 'label' => 'View content', 'protected' => false],
    'content.create' => ['module' => 'content', 'label' => 'Create content', 'protected' => false],
    'content.update' => ['module' => 'content', 'label' => 'Update content', 'protected' => false],
    'content.review' => ['module' => 'content', 'label' => 'Review content', 'protected' => false],
    'content.publish' => ['module' => 'content', 'label' => 'Publish content', 'protected' => false],
    'content.archive' => ['module' => 'content', 'label' => 'Archive content', 'protected' => false],
    'inquiries.update' => ['module' => 'content', 'label' => 'Update inquiry status', 'protected' => false],

    /*
     * Phase 4 operational permissions. These are technical/operational
     * capabilities only. No `*.approve` or institutional delegation keys are
     * defined, seeded, or assignable by this phase.
     */
    'locations.view' => ['module' => 'operations', 'label' => 'View geographic locations', 'protected' => false],
    'locations.create' => ['module' => 'operations', 'label' => 'Create geographic locations', 'protected' => false],
    'locations.update' => ['module' => 'operations', 'label' => 'Update geographic locations', 'protected' => false],
    'locations.transition' => ['module' => 'operations', 'label' => 'Change geographic location status', 'protected' => false],

    'interventions.view' => ['module' => 'operations', 'label' => 'View interventions', 'protected' => false],
    'interventions.create' => ['module' => 'operations', 'label' => 'Create interventions', 'protected' => false],
    'interventions.update' => ['module' => 'operations', 'label' => 'Update interventions', 'protected' => false],
    'interventions.transition' => ['module' => 'operations', 'label' => 'Change intervention status', 'protected' => false],

    'activities.view' => ['module' => 'operations', 'label' => 'View activities', 'protected' => false],
    'activities.create' => ['module' => 'operations', 'label' => 'Create activities', 'protected' => false],
    'activities.update' => ['module' => 'operations', 'label' => 'Update activities', 'protected' => false],
    'activities.transition' => ['module' => 'operations', 'label' => 'Change activity status', 'protected' => false],

    'distributions.view' => ['module' => 'operations', 'label' => 'View distributions', 'protected' => false],
    'distributions.create' => ['module' => 'operations', 'label' => 'Create distributions', 'protected' => false],
    'distributions.update' => ['module' => 'operations', 'label' => 'Update distributions', 'protected' => false],
    'distributions.transition' => ['module' => 'operations', 'label' => 'Change distribution status', 'protected' => false],
    'distributions.reconcile' => ['module' => 'operations', 'label' => 'Reconcile distributions', 'protected' => false],

    'inventory.view' => ['module' => 'operations', 'label' => 'View inventory', 'protected' => false],
    'inventory.create' => ['module' => 'operations', 'label' => 'Create inventory master data', 'protected' => false],
    'inventory.update' => ['module' => 'operations', 'label' => 'Update inventory master data', 'protected' => false],
    'inventory.transition' => ['module' => 'operations', 'label' => 'Change inventory status', 'protected' => false],
    'inventory.receive' => ['module' => 'operations', 'label' => 'Receive stock', 'protected' => false],
    'inventory.issue' => ['module' => 'operations', 'label' => 'Issue stock', 'protected' => false],
    'inventory.transfer' => ['module' => 'operations', 'label' => 'Transfer stock', 'protected' => false],
    'inventory.adjust' => ['module' => 'operations', 'label' => 'Adjust stock', 'protected' => false],
    'inventory.reverse' => ['module' => 'operations', 'label' => 'Reverse stock movements', 'protected' => false],

    'volunteers.view' => ['module' => 'operations', 'label' => 'View volunteer program records', 'protected' => false],
    'volunteers.create' => ['module' => 'operations', 'label' => 'Create volunteer program records', 'protected' => false],
    'volunteers.update' => ['module' => 'operations', 'label' => 'Update volunteer program records', 'protected' => false],
    'volunteers.transition' => ['module' => 'operations', 'label' => 'Change volunteer record status', 'protected' => false],

    'committees.view' => ['module' => 'operations', 'label' => 'View community participation records', 'protected' => false],
    'committees.create' => ['module' => 'operations', 'label' => 'Create community participation records', 'protected' => false],
    'committees.update' => ['module' => 'operations', 'label' => 'Update community participation records', 'protected' => false],
    'committees.transition' => ['module' => 'operations', 'label' => 'Change community record status', 'protected' => false],

    'partners.view' => ['module' => 'operations', 'label' => 'View partner and donor relationships', 'protected' => false],
    'partners.create' => ['module' => 'operations', 'label' => 'Create partner and donor records', 'protected' => false],
    'partners.update' => ['module' => 'operations', 'label' => 'Update partner and donor records', 'protected' => false],
    'partners.transition' => ['module' => 'operations', 'label' => 'Change relationship status', 'protected' => false],

    'partner_contacts.view' => ['module' => 'operations', 'label' => 'View restricted partner contacts', 'protected' => false],
    'partner_contacts.create' => ['module' => 'operations', 'label' => 'Create restricted partner contacts', 'protected' => false],
    'partner_contacts.update' => ['module' => 'operations', 'label' => 'Update restricted partner contacts', 'protected' => false],
    'partner_contacts.transition' => ['module' => 'operations', 'label' => 'Change partner contact status', 'protected' => false],

    'grants.view' => ['module' => 'operations', 'label' => 'View grants and proposals', 'protected' => false],
    'grants.create' => ['module' => 'operations', 'label' => 'Create grants and proposals', 'protected' => false],
    'grants.update' => ['module' => 'operations', 'label' => 'Update grants and proposals', 'protected' => false],
    'grants.transition' => ['module' => 'operations', 'label' => 'Change grant and proposal status', 'protected' => false],
    'grants.submit' => ['module' => 'operations', 'label' => 'Record proposal submission evidence', 'protected' => false],

    'meal.view' => ['module' => 'operations', 'label' => 'View MEAL records', 'protected' => false],
    'meal.create' => ['module' => 'operations', 'label' => 'Create MEAL records', 'protected' => false],
    'meal.update' => ['module' => 'operations', 'label' => 'Update MEAL records', 'protected' => false],
    'meal.transition' => ['module' => 'operations', 'label' => 'Change MEAL status', 'protected' => false],
    'meal.verify' => ['module' => 'operations', 'label' => 'Verify MEAL measurements', 'protected' => false],

    'assessments.view' => ['module' => 'operations', 'label' => 'View assessments and research', 'protected' => false],
    'assessments.create' => ['module' => 'operations', 'label' => 'Create assessments and research', 'protected' => false],
    'assessments.update' => ['module' => 'operations', 'label' => 'Update assessments and research', 'protected' => false],
    'assessments.transition' => ['module' => 'operations', 'label' => 'Change assessment status', 'protected' => false],

    'reports.view' => ['module' => 'operations', 'label' => 'View operational reports and dashboards', 'protected' => false],
    'reports.create' => ['module' => 'operations', 'label' => 'Generate operational reports', 'protected' => false],
    'reports.update' => ['module' => 'operations', 'label' => 'Update operational report records', 'protected' => false],
    'reports.export' => ['module' => 'operations', 'label' => 'Export operational reports', 'protected' => false],

    'documents.view' => ['module' => 'operations', 'label' => 'View operational document links', 'protected' => false],
    'documents.upload' => ['module' => 'operations', 'label' => 'Attach operational documents', 'protected' => false],
    'documents.download' => ['module' => 'operations', 'label' => 'Download operational documents', 'protected' => false],

    /*
     * Phase 5 institutional operating layer. These are technical capabilities
     * only. No `*.approve` key is defined or seeded: OQ-49 remains pending
     * formal authority and delegation. Sensitive accountability capabilities
     * are separately gated and are not assigned to any default role.
     */
    'workflows.view' => ['module' => 'phase5', 'label' => 'View workflows', 'protected' => false],
    'workflows.create' => ['module' => 'phase5', 'label' => 'Configure workflows', 'protected' => false],
    'workflows.update' => ['module' => 'phase5', 'label' => 'Update workflow definitions', 'protected' => false],
    'workflows.transition' => ['module' => 'phase5', 'label' => 'Advance workflow instances', 'protected' => false],

    'tasks.view' => ['module' => 'phase5', 'label' => 'View tasks', 'protected' => false],
    'tasks.create' => ['module' => 'phase5', 'label' => 'Create tasks', 'protected' => false],
    'tasks.update' => ['module' => 'phase5', 'label' => 'Update tasks', 'protected' => false],
    'tasks.delete' => ['module' => 'phase5', 'label' => 'Archive tasks', 'protected' => false],
    'tasks.assign' => ['module' => 'phase5', 'label' => 'Assign tasks', 'protected' => false],
    'tasks.transition' => ['module' => 'phase5', 'label' => 'Change task status', 'protected' => false],

    'collaboration.view' => ['module' => 'phase5', 'label' => 'View internal discussions', 'protected' => false],
    'collaboration.comment' => ['module' => 'phase5', 'label' => 'Add internal comments', 'protected' => false],
    'collaboration.mention' => ['module' => 'phase5', 'label' => 'Mention colleagues', 'protected' => false],
    'collaboration.moderate' => ['module' => 'phase5', 'label' => 'Moderate internal discussions', 'protected' => false],

    'accountability.create' => ['module' => 'phase5', 'label' => 'Submit feedback through authorized channels', 'protected' => false],
    'accountability.view' => ['module' => 'phase5', 'label' => 'View accountability cases', 'protected' => false],
    'accountability.update' => ['module' => 'phase5', 'label' => 'Update accountability cases', 'protected' => false],
    'accountability.assign' => ['module' => 'phase5', 'label' => 'Assign accountability cases', 'protected' => false],
    'accountability.view_sensitive' => ['module' => 'phase5', 'label' => 'View sensitive accountability notes', 'protected' => false],

    'referrals.view' => ['module' => 'phase5', 'label' => 'View referrals', 'protected' => false],
    'referrals.create' => ['module' => 'phase5', 'label' => 'Create non-identifying referrals', 'protected' => false],
    'referrals.update' => ['module' => 'phase5', 'label' => 'Update referrals', 'protected' => false],
    'referrals.transition' => ['module' => 'phase5', 'label' => 'Change referral status', 'protected' => false],

    'notifications.view' => ['module' => 'phase5', 'label' => 'View notification inbox', 'protected' => false],
    'notifications.preferences' => ['module' => 'phase5', 'label' => 'Manage notification preferences', 'protected' => false],

    'automations.view' => ['module' => 'phase5', 'label' => 'View automation rules', 'protected' => false],
    'automations.create' => ['module' => 'phase5', 'label' => 'Configure automation rules', 'protected' => false],
    'automations.update' => ['module' => 'phase5', 'label' => 'Update automation rules', 'protected' => false],
    'automations.execute' => ['module' => 'phase5', 'label' => 'Run or retry automation executions', 'protected' => false],

    'calendar.view' => ['module' => 'phase5', 'label' => 'View institutional calendar', 'protected' => false],

    'documents.review' => ['module' => 'phase5', 'label' => 'Review routed documents', 'protected' => false],
    'documents.submit_review' => ['module' => 'phase5', 'label' => 'Submit documents for technical review', 'protected' => false],

    'integrations.view' => ['module' => 'phase5', 'label' => 'View integration registry', 'protected' => false],
    'integrations.create' => ['module' => 'phase5', 'label' => 'Register integrations', 'protected' => false],
    'integrations.update' => ['module' => 'phase5', 'label' => 'Update integration configuration', 'protected' => false],
    'integrations.retry' => ['module' => 'phase5', 'label' => 'Retry webhook deliveries', 'protected' => false],

    'dashboards.view' => ['module' => 'phase5', 'label' => 'View institutional operations dashboard', 'protected' => false],
];
