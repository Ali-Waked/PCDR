<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dashboard feature registry
|--------------------------------------------------------------------------
| Single source of truth for which modules may appear in the delivered
| dashboard. This registry is intentionally small and dependency-free.
|
| States:
|   enabled - visible and available in the delivered CMS dashboard.
|   hidden  - preserved in code and database, but hidden and unavailable to
|             ordinary dashboard users.
|   reserved - future capability with no dashboard surface yet.
|
| Hidden operational modules are NOT part of the USD 100 website delivery.
| Their source, migrations, routes and data are preserved. Future activation
| requires an intentional developer-level configuration change (or a
| separately authorized project), never a dashboard control.
|
*/

return [
    'states' => [
        'enabled' => 'Enabled and visible',
        'hidden' => 'Disabled and hidden',
        'reserved' => 'Reserved for future activation',
    ],

    'features' => [
        'overview' => [
            'state' => 'enabled',
            'description' => 'Website management overview.',
        ],
        'content' => [
            'state' => 'enabled',
            'description' => 'Public website and CMS content management.',
        ],
        'engagement' => [
            'state' => 'enabled',
            'description' => 'Contact inquiries and email templates.',
        ],
        'access' => [
            'state' => 'enabled',
            'description' => 'Website users, roles and permissions.',
        ],
        'operations' => [
            'state' => 'hidden',
            'path_prefix' => 'operations',
            'description' => 'Phase 4 humanitarian operations (preserved, not in scope).',
            'legacy_modules' => ['operations'],
            'legacy_permissions' => [
                'locations.view', 'interventions.view', 'activities.view',
                'distributions.view', 'inventory.view', 'volunteers.view',
                'committees.view', 'partners.view', 'partner_contacts.view',
                'grants.view', 'meal.view', 'assessments.view',
                'reports.view', 'documents.view',
            ],
        ],
        'phase5' => [
            'state' => 'hidden',
            'path_prefix' => 'operations/phase5',
            'description' => 'Phase 5 institutional operating layer (preserved, not in scope).',
            'legacy_modules' => ['phase5'],
            'legacy_permissions' => [
                'workflows.view', 'tasks.view', 'collaboration.view',
                'accountability.view', 'referrals.view', 'notifications.view',
                'automations.view', 'calendar.view', 'documents.review',
                'integrations.view', 'dashboards.view',
            ],
        ],
    ],

    /*
     * Developer-level override used only when intentionally restoring hidden
     * modules in a future project. There is no dashboard control for this.
     */
    'hidden_modules_access' => env('PCDR_HIDDEN_MODULES_ACCESS', false),
];
