<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class GlobalUxRefinementTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'ar';

    private const BOOTSTRAP_EMAIL = 'global-ux@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cms.bootstrap.email', self::BOOTSTRAP_EMAIL);
        config()->set('cms.local_auth_enabled', true);
    }

    public function test_shared_notification_and_pagination_labels_exist_in_both_locales(): void
    {
        $this->assertSame('Notifications', __('website.ui.notification.notifications_title', [], 'en'));
        $this->assertSame('الإشعارات', __('website.ui.notification.notifications_title', [], 'ar'));

        $english = __('website.ui.pagination.showing', [
            'from' => 1,
            'to' => 12,
            'total' => 42,
        ], 'en');
        $arabic = __('website.ui.pagination.showing', [
            'from' => 1,
            'to' => 12,
            'total' => 42,
        ], 'ar');

        $this->assertStringContainsString('1', $english);
        $this->assertStringContainsString('42', $english);
        $this->assertStringContainsString('1', $arabic);
        $this->assertStringContainsString('42', $arabic);

        foreach (['label', 'previous', 'next', 'page', 'per_page', 'ellipsis'] as $key) {
            $this->assertNotSame("ui.pagination.{$key}", __("website.ui.pagination.{$key}", [], 'en'));
            $this->assertNotSame("ui.pagination.{$key}", __("website.ui.pagination.{$key}", [], 'ar'));
        }
    }

    public function test_media_pagination_exposes_real_range_metadata_and_preserves_filters(): void
    {
        $user = User::factory()->create([
            'name' => 'Global UX Editor',
            'email' => self::BOOTSTRAP_EMAIL,
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);

        $media = Media::create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'path' => 'media/2026/11/global-ux.png',
            'type' => MediaType::Image->value,
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 1024,
            'width' => 800,
            'height' => 600,
            'checksum' => hash('sha256', 'global-ux.png'),
            'original_name' => 'global-ux.png',
            'is_restricted' => false,
        ]);

        $this->get(route('cms.media.index', [
            'locale' => self::LOCALE,
            'q' => 'global-ux',
            'type' => 'image',
        ]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Cms/Media/Index')
            ->where('filters', [
                'type' => 'image',
                'visibility' => '',
                'q' => 'global-ux',
                'sort' => 'newest',
            ])
            ->where('pagination.currentPage', 1)
            ->where('pagination.lastPage', 1)
            ->where('pagination.perPage', 24)
            ->where('pagination.total', 1)
            ->where('pagination.from', 1)
            ->where('pagination.to', 1)
            ->where('items.0.id', $media->getKey()));
    }
}
