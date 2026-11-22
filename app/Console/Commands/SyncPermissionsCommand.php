<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Permission;
use App\Services\AuditService;
use App\Services\RbacService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'pcdr:permissions:sync';

    protected $description = 'Synchronize the code-owned permission catalog into the database';

    public function handle(AuditService $audit, RbacService $rbac): int
    {
        $catalog = config('permissions', []);
        $created = 0;
        $retired = 0;

        DB::transaction(function () use ($catalog, &$created, &$retired, $audit, $rbac): void {
            foreach ($catalog as $key => $definition) {
                $permission = Permission::firstOrNew(['key' => $key]);

                if (! $permission->exists) {
                    $created++;
                }

                $permission->fill([
                    'name' => $definition['label'] ?? $key,
                    'module' => $definition['module'] ?? 'general',
                    'description' => $definition['description'] ?? null,
                    'is_protected' => (bool) ($definition['protected'] ?? false),
                    'is_retired' => false,
                ])->save();
            }

            $retired = Permission::whereNotIn('key', array_keys($catalog))
                ->where('is_retired', false)
                ->update(['is_retired' => true]);

            $rbac->invalidate();
            $audit->record('permissions.synced', 'permissions', null, null, [
                'created' => $created,
                'retired' => $retired,
            ]);
        });

        $this->info("Permissions synced: {$created} created, {$retired} retired.");

        return self::SUCCESS;
    }
}
