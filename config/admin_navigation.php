<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Administrative navigation contract
|--------------------------------------------------------------------------
|
| Navigation visibility is resolved on the server from RbacService. The Vue
| shell renders this list only; it never decides which capability is allowed.
| Institutional approval entries are intentionally absent.
|
*/

return [
    'groups' => [
        [
            'key' => 'overview',
            'label' => 'overview',
            'items' => [
                ['key' => 'dashboard', 'label' => 'dashboard', 'path' => '/dashboard', 'permission' => null],
            ],
        ],
        [
            'key' => 'access',
            'label' => 'access',
            'items' => [
                ['key' => 'users', 'label' => 'users', 'path' => '/admin/users', 'permission' => 'users.view'],
                ['key' => 'roles', 'label' => 'roles', 'path' => '/admin/roles', 'permission' => 'roles.view'],
                ['key' => 'permissions', 'label' => 'permissions', 'path' => '/admin/permissions', 'permission' => 'permissions.view'],
                ['key' => 'audit_logs', 'label' => 'audit_logs', 'path' => '/admin/audit-logs', 'permission' => 'audit_logs.view'],
                ['key' => 'platform_settings', 'label' => 'platform_settings', 'path' => '/admin/settings', 'permission' => 'settings.view'],
            ],
        ],
        [
            'key' => 'content',
            'label' => 'content',
            'items' => [
                ['key' => 'cms_dashboard', 'label' => 'cms_dashboard', 'path' => '/cms', 'permission' => 'pages.view'],
                ['key' => 'pages', 'label' => 'pages', 'path' => '/cms/pages', 'permission' => 'pages.view'],
                ['key' => 'pillars', 'label' => 'pillars', 'path' => '/cms/pillars', 'permission' => 'pillars.view'],
                ['key' => 'programs', 'label' => 'programs', 'path' => '/cms/programs', 'permission' => 'programs.view'],
                ['key' => 'projects', 'label' => 'projects', 'path' => '/cms/projects', 'permission' => 'programs.view'],
                ['key' => 'news', 'label' => 'news', 'path' => '/cms/news', 'permission' => 'news.view'],
                ['key' => 'publications', 'label' => 'publications', 'path' => '/cms/publications', 'permission' => 'publications.view'],
                ['key' => 'media', 'label' => 'media', 'path' => '/cms/media', 'permission' => 'media.view'],
                ['key' => 'navigation', 'label' => 'navigation', 'path' => '/cms/navigation', 'permission' => 'navigation.view'],
                ['key' => 'homepage', 'label' => 'homepage', 'path' => '/cms/homepage', 'permission' => 'homepage.view'],
                ['key' => 'cms_settings', 'label' => 'cms_settings', 'path' => '/cms/settings', 'permission' => 'settings.view'],
                ['key' => 'inquiries', 'label' => 'inquiries', 'path' => '/cms/inquiries', 'permission' => 'inquiries.view'],
                ['key' => 'email_templates', 'label' => 'email_templates', 'path' => '/cms/email-templates', 'permission' => 'email_templates.view'],
            ],
        ],
    ],
];
