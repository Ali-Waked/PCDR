<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed only technical reference data. No demo account or institutional
     * content is created. Bootstrap the Platform Owner explicitly with
     * `php artisan pcdr:platform-owner:bootstrap`.
     */
    public function run(): void
    {
        Artisan::call('pcdr:permissions:sync');
    }
}
