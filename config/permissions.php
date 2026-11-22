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
];
