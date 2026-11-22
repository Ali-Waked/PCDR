<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(WebsiteSeeder::class);

        // Local development identity only. Production credentials are never seeded.
        if (app()->environment(['local', 'testing'])) {
            $email = config('cms.bootstrap.email') ?: 'test@example.com';
            $password = env('CMS_LOCAL_PASSWORD', 'password');

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Test User',
                    'password' => Hash::make($password),
                ],
            );

            // Keep an existing local bootstrap user usable after password changes.
            $user->forceFill(['password' => Hash::make($password)])->save();
        }
    }
}
