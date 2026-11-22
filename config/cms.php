<?php

declare(strict_types=1);

/**
 * Phase 3 CMS contract.
 *
 * Permission keys deliberately match the names documented in
 * docs/architecture/AUTHORIZATION_ARCHITECTURE.md so that the Phase 2
 * permission catalogue can take over without renaming anything.
 */
return [
    'route_prefix' => 'cms',

    /**
     * Development-only bootstrap identity. Never used in production.
     * Phase 2's RBAC catalogue is authoritative once available.
     */
    'bootstrap' => [
        'email' => env('CMS_BOOTSTRAP_EMAIL'),
    ],

    /**
     * Temporary local sign-in shim. Phase 2 owns production authentication.
     */
    'local_auth_enabled' => env('CMS_LOCAL_AUTH', null),

    'permissions' => [
        'dashboard' => 'pages.view',
        'pages.view' => 'View pages',
        'pages.create' => 'Create pages',
        'pages.update' => 'Edit pages',
        'pages.delete' => 'Delete pages',
        'pages.publish' => 'Publish pages',
        'pillars.view' => 'View strategic pillars',
        'pillars.update' => 'Edit strategic pillars',
        'programs.view' => 'View programmes and projects',
        'programs.update' => 'Edit programmes and projects',
        'news.view' => 'View news',
        'news.create' => 'Create news',
        'news.update' => 'Edit news',
        'news.publish' => 'Publish news',
        'publications.view' => 'View publications',
        'publications.create' => 'Create publications',
        'publications.update' => 'Edit publications',
        'publications.publish' => 'Publish publications',
        'media.view' => 'View media library',
        'media.upload' => 'Upload media',
        'media.update' => 'Edit media',
        'media.delete' => 'Delete media',
        'navigation.view' => 'View navigation',
        'navigation.update' => 'Edit navigation',
        'homepage.view' => 'View homepage sections',
        'homepage.update' => 'Edit homepage sections',
        'settings.view' => 'View public site settings',
        'settings.update' => 'Edit public site settings',
        'inquiries.view' => 'View inquiries',
    ],

    /**
     * The public workflow defined in the project brief. Institutional
     * approval is a separate governance concern and is not implied here.
     */
    'transitions' => [
        'draft' => ['pending_review', 'archived'],
        'pending_review' => ['draft', 'approved', 'archived'],
        'approved' => ['draft', 'published', 'archived'],
        'published' => ['draft', 'archived'],
        'archived' => ['draft'],
    ],

    /**
     * Project lifecycle is deliberately distinct from publication workflow.
     */
    'project_statuses' => ['planned', 'ongoing', 'completed', 'archived'],

    'editor' => [
        'allowed_tags' => ['p', 'br', 'strong', 'em', 'u', 'h2', 'h3', 'h4',
            'ul', 'ol', 'li', 'blockquote', 'a', 'figure', 'figcaption', 'table',
            'thead', 'tbody', 'tr', 'th', 'td', 'code', 'pre'],
        'allowed_attributes' => ['href', 'title', 'target', 'rel', 'class'],
    ],
];
