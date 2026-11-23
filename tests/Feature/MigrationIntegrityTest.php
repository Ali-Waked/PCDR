<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_delivery_reference_matches_notification_id_type(): void
    {
        $this->assertSame(
            Schema::getColumnType('notifications', 'id'),
            Schema::getColumnType('notification_deliveries', 'notification_id'),
        );
    }

    public function test_distribution_records_reference_inventory_items(): void
    {
        $connection = Schema::getConnection();

        $referencedTables = match ($connection->getDriverName()) {
            'sqlite' => collect($connection->select("PRAGMA foreign_key_list('distribution_records')"))
                ->where('from', 'inventory_item_id')
                ->pluck('table')
                ->map(fn (mixed $table): string => (string) $table)
                ->all(),
            'mysql' => collect($connection->select(
                'SELECT referenced_table_name AS referenced_table
                 FROM information_schema.key_column_usage
                 WHERE table_schema = DATABASE()
                   AND table_name = \'distribution_records\'
                   AND column_name = \'inventory_item_id\'
                   AND referenced_table_name IS NOT NULL'
            ))->pluck('referenced_table')
                ->map(fn (mixed $table): string => (string) $table)
                ->all(),
            default => [],
        };

        $this->assertContains('inventory_items', $referencedTables);
    }
}
