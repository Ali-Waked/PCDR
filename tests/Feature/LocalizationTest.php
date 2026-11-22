<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_and_legacy_auth_urls_redirect_to_arabic_default(): void
    {
        $this->get('/')->assertRedirect('/ar');
        $this->get('/login')->assertRedirect('/ar/login');
        $this->get('/forgot-password')->assertRedirect('/ar/forgot-password');
    }

    public function test_arabic_route_sets_rtl_document_and_localized_props(): void
    {
        $this->get('/ar/login')
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'ar')
                ->where('direction', 'rtl')
                ->where('availableLocales', ['ar', 'en'])
                ->where('translations.ui.auth.login_title', 'تسجيل الدخول إلى المنصة'));
    }

    public function test_english_route_sets_ltr_document_and_localized_props(): void
    {
        $this->get('/en/login')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'en')
                ->where('direction', 'ltr')
                ->where('translations.ui.auth.login_title', 'Sign in to the platform'));
    }

    public function test_validation_messages_follow_the_route_locale(): void
    {
        $this->from('/ar/login')->post('/ar/login', [])
            ->assertSessionHasErrors(['email' => 'الحقل البريد الإلكتروني مطلوب.']);

        $this->from('/en/login')->post('/en/login', [])
            ->assertSessionHasErrors(['email' => 'The email address field is required.']);
    }
}
