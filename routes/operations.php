<?php

declare(strict_types=1);

use App\Http\Controllers\Operations\DocumentController;
use App\Http\Controllers\Operations\OperationController;
use App\Services\OperationCatalog;
use Illuminate\Support\Facades\Route;

/*
 * Phase 4 operational routes. Permission keys are generated from the families
 * declared in config/operations.php; an undeclared resource has no route.
 */

Route::get('operational-documents/{document}/download', [DocumentController::class, 'download'])
    ->whereNumber('document')
    ->middleware(['auth', 'account.active', 'signed', 'permission:documents.download'])
    ->name('public.operational-documents.download');

Route::middleware('account.active')->prefix('operations')->group(function (): void {
    Route::get('dashboard', [OperationController::class, 'dashboard'])
        ->middleware('permission:reports.view')
        ->name('operations.dashboard');

    Route::middleware('permission:reports.view')->group(function (): void {
        Route::get('reports', [OperationController::class, 'reports'])->name('operations.reports.index');

        Route::get('reports/{report}/export', [OperationController::class, 'exportReport'])
            ->whereNumber('report')
            ->middleware('permission:reports.export')
            ->name('operations.reports.export');
    });

    Route::post('reports/generate', [OperationController::class, 'generateReport'])
        ->middleware('permission:reports.create')
        ->name('operations.reports.generate');

    Route::get('inventory', [OperationController::class, 'inventory'])
        ->middleware('permission:inventory.view')
        ->name('operations.inventory.index');

    Route::post('inventory/movements', [OperationController::class, 'storeMovement'])
        ->name('operations.inventory.movements.store');

    Route::post('inventory/movements/{movement}/reverse', [OperationController::class, 'reverseMovement'])
        ->whereNumber('movement')
        ->middleware('permission:inventory.reverse')
        ->name('operations.inventory.movements.reverse');

    Route::post('indicator-measurements/{record}/verify', [OperationController::class, 'verifyMeasurement'])
        ->whereNumber('record')
        ->name('operations.measurements.verify');

    Route::post('grant-proposals/{record}/submit', [OperationController::class, 'submitProposal'])
        ->whereNumber('record')
        ->name('operations.proposals.submit');

    Route::post('distribution-records/{record}/reconcile', [OperationController::class, 'reconcileDistribution'])
        ->whereNumber('record')
        ->name('operations.distributions.reconcile');

    $catalog = app(OperationCatalog::class);

    foreach ($catalog->resources() as $resource => $definition) {
        if (! is_array($definition) || ! $catalog->has((string) $resource)) {
            continue;
        }

        $family = (string) ($definition['permission'] ?? $resource);
        $path = (string) $resource;

        Route::middleware("permission:{$family}.view")->group(function () use ($resource, $path): void {
            Route::get($path, [OperationController::class, 'index'])
                ->name("operations.{$resource}.index");

            Route::get($path.'/create', [OperationController::class, 'create'])
                ->name("operations.{$resource}.create");

            Route::get($path.'/{record}/edit', [OperationController::class, 'edit'])
                ->whereNumber('record')
                ->name("operations.{$resource}.edit");
        });

        Route::middleware("permission:{$family}.create")->group(function () use ($resource, $path): void {
            Route::post($path, [OperationController::class, 'store'])
                ->name("operations.{$resource}.store");
        });

        Route::middleware("permission:{$family}.update")->group(function () use ($resource, $path): void {
            Route::put($path.'/{record}', [OperationController::class, 'update'])
                ->whereNumber('record')
                ->name("operations.{$resource}.update");

            Route::delete($path.'/{record}', [OperationController::class, 'destroy'])
                ->whereNumber('record')
                ->name("operations.{$resource}.destroy");
        });

        Route::middleware("permission:{$family}.transition")->group(function () use ($resource, $path): void {
            Route::post($path.'/{record}/status', [OperationController::class, 'transition'])
                ->whereNumber('record')
                ->name("operations.{$resource}.transition");
        });

        Route::post($path.'/{record}/documents', [OperationController::class, 'attachDocument'])
            ->whereNumber('record')
            ->middleware('permission:documents.upload')
            ->name("operations.{$resource}.documents.attach");
    }
});
