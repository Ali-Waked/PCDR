<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Permission;
use App\Services\AuditService;
use App\Services\RbacService;
use App\Support\PermissionCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'pcdr:permissions:sync';

    protected $description = 'Synchronize supported code-defined permissions into the database';

    public function handle(AuditService $audit, RbacService $rbac): int
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use (&$created, &$updated, $audit, $rbac): void {
            foreach (PermissionCatalog::supported() as $key => $definition) {
                $permission = Permission::firstOrNew(['key' => $key]);
                $isNew = ! $permission->exists;

                $permission->fill([
                    'name' => $definition['label'] ?? $key,
                    'module' => $definition['module'] ?? 'general',
                    'description' => $definition['description'] ?? null,
                    'is_protected' => (bool) ($definition['protected'] ?? false),
                    'is_retired' => false,
                ]);

                if ($permission->isDirty()) {
                    $permission->save();

                    if ($isNew) {
                        $created++;
                    } else {
                        $updated++;
                    }
                }
            }

            $rbac->invalidate();
            $audit->record('permissions.synced', 'permissions', null, null, [
                'created' => $created,
                'updated' => $updated,
            ]);
        });

        $this->info("Permissions synced: {$created} created, {$updated} updated.");

        return self::SUCCESS;
    }
}
