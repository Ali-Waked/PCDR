<?php

declare(strict_types=1);

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
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::prefix('{locale}')
    ->where(['locale' => 'ar|en'])
    ->group(function (): void {
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
    });
