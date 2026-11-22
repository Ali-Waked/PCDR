<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Cms\ArticleController;
use App\Http\Controllers\Cms\AuthController;
use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\HomepageController;
use App\Http\Controllers\Cms\InquiryController;
use App\Http\Controllers\Cms\MediaController as CmsMediaController;
use App\Http\Controllers\Cms\NavigationController;
use App\Http\Controllers\Cms\PageController as CmsPageController;
use App\Http\Controllers\Cms\PillarController as CmsPillarController;
use App\Http\Controllers\Cms\ProgramController as CmsProgramController;
use App\Http\Controllers\Cms\ProjectController as CmsProjectController;
use App\Http\Controllers\Cms\PublicationController as CmsPublicationController;
use App\Http\Controllers\Cms\SettingController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\InquiryController as PublicInquiryController;
use App\Http\Controllers\Public\MediaController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PillarController;
use App\Http\Controllers\Public\ProgramController;
use App\Http\Controllers\Public\PublicationController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/'.config('website.default_locale', 'ar'))->name('root');

// Legacy unprefixed authentication URLs redirect to the Arabic default.
Route::get('/login', fn () => redirect()->route('login.show', ['locale' => 'ar']));
Route::get('/forgot-password', fn () => redirect()->route('password.request', ['locale' => 'ar']));
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::prefix('{locale}')
    ->where(['locale' => 'ar|en'])
    ->group(function (): void {
        // Single route registration: a duplicate same-URI route overwrites the
        // method/URI match and can drop the earlier name from the route collection.
        Route::get('/', HomeController::class)->name('public.home');

        Route::get('/about', [AboutController::class, '__invoke'])->name('public.about');
        Route::get('/about/{focus}', [AboutController::class, '__invoke'])->name('public.about.focus');

        Route::get('/strategic-areas', [PillarController::class, 'index'])->name('public.pillars.index');
        Route::get('/strategic-areas/{pillar}', [PillarController::class, 'show'])->name('public.pillars.show');

        Route::get('/programs', [ProgramController::class, 'index'])->name('public.programs.index');
        Route::get('/programs/{program}', [ProgramController::class, 'show'])->name('public.programs.show');
        Route::get('/projects/{project}', [ProgramController::class, 'project'])->name('public.projects.show');

        Route::get('/news', [NewsController::class, 'index'])->name('public.news.index');
        Route::get('/news/{article}', [NewsController::class, 'show'])->name('public.news.show');

        Route::get('/publications', [PublicationController::class, 'index'])->name('public.publications.index');
        Route::get('/publications/{publication}/download', [PublicationController::class, 'download'])->name('public.publications.download');
        Route::get('/publications/{publication}', [PublicationController::class, 'show'])->name('public.publications.show');

        Route::get('/transparency', [StaticPageController::class, 'transparency'])->name('public.transparency');
        Route::get('/partnerships', [StaticPageController::class, 'partnerships'])->name('public.partnerships');
        Route::get('/get-involved', [StaticPageController::class, 'involve'])->name('public.involve');
        Route::get('/contact', [StaticPageController::class, 'contact'])->name('public.contact');

        Route::post('/contact/inquiry', [PublicInquiryController::class, 'contact'])->name('public.inquiry.contact');
        Route::post('/volunteer/inquiry', [PublicInquiryController::class, 'volunteer'])->name('public.inquiry.volunteer');
        Route::post('/partnership/inquiry', [PublicInquiryController::class, 'partnership'])->name('public.inquiry.partnership');

        Route::get('/pages/{page}', [PageController::class, 'show'])->name('public.page');
        Route::get('/media/{uuid}/download', [MediaController::class, 'download'])->name('public.media.download');

        Route::prefix('cms')->name('cms.')->group(function (): void {
            Route::middleware('throttle:10,5')->group(function (): void {
                Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
                Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
            });
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            // Every CMS route is protected by the `cms` middleware below,
            // which redirects guests to the real localized `cms.login` route.
            Route::group([], function (): void {
                Route::get('/', DashboardController::class)->middleware('cms:pages.view')->name('dashboard');

                Route::middleware('cms:pages.view')->group(function (): void {
                    Route::get('/pages', [CmsPageController::class, 'index'])->name('pages.index');
                    Route::get('/pages/create', [CmsPageController::class, 'create'])->name('pages.create');
                    Route::post('/pages', [CmsPageController::class, 'store'])->name('pages.store');
                    Route::get('/pages/{content}/edit', [CmsPageController::class, 'edit'])->name('pages.edit');
                    Route::put('/pages/{content}', [CmsPageController::class, 'update'])->name('pages.update');
                    Route::delete('/pages/{content}', [CmsPageController::class, 'destroy'])->name('pages.destroy');
                    Route::post('/pages/{content}/status', [CmsPageController::class, 'status'])->name('pages.status');
                });

                Route::middleware('cms:pillars.view')->group(function (): void {
                    Route::get('/pillars', [CmsPillarController::class, 'index'])->name('pillars.index');
                    Route::get('/pillars/create', [CmsPillarController::class, 'create'])->name('pillars.create');
                    Route::post('/pillars', [CmsPillarController::class, 'store'])->name('pillars.store');
                    Route::get('/pillars/{content}/edit', [CmsPillarController::class, 'edit'])->name('pillars.edit');
                    Route::put('/pillars/{content}', [CmsPillarController::class, 'update'])->name('pillars.update');
                    Route::delete('/pillars/{content}', [CmsPillarController::class, 'destroy'])->name('pillars.destroy');
                    Route::post('/pillars/{content}/status', [CmsPillarController::class, 'status'])->name('pillars.status');
                });

                Route::middleware('cms:programs.view')->group(function (): void {
                    Route::get('/programs', [CmsProgramController::class, 'index'])->name('programs.index');
                    Route::get('/programs/create', [CmsProgramController::class, 'create'])->name('programs.create');
                    Route::post('/programs', [CmsProgramController::class, 'store'])->name('programs.store');
                    Route::get('/programs/{content}/edit', [CmsProgramController::class, 'edit'])->name('programs.edit');
                    Route::put('/programs/{content}', [CmsProgramController::class, 'update'])->name('programs.update');
                    Route::delete('/programs/{content}', [CmsProgramController::class, 'destroy'])->name('programs.destroy');
                    Route::post('/programs/{content}/status', [CmsProgramController::class, 'status'])->name('programs.status');

                    Route::get('/projects', [CmsProjectController::class, 'index'])->name('projects.index');
                    Route::get('/projects/create', [CmsProjectController::class, 'create'])->name('projects.create');
                    Route::post('/projects', [CmsProjectController::class, 'store'])->name('projects.store');
                    Route::get('/projects/{content}/edit', [CmsProjectController::class, 'edit'])->name('projects.edit');
                    Route::put('/projects/{content}', [CmsProjectController::class, 'update'])->name('projects.update');
                    Route::delete('/projects/{content}', [CmsProjectController::class, 'destroy'])->name('projects.destroy');
                    Route::post('/projects/{content}/status', [CmsProjectController::class, 'status'])->name('projects.status');
                });

                Route::middleware('cms:news.view')->group(function (): void {
                    Route::get('/news', [ArticleController::class, 'index'])->name('news.index');
                    Route::get('/news/create', [ArticleController::class, 'create'])->name('news.create');
                    Route::post('/news', [ArticleController::class, 'store'])->name('news.store');
                    Route::get('/news/{content}/edit', [ArticleController::class, 'edit'])->name('news.edit');
                    Route::put('/news/{content}', [ArticleController::class, 'update'])->name('news.update');
                    Route::delete('/news/{content}', [ArticleController::class, 'destroy'])->name('news.destroy');
                    Route::post('/news/{content}/status', [ArticleController::class, 'status'])->name('news.status');
                });

                Route::middleware('cms:publications.view')->group(function (): void {
                    Route::get('/publications', [CmsPublicationController::class, 'index'])->name('publications.index');
                    Route::get('/publications/create', [CmsPublicationController::class, 'create'])->name('publications.create');
                    Route::post('/publications', [CmsPublicationController::class, 'store'])->name('publications.store');
                    Route::get('/publications/{content}/edit', [CmsPublicationController::class, 'edit'])->name('publications.edit');
                    Route::put('/publications/{content}', [CmsPublicationController::class, 'update'])->name('publications.update');
                    Route::delete('/publications/{content}', [CmsPublicationController::class, 'destroy'])->name('publications.destroy');
                    Route::post('/publications/{content}/status', [CmsPublicationController::class, 'status'])->name('publications.status');
                });

                Route::get('/media', [CmsMediaController::class, 'index'])->middleware('cms:media.view')->name('media.index');
                Route::post('/media', [CmsMediaController::class, 'store'])->middleware('cms:media.upload')->name('media.store');
                Route::put('/media/{media}', [CmsMediaController::class, 'update'])->middleware('cms:media.update')->name('media.update');
                Route::delete('/media/{media}', [CmsMediaController::class, 'destroy'])->middleware('cms:media.delete')->name('media.destroy');
                Route::get('/media/{media}/signed', [CmsMediaController::class, 'signed'])->middleware('cms:media.view')->name('media.signed');

                Route::get('/navigation', [NavigationController::class, 'index'])->middleware('cms:navigation.view')->name('navigation.index');
                Route::put('/navigation/{menu}', [NavigationController::class, 'update'])->middleware('cms:navigation.update')->name('navigation.update');

                Route::get('/homepage', [HomepageController::class, 'index'])->middleware('cms:homepage.view')->name('homepage.index');
                Route::put('/homepage', [HomepageController::class, 'update'])->middleware('cms:homepage.update')->name('homepage.update');

                Route::get('/settings', [SettingController::class, 'index'])->middleware('cms:settings.view')->name('settings.index');
                Route::put('/settings', [SettingController::class, 'update'])->middleware('cms:settings.update')->name('settings.update');

                Route::get('/inquiries', [InquiryController::class, 'index'])->middleware('cms:inquiries.view')->name('inquiries.index');
            });
        });

        // Phase 2 authentication and platform administration routes.
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
            Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

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

                Route::get('settings', [AdminSettingController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
                Route::put('settings', [AdminSettingController::class, 'update'])->middleware('permission:settings.update')->name('settings.update');
            });
        });

    });
