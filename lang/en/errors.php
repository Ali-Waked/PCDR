<?php

declare(strict_types=1);

return [
    'common' => [
        'skip' => 'Skip to main content',
        'navigation_options' => 'Navigation options',
        'home' => 'Return to homepage',
        'back' => 'Go back',
        'reload' => 'Reload page',
        'contact' => 'Contact us',
        'suggestions' => 'Explore the website',
        'admin_shell' => 'Administration',
        'locale_name' => 'English',
    ],

    '403' => [
        'page_title' => 'Access restricted | PCDR',
        'eyebrow' => 'HTTP 403 · Access restricted',
        'title' => "You don't have permission to access this page.",
        'description' => 'Sorry, you do not have the required permissions to access this content. If you believe this is an error, please contact the appropriate administrator through the official channels.',
        'sr_status' => 'Error status: 403, access restricted.',
    ],

    '404' => [
        'page_title' => 'Page not found | PCDR',
        'eyebrow' => 'HTTP 404 · Page unavailable',
        'title' => "We couldn't find this page.",
        'description' => 'The link may be incorrect, or the page may no longer be available. You can return to the homepage and continue exploring our website.',
        'sr_status' => 'Error status: 404, page not found.',
    ],

    '500' => [
        'page_title' => 'Something went wrong | PCDR',
        'eyebrow' => 'HTTP 500 · Technical problem',
        'title' => 'Something went wrong.',
        'description' => 'We encountered a technical problem while processing your request. We apologize for the inconvenience. Please try again shortly.',
        'sr_status' => 'Error status: 500, something went wrong.',
    ],
];
