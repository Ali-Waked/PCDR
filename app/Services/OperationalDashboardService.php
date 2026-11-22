<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aggregates non-identifying operational indicators for the internal dashboard.
 */
final class OperationalDashboardService
{
    public function __construct(private readonly OperationCatalog $catalog) {}

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $modules = [];

        foreach ($this->catalog->resources() as $resource => $definition) {
            $module = (string) ($definition['module'] ?? $resource);
            if (! isset($modules[$module])) {
                $modules[$module] = [
                    'module' => $module,
                    'records' => 0,
                    'resources' => [],
                ];
            }

            $table = (string) $definition['table'];
            $query = DB::table($table);
            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            $count = (int) $query->count();

            $modules[$module]['records'] += $count;
            $modules[$module]['resources'][] = [
                'resource' => $resource,
                'label' => (string) ($definition['label'] ?? $resource),
                'count' => $count,
                'sensitive' => $this->catalog->isSensitive($resource),
            ];
        }

        $inventoryBalance = DB::table('inventory_balances')
            ->selectRaw('COALESCE(SUM(quantity), 0) as on_hand, COALESCE(SUM(reserved_quantity), 0) as reserved')
            ->first();

        return [
            'modules' => array_values($modules),
            'inventory' => [
                'on_hand' => (float) ($inventoryBalance->on_hand ?? 0),
                'reserved' => (float) ($inventoryBalance->reserved ?? 0),
                'valuation_enabled' => $this->catalog->gate('inventory_valuation_enabled'),
            ],
            'gates' => [
                'institutional_approvals_enabled' => $this->catalog->gate('institutional_approvals_enabled'),
                'beneficiary_records_enabled' => $this->catalog->gate('beneficiary_records_enabled'),
                'sensitive_disaggregation_enabled' => $this->catalog->gate('sensitive_disaggregation_enabled'),
                'inventory_valuation_enabled' => $this->catalog->gate('inventory_valuation_enabled'),
            ],
            'privacy_notice' => 'Counts only; no beneficiary, household, or identifiable records are present.',
        ];
    }
}
