<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('welcome', ['locale' => 'ar']));

// Legacy unprefixed authentication URLs redirect to the Arabic default.
Route::get('/login', fn () => redirect()->route('login.show', ['locale' => 'ar']));
Route::get('/forgot-password', fn () => redirect()->route('password.request', ['locale' => 'ar']));

Route::prefix('{locale}')->whereIn('locale', ['ar', 'en'])->group(function (): void {
    Route::get('/', WelcomeController::class)->name('welcome');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthenticatedSessionController::class, 'show'])->name('login.show');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:login')
            ->name('login.store');

        Route::get('/forgot-password', [PasswordResetController::class, 'showRequest'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
            ->middleware('throttle:password-reset')
            ->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.store');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
        Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('verification.resend');
    });

    Route::middleware(['auth', 'account.active'])->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::prefix('admin')->as('admin.')->group(function (): void {
            Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
            Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
            Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:users.view')->name('users.show');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
            Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->middleware('permission:users.update')->name('users.status');
            Route::put('users/{user}/roles', [UserController::class, 'syncRoles'])->middleware('permission:users.assign_roles')->name('users.roles');
            Route::post('users/{user}/reset-password', [UserController::class, 'sendPasswordReset'])->middleware('permission:users.reset_password')->name('users.reset-password');

            Route::get('roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
            Route::get('roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
            Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.view')->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');
            Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:roles.update')->name('roles.permissions');

            Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view')->name('permissions.index');
            Route::get('permissions/create', [PermissionController::class, 'create'])->middleware('permission:permissions.create')->name('permissions.create');
            Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.create')->name('permissions.store');
            Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])->middleware('permission:permissions.view')->name('permissions.edit');
            Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.update')->name('permissions.update');
            Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete')->name('permissions.destroy');

            Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.view')->name('audit-logs.index');
            Route::get('audit-logs/export', [AuditLogController::class, 'export'])->middleware('permission:audit_logs.export')->name('audit-logs.export');

            Route::get('settings', [SettingController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
            Route::put('settings', [SettingController::class, 'update'])->middleware('permission:settings.update')->name('settings.update');
        });
    });
});
