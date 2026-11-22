<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditService;
use App\Services\RbacService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class BootstrapPlatformOwnerCommand extends Command
{
    protected $signature = 'pcdr:platform-owner:bootstrap';

    protected $description = 'Securely bootstrap the single Platform Owner account from environment or interactive input';

    public function handle(AuditService $audit, RbacService $rbac): int
    {
        if (User::where('is_platform_owner', true)->exists()) {
            $this->error('A Platform Owner already exists. Use the authenticated admin interface to manage access.');

            return self::FAILURE;
        }

        $email = env('PCDR_OWNER_EMAIL') ?: $this->ask('Platform Owner email');
        $name = env('PCDR_OWNER_NAME') ?: $this->ask('Platform Owner display name');
        $password = env('PCDR_OWNER_PASSWORD') ?: $this->secret('Platform Owner password (minimum 12 characters)');

        Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', Password::min(12)],
            ],
        )->validate();

        $owner = DB::transaction(function () use ($email, $name, $password, $audit, $rbac): User {
            $user = User::forceCreate([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVE,
                'locale' => 'ar',
                'is_platform_owner' => true,
            ]);

            $rbac->invalidate();
            $audit->record('platform_owner.bootstrapped', 'users', (string) $user->id, null, [
                'email' => $user->email,
            ]);

            return $user;
        });

        $this->info("Platform Owner created for {$owner->email}.");
        $this->warn('Remove PCDR_OWNER_PASSWORD from the environment after bootstrap.');

        return self::SUCCESS;
    }
}
