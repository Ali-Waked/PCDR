<?php

declare(strict_types=1);

use App\Http\Controllers\Phase5\AccountabilityController;
use App\Http\Controllers\Phase5\ActivityController;
use App\Http\Controllers\Phase5\AutomationController;
use App\Http\Controllers\Phase5\CalendarController;
use App\Http\Controllers\Phase5\CollaborationController;
use App\Http\Controllers\Phase5\DashboardController;
use App\Http\Controllers\Phase5\DocumentReviewController;
use App\Http\Controllers\Phase5\IntegrationController;
use App\Http\Controllers\Phase5\NotificationController;
use App\Http\Controllers\Phase5\ReferralController;
use App\Http\Controllers\Phase5\TaskController;
use App\Http\Controllers\Phase5\WebhookController;
use App\Http\Controllers\Phase5\WorkflowController;
use Illuminate\Support\Facades\Route;

/*
 * Phase 5 routes. Permission middleware is the first boundary; controllers and
 * services apply resource-level authorization and governance gates as a second
 * boundary. Approval and publication routes intentionally do not exist.
 */
Route::prefix('operations/phase5')->name('phase5.')->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboards.view')->name('dashboard');
    Route::get('activity', [ActivityController::class, 'index'])
        ->middleware('permission:dashboards.view')->name('activity.index');

    Route::prefix('workflows')->name('workflows.')->group(function (): void {
        Route::get('/', [WorkflowController::class, 'index'])
            ->middleware('permission:workflows.view')->name('index');
        Route::post('definitions', [WorkflowController::class, 'storeDefinition'])
            ->middleware('permission:workflows.create')->name('definitions.store');
        Route::put('definitions/{definition}', [WorkflowController::class, 'updateDefinition'])
            ->middleware('permission:workflows.update')->name('definitions.update');
        Route::post('definitions/{definition}/versions', [WorkflowController::class, 'storeVersion'])
            ->middleware('permission:workflows.update')->name('definitions.versions.store');
        Route::get('instances', [WorkflowController::class, 'instances'])
            ->middleware('permission:workflows.view')->name('instances.index');
        Route::get('instances/{instance}', [WorkflowController::class, 'showInstance'])
            ->middleware('permission:workflows.view')->name('instances.show');
        Route::get('instances/{instance}/history', [WorkflowController::class, 'history'])
            ->middleware('permission:workflows.view')->name('instances.history');
        Route::post('instances/{instance}/transition', [WorkflowController::class, 'transitionInstance'])
            ->middleware('permission:workflows.transition')->name('instances.transition');
        Route::post('instances/{instance}/steps/{step}/transition', [WorkflowController::class, 'transitionStep'])
            ->middleware('permission:workflows.transition')->name('steps.transition');
    });

    Route::prefix('tasks')->name('tasks.')->group(function (): void {
        Route::get('/', [TaskController::class, 'index'])->middleware('permission:tasks.view')->name('index');
        Route::post('/', [TaskController::class, 'store'])->middleware('permission:tasks.create')->name('store');
        Route::get('{task}', [TaskController::class, 'show'])->middleware('permission:tasks.view')->name('show');
        Route::put('{task}', [TaskController::class, 'update'])->middleware('permission:tasks.update')->name('update');
        Route::delete('{task}', [TaskController::class, 'destroy'])->middleware('permission:tasks.delete')->name('destroy');
        Route::post('{task}/transition', [TaskController::class, 'transition'])->middleware('permission:tasks.transition')->name('transition');
        Route::post('{task}/assign', [TaskController::class, 'assign'])->middleware('permission:tasks.assign')->name('assign');
        Route::post('{task}/checklist', [TaskController::class, 'storeChecklist'])->middleware('permission:tasks.update')->name('checklist.store');
        Route::post('{task}/checklist/{item}/toggle', [TaskController::class, 'toggleChecklist'])->middleware('permission:tasks.update')->name('checklist.toggle');
        Route::post('{task}/dependencies', [TaskController::class, 'storeDependency'])->middleware('permission:tasks.update')->name('dependencies.store');
    });

    Route::prefix('discussions')->name('discussions.')->group(function (): void {
        Route::get('{resourceType}/{resourceId}', [CollaborationController::class, 'index'])
            ->middleware('permission:collaboration.view')->name('index');
        Route::get('{resourceType}/{resourceId}/timeline', [CollaborationController::class, 'timeline'])
            ->middleware('permission:collaboration.view')->name('timeline');
        Route::post('{resourceType}/{resourceId}/comments', [CollaborationController::class, 'storeComment'])
            ->middleware('permission:collaboration.comment')->name('comments.store');
        Route::post('{resourceType}/{resourceId}/follow', [CollaborationController::class, 'toggleFollow'])
            ->middleware('permission:collaboration.view')->name('follow.toggle');
        Route::put('comments/{comment}', [CollaborationController::class, 'updateComment'])
            ->middleware('permission:collaboration.comment')->name('comments.update');
        Route::delete('comments/{comment}', [CollaborationController::class, 'destroyComment'])
            ->middleware('permission:collaboration.comment')->name('comments.destroy');
    });

    Route::prefix('accountability')->name('accountability.')->group(function (): void {
        Route::get('/', [AccountabilityController::class, 'index'])
            ->middleware('permission:accountability.view')->name('index');
        Route::post('/', [AccountabilityController::class, 'store'])
            ->middleware('permission:accountability.create')->name('store');
        Route::get('track/{reference}', [AccountabilityController::class, 'track'])
            ->middleware('throttle:10,1')->name('track');
        Route::get('{case}', [AccountabilityController::class, 'show'])
            ->middleware('permission:accountability.view')->name('show');
        Route::post('{case}/transition', [AccountabilityController::class, 'transition'])
            ->middleware('permission:accountability.update')->name('transition');
        Route::post('{case}/assign', [AccountabilityController::class, 'assign'])
            ->middleware('permission:accountability.assign')->name('assign');
    });

    Route::prefix('referrals')->name('referrals.')->group(function (): void {
        Route::get('/', [ReferralController::class, 'index'])->middleware('permission:referrals.view')->name('index');
        Route::post('/', [ReferralController::class, 'store'])->middleware('permission:referrals.create')->name('store');
        Route::get('{referral}', [ReferralController::class, 'show'])->middleware('permission:referrals.view')->name('show');
        Route::post('{referral}/transition', [ReferralController::class, 'transition'])
            ->middleware('permission:referrals.transition')->name('transition');
    });

    Route::prefix('notifications')->name('notifications.')->group(function (): void {
        Route::get('/', [NotificationController::class, 'index'])->middleware('permission:notifications.view')->name('index');
        Route::get('deliveries', [NotificationController::class, 'deliveries'])->middleware('permission:notifications.view')->name('deliveries');
        Route::get('preferences', [NotificationController::class, 'preferences'])->middleware('permission:notifications.preferences')->name('preferences');
        Route::put('preferences', [NotificationController::class, 'updatePreferences'])->middleware('permission:notifications.preferences')->name('preferences.update');
        Route::post('read-all', [NotificationController::class, 'markAllRead'])->middleware('permission:notifications.view')->name('read-all');
        Route::post('{notification}/read', [NotificationController::class, 'markRead'])->middleware('permission:notifications.view')->name('read');
    });

    Route::prefix('automations')->name('automations.')->group(function (): void {
        Route::get('/', [AutomationController::class, 'index'])->middleware('permission:automations.view')->name('index');
        Route::get('executions', [AutomationController::class, 'executions'])->middleware('permission:automations.view')->name('executions');
        Route::post('/', [AutomationController::class, 'store'])->middleware('permission:automations.create')->name('store');
        Route::put('{automation}', [AutomationController::class, 'update'])->middleware('permission:automations.update')->name('update');
        Route::post('{automation}/toggle', [AutomationController::class, 'toggle'])->middleware('permission:automations.update')->name('toggle');
        Route::post('executions/{execution}/retry', [AutomationController::class, 'retry'])->middleware('permission:automations.execute')->name('executions.retry');
    });

    Route::get('calendar', [CalendarController::class, 'index'])
        ->middleware('permission:calendar.view')->name('calendar.index');

    Route::prefix('document-reviews')->name('documents.')->group(function (): void {
        Route::get('/', [DocumentReviewController::class, 'index'])->middleware('permission:documents.review')->name('index');
        Route::post('/', [DocumentReviewController::class, 'store'])->middleware('permission:documents.submit_review')->name('store');
        Route::get('{review}', [DocumentReviewController::class, 'show'])->middleware('permission:documents.review')->name('show');
        Route::get('{review}/history', [DocumentReviewController::class, 'history'])->middleware('permission:documents.review')->name('history');
        Route::post('{review}/assign', [DocumentReviewController::class, 'assign'])->middleware('permission:documents.review')->name('assign');
        Route::post('{review}/transition', [DocumentReviewController::class, 'transition'])->middleware('permission:documents.review')->name('transition');
    });

    Route::prefix('integrations')->name('integrations.')->group(function (): void {
        Route::get('/', [IntegrationController::class, 'index'])->middleware('permission:integrations.view')->name('index');
        Route::post('/', [IntegrationController::class, 'store'])->middleware('permission:integrations.create')->name('store');
        Route::put('{integration}', [IntegrationController::class, 'update'])->middleware('permission:integrations.update')->name('update');
        Route::post('{integration}/toggle', [IntegrationController::class, 'toggle'])->middleware('permission:integrations.update')->name('toggle');
    });

    Route::prefix('webhooks')->name('webhooks.')->group(function (): void {
        Route::get('endpoints', [WebhookController::class, 'endpoints'])->middleware('permission:integrations.view')->name('endpoints.index');
        Route::post('endpoints', [WebhookController::class, 'storeEndpoint'])->middleware('permission:integrations.create')->name('endpoints.store');
        Route::get('deliveries', [WebhookController::class, 'deliveries'])->middleware('permission:integrations.view')->name('deliveries.index');
        Route::post('deliveries/{delivery}/retry', [WebhookController::class, 'retry'])->middleware('permission:integrations.retry')->name('deliveries.retry');
    });
});
