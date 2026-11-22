<?php

declare(strict_types=1);

return [
    'pages' => [
        'paths' => [
            resource_path('js/Pages'),
        ],
        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],
    'testing' => [
        'ensure_pages_exist' => true,
    ],
];
