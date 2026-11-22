<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Content workflow
|--------------------------------------------------------------------------
|
| The five required publication states and their permitted forward
| transitions. No content module is implemented in Phase 2; this
| configuration is the shared contract for later modules.
|
*/

return [
    'states' => [
        'draft',
        'pending_review',
        'approved',
        'published',
        'archived',
    ],

    'transitions' => [
        'draft' => ['pending_review', 'archived'],
        'pending_review' => ['approved', 'draft', 'archived'],
        'approved' => ['published', 'pending_review', 'archived'],
        'published' => ['archived'],
        'archived' => ['draft'],
    ],

    'initial' => 'draft',
];
