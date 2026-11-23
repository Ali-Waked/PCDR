<?php

declare(strict_types=1);

/*
| Hidden administrative navigation definitions |
|
| Preserved verbatim for future restoration. Not loaded by the delivered
| dashboard. See config/dashboard_features.php and docs/architecture/WEBSITE_CMS_SCOPE.md. |
*/

return [
    'groups' => [
        [
            'key' => 'operations',
            'label' => 'operations',
            'items' => [
                ['key' => 'operations_dashboard', 'label' => 'operations_dashboard', 'path' => '/operations/dashboard', 'permission' => 'reports.view'],
                ['key' => 'reports', 'label' => 'reports', 'path' => '/operations/reports', 'permission' => 'reports.view'],
                ['key' => 'inventory', 'label' => 'inventory', 'path' => '/operations/inventory', 'permission' => 'inventory.view'],
                ['key' => 'locations', 'label' => 'locations', 'path' => '/operations/locations', 'permission' => 'locations.view'],
                ['key' => 'interventions', 'label' => 'interventions', 'path' => '/operations/interventions', 'permission' => 'interventions.view'],
                ['key' => 'activities', 'label' => 'activities', 'path' => '/operations/activities', 'permission' => 'activities.view'],
                ['key' => 'distribution_plans', 'label' => 'distribution_plans', 'path' => '/operations/distribution_plans', 'permission' => 'distributions.view'],
                ['key' => 'distribution_rounds', 'label' => 'distribution_rounds', 'path' => '/operations/distribution_rounds', 'permission' => 'distributions.view'],
                ['key' => 'distribution_records', 'label' => 'distribution_records', 'path' => '/operations/distribution_records', 'permission' => 'distributions.view'],
                ['key' => 'warehouses', 'label' => 'warehouses', 'path' => '/operations/warehouses', 'permission' => 'inventory.view'],
                ['key' => 'inventory_items', 'label' => 'inventory_items', 'path' => '/operations/inventory_items', 'permission' => 'inventory.view'],
                ['key' => 'volunteers', 'label' => 'volunteers', 'path' => '/operations/volunteers', 'permission' => 'volunteers.view'],
                ['key' => 'volunteer_teams', 'label' => 'volunteer_teams', 'path' => '/operations/volunteer_teams', 'permission' => 'volunteers.view'],
                ['key' => 'volunteer_assignments', 'label' => 'volunteer_assignments', 'path' => '/operations/volunteer_assignments', 'permission' => 'volunteers.view'],
                ['key' => 'volunteer_trainings', 'label' => 'volunteer_trainings', 'path' => '/operations/volunteer_trainings', 'permission' => 'volunteers.view'],
                ['key' => 'committees', 'label' => 'committees', 'path' => '/operations/committees', 'permission' => 'committees.view'],
                ['key' => 'committee_meetings', 'label' => 'committee_meetings', 'path' => '/operations/committee_meetings', 'permission' => 'committees.view'],
                ['key' => 'community_feedback', 'label' => 'community_feedback', 'path' => '/operations/community_feedback', 'permission' => 'committees.view'],
                ['key' => 'partners', 'label' => 'partners', 'path' => '/operations/partners', 'permission' => 'partners.view'],
                ['key' => 'partner_agreements', 'label' => 'partner_agreements', 'path' => '/operations/partner_agreements', 'permission' => 'partners.view'],
                ['key' => 'partner_contacts', 'label' => 'partner_contacts', 'path' => '/operations/partner_contacts', 'permission' => 'partner_contacts.view'],
                ['key' => 'grant_opportunities', 'label' => 'grant_opportunities', 'path' => '/operations/grant_opportunities', 'permission' => 'grants.view'],
                ['key' => 'grant_proposals', 'label' => 'grant_proposals', 'path' => '/operations/grant_proposals', 'permission' => 'grants.view'],
                ['key' => 'grant_awards', 'label' => 'grant_awards', 'path' => '/operations/grant_awards', 'permission' => 'grants.view'],
                ['key' => 'results_frameworks', 'label' => 'results_frameworks', 'path' => '/operations/results_frameworks', 'permission' => 'meal.view'],
                ['key' => 'reporting_periods', 'label' => 'reporting_periods', 'path' => '/operations/reporting_periods', 'permission' => 'meal.view'],
                ['key' => 'indicators', 'label' => 'indicators', 'path' => '/operations/indicators', 'permission' => 'meal.view'],
                ['key' => 'indicator_measurements', 'label' => 'indicator_measurements', 'path' => '/operations/indicator_measurements', 'permission' => 'meal.view'],
                ['key' => 'evaluations', 'label' => 'evaluations', 'path' => '/operations/evaluations', 'permission' => 'meal.view'],
                ['key' => 'corrective_actions', 'label' => 'corrective_actions', 'path' => '/operations/corrective_actions', 'permission' => 'meal.view'],
                ['key' => 'assessments', 'label' => 'assessments', 'path' => '/operations/assessments', 'permission' => 'assessments.view'],
                ['key' => 'data_collection_instruments', 'label' => 'data_collection_instruments', 'path' => '/operations/data_collection_instruments', 'permission' => 'assessments.view'],
                ['key' => 'operational_reports', 'label' => 'operational_reports', 'path' => '/operations/operational_reports', 'permission' => 'reports.view'],
            ],
        ],
        [
            'key' => 'phase5',
            'label' => 'phase5',
            'items' => [
                ['key' => 'phase5_dashboard', 'label' => 'phase5_dashboard', 'path' => '/operations/phase5/dashboard', 'permission' => 'dashboards.view'],
                ['key' => 'workflows', 'label' => 'workflows', 'path' => '/operations/phase5/workflows', 'permission' => 'workflows.view'],
                ['key' => 'tasks', 'label' => 'tasks', 'path' => '/operations/phase5/tasks', 'permission' => 'tasks.view'],
                ['key' => 'collaboration', 'label' => 'collaboration', 'path' => '/operations/phase5/discussions/workflow/1', 'permission' => 'collaboration.view'],
                ['key' => 'accountability', 'label' => 'accountability', 'path' => '/operations/phase5/accountability', 'permission' => 'accountability.view'],
                ['key' => 'referrals', 'label' => 'referrals', 'path' => '/operations/phase5/referrals', 'permission' => 'referrals.view'],
                ['key' => 'notifications', 'label' => 'notifications', 'path' => '/operations/phase5/notifications', 'permission' => 'notifications.view'],
                ['key' => 'automations', 'label' => 'automations', 'path' => '/operations/phase5/automations', 'permission' => 'automations.view'],
                ['key' => 'calendar', 'label' => 'calendar', 'path' => '/operations/phase5/calendar', 'permission' => 'calendar.view'],
                ['key' => 'document_reviews', 'label' => 'document_reviews', 'path' => '/operations/phase5/document-reviews', 'permission' => 'documents.review'],
                ['key' => 'integrations', 'label' => 'integrations', 'path' => '/operations/phase5/integrations', 'permission' => 'integrations.view'],
                ['key' => 'webhooks', 'label' => 'webhooks', 'path' => '/operations/phase5/webhooks/endpoints', 'permission' => 'integrations.view'],
                ['key' => 'activity', 'label' => 'activity', 'path' => '/operations/phase5/activity', 'permission' => 'dashboards.view'],
            ],
        ],
    ],
];
