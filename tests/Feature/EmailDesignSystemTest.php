<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AuditLifecycleNotification;
use App\Notifications\InquiryReplyMail;
use App\Notifications\Phase5\Phase5Mail;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\EmailMessagePresenter;
use App\Services\EmailTemplatePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EmailDesignSystemTest extends TestCase
{
    use RefreshDatabase;

    private const LOGO_HASH = '5834fb72446a75cd56ed6a51a06ae1dafcaa56559c62e6906603978eb27ecb9e';

    private const LAYOUT_MARKER = 'data-pcdr-email="layout-v1"';

    public function test_shared_layout_uses_public_logo_url_and_official_palette(): void
    {
        config()->set('app.url', 'https://pcdr.example.org');
        config()->set('email.brand.logo_url', null);

        $html = app(EmailMessagePresenter::class)->document('Hello there', 'en', [
            'title' => 'Institutional update',
            'reference' => 'PCDR-REF-1',
        ]);

        $this->assertStringContainsString('<html lang="en" dir="ltr">', $html);
        $this->assertStringContainsString(self::LAYOUT_MARKER, $html);
        $this->assertStringContainsString(
            'https://pcdr.example.org/assets/pcdr-homepage/logo/pcdr-logo.png',
            $html,
        );
        $this->assertStringContainsString('#71BCB7', $html);
        $this->assertStringContainsString('#397F7B', $html);
        $this->assertStringContainsString('#F7F2EA', $html);
        $this->assertStringContainsString('PCDR-REF-1', $html);
        $this->assertStringNotContainsString('localhost', $html);
        $this->assertStringNotContainsString('http://', $html);
    }

    public function test_non_public_logo_urls_are_omitted_instead_of_emitted(): void
    {
        config()->set('app.url', 'http://localhost:8000');
        config()->set('email.brand.logo_url', 'http://localhost:8000/assets/logo.png');

        $html = app(EmailMessagePresenter::class)->document('Hello', 'en', ['title' => 'Subject']);

        $this->assertStringContainsString(self::LAYOUT_MARKER, $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('localhost', $html);

        config()->set('email.brand.logo_url', '/assets/pcdr-homepage/logo/pcdr-logo.png');
        $html = app(EmailMessagePresenter::class)->document('Hello', 'en', ['title' => 'Subject']);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('/assets/pcdr-homepage/logo/pcdr-logo.png', $html);
    }

    public function test_arabic_and_english_documents_use_correct_language_direction_and_alignment(): void
    {
        config()->set('email.brand.logo_url', 'https://cdn.example.org/pcdr-logo.png');

        $arabic = app(EmailMessagePresenter::class)->document('نص الرسالة', 'ar', ['title' => 'موضوع الرسالة']);
        $english = app(EmailMessagePresenter::class)->document('Message body', 'en', ['title' => 'Subject']);

        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $arabic);
        $this->assertStringContainsString('text-align:right', $arabic);
        $this->assertStringContainsString('شعار', $arabic);

        $this->assertStringContainsString('<html lang="en" dir="ltr">', $english);
        $this->assertStringContainsString('text-align:left', $english);
        $this->assertStringContainsString('logo', $english);
    }

    public function test_every_default_template_renders_through_the_shared_layout_in_both_locales(): void
    {
        config()->set('email.brand.logo_url', 'https://cdn.example.org/pcdr-logo.png');
        $previews = app(EmailTemplatePreviewService::class);
        $keys = array_keys((array) config('email.default_templates', []));

        $this->assertNotEmpty($keys);

        foreach ($keys as $key) {
            foreach (['ar', 'en'] as $locale) {
                $preview = $previews->build(null, (string) $key, $locale);
                $html = (string) $preview['html'];

                $this->assertStringContainsString(self::LAYOUT_MARKER, $html, (string) $key.'/'.$locale);
                $this->assertStringContainsString('<html lang="'.$locale.'" dir="'.($locale === 'ar' ? 'rtl' : 'ltr').'">', $html);
                $this->assertStringContainsString('https://cdn.example.org/pcdr-logo.png', $html);
                $this->assertStringContainsString((string) $preview['subject'], $html);
                $this->assertStringNotContainsString('{{', $html);
                $this->assertStringNotContainsString('localhost', $html);
                $this->assertSame($locale, $preview['locale']);
                $this->assertSame($locale === 'ar' ? 'rtl' : 'ltr', $preview['direction']);
            }
        }
    }

    public function test_delivered_mailable_and_cms_preview_share_the_same_layout_shell(): void
    {
        config()->set('email.brand.logo_url', 'https://cdn.example.org/pcdr-logo.png');

        $sampleName = (string) __('website.cms.preview_sender_name', [], 'en');
        $mail = new InquiryReplyMail('Reply subject', 'Hello '.$sampleName, 'en', 'REF-42');
        $delivered = (string) $mail->render();

        $preview = app(EmailTemplatePreviewService::class)->build(
            null,
            'inquiry_reply',
            'en',
            'Reply subject',
            'Hello {{ sender_name }}',
        );

        $this->assertStringContainsString(self::LAYOUT_MARKER, $delivered);
        $this->assertStringContainsString(self::LAYOUT_MARKER, (string) $preview['html']);
        $this->assertStringContainsString('REF-42', $delivered);
        $this->assertStringContainsString('https://cdn.example.org/pcdr-logo.png', $delivered);
        $this->assertStringContainsString('https://cdn.example.org/pcdr-logo.png', (string) $preview['html']);
        $this->assertStringContainsString('data-email-preview="true"', (string) $preview['html']);
        $this->assertStringNotContainsString('data-email-preview="true"', $delivered);
        $this->assertStringContainsString('Hello '.$sampleName, $delivered);
        $this->assertStringContainsString('Hello '.$sampleName, (string) $preview['html']);
    }

    public function test_notification_emails_are_wrapped_in_the_shared_layout(): void
    {
        config()->set('email.brand.logo_url', 'https://cdn.example.org/pcdr-logo.png');
        $presenter = app(EmailMessagePresenter::class);

        $audit = (new AuditLifecycleNotification('Audit title', 'Audit message', 'info', ['Actor' => 'Admin']))
            ->toMail((object) ['locale' => 'ar'])
            ->render();

        $phase5 = (string) (new Phase5Mail('phase5.notification.case_created', 'preview', 'en'))->render();

        $user = User::factory()->create(['locale' => 'en']);
        $verification = (string) (new VerifyEmailNotification)->toMail($user)->render();
        $reset = (string) (new ResetPasswordNotification('reset-token'))->toMail($user)->render();

        foreach ([$audit, $phase5, $verification, $reset] as $html) {
            $this->assertStringContainsString(self::LAYOUT_MARKER, $html);
            $this->assertStringContainsString('https://cdn.example.org/pcdr-logo.png', $html);
        }

        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $audit);
        $this->assertStringContainsString('Audit title', $audit);
        $this->assertStringContainsString('Audit message', $audit);
        $this->assertStringContainsString('Case recorded with a safe reference number.', $phase5);
        $this->assertStringContainsString('Verify email address', $verification);
        $this->assertStringContainsString('Reset password', $reset);
    }

    public function test_untrusted_html_is_sanitized_inside_the_layout(): void
    {
        $html = app(EmailMessagePresenter::class)->document(
            '<p onclick="alert(1)">Hello</p><script>alert("xss")</script>'
            .'<a href="javascript:alert(1)">unsafe</a><img src="https://cdn.example.org/x.png" onerror="alert(1)">',
            'en',
            ['title' => 'Subject'],
        );

        $this->assertStringContainsString(self::LAYOUT_MARKER, $html);
        $this->assertStringContainsString('<p>Hello</p>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_official_static_logo_asset_is_used(): void
    {
        $path = public_path((string) config('email.brand.logo_path'));

        $this->assertFileExists($path);
        $this->assertSame(self::LOGO_HASH, hash_file('sha256', $path));
    }
}
