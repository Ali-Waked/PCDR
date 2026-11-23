<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CmsEditorialToolsTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'ar';

    private const BOOTSTRAP_EMAIL = 'editor@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cms.bootstrap.email', self::BOOTSTRAP_EMAIL);
        config()->set('cms.local_auth_enabled', true);
        Storage::fake('public');
        Storage::fake('private');
    }

    public function test_media_library_filters_records_and_exposes_per_locale_alt_text(): void
    {
        $this->actingAs($this->bootstrapUser());

        $image = $this->createMedia('field-photo.png', MediaType::Image, false);
        $this->createMedia('policy-note.pdf', MediaType::Document, false);
        $image->translations()->createMany([
            ['locale' => 'ar', 'alt_text' => 'صورة الميدان', 'caption' => 'لقطة ميدانية'],
            ['locale' => 'en', 'alt_text' => 'Field photo', 'caption' => 'Field caption'],
        ]);

        $this->get(route('cms.media.index', [
            'locale' => self::LOCALE,
            'q' => 'field-photo',
            'type' => 'image',
        ]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Cms/Media/Index')
            ->where('filters', ['type' => 'image', 'q' => 'field-photo'])
            ->where('pagination.total', 1)
            ->where('items.0.id', $image->getKey())
            ->where('items.0.translations.ar.alt_text', 'صورة الميدان')
            ->where('items.0.translations.en.alt_text', 'Field photo'));
    }

    public function test_inquiry_index_filters_by_workflow_status(): void
    {
        $this->actingAs($this->bootstrapUser());

        $new = $this->createContactMessage('new');
        $inProgress = $this->createContactMessage('in_progress');

        $this->get(route('cms.inquiries.index', ['locale' => self::LOCALE, 'status' => 'in_progress']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cms/Inquiries/Index')
                ->where('filters.status', 'in_progress')
                ->where('pagination.contact.total', 1)
                ->where('inquiries.contact.0.reference', $inProgress->reference)
                ->where('inquiries.contact.0.status', 'in_progress')
                ->missing('inquiries.contact.1')
                ->where('statuses', fn ($statuses) => array_values(array_intersect(
                    ['new', 'in_progress', 'resolved', 'closed'],
                    $statuses->all(),
                )) === ['new', 'in_progress', 'resolved', 'closed']));

        $this->assertSame('new', $new->fresh()->status);
    }

    public function test_authorized_user_can_change_an_inquiry_workflow_status(): void
    {
        $this->actingAs($this->bootstrapUser());

        $message = $this->createContactMessage('new');

        $this->put(route('cms.inquiries.status', [
            'locale' => self::LOCALE,
            'type' => 'contact',
            'inquiry' => $message->getKey(),
        ]), ['status' => 'in_progress'])->assertRedirect();

        $this->assertSame('in_progress', $message->fresh()->status);
    }

    public function test_inquiry_status_update_rejects_non_workflow_values_and_unknown_types(): void
    {
        $this->actingAs($this->bootstrapUser());

        $message = $this->createContactMessage('new');

        $this->put(route('cms.inquiries.status', [
            'locale' => self::LOCALE,
            'type' => 'contact',
            'inquiry' => $message->getKey(),
        ]), ['status' => 'approved'])->assertSessionHasErrors('status');

        $this->put('/'.self::LOCALE."/cms/inquiries/unknown/{$message->getKey()}/status", [
            'status' => 'resolved',
        ])->assertNotFound();

        $this->assertSame('new', $message->fresh()->status);
    }

    public function test_user_without_inquiry_update_permission_cannot_change_status(): void
    {
        $message = $this->createContactMessage('new');
        $viewer = $this->userWith(['inquiries.view']);

        $this->actingAs($viewer)->put(route('cms.inquiries.status', [
            'locale' => self::LOCALE,
            'type' => 'contact',
            'inquiry' => $message->getKey(),
        ]), ['status' => 'resolved'])->assertForbidden();

        $this->assertSame('new', $message->fresh()->status);
    }

    public function test_content_editor_offers_public_media_only_to_users_with_media_view_permission(): void
    {
        $article = Article::create(['status' => 'draft', 'display_order' => 0]);
        foreach (['ar', 'en'] as $locale) {
            $article->translations()->create([
                'locale' => $locale,
                'slug' => 'editorial-article-'.$locale,
                'title' => 'Article title ('.$locale.')',
            ]);
        }

        $publicImage = $this->createMedia('cover.png', MediaType::Image, false);
        $this->createMedia('private.pdf', MediaType::Document, true);

        $this->actingAs($this->bootstrapUser())
            ->get(route('cms.news.edit', ['locale' => self::LOCALE, 'content' => $article->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cms/Content/Edit')
                ->where('options.media.0.id', $publicImage->getKey())
                ->where('options.media.0.url', $publicImage->url())
                ->missing('options.media.1'));

        $page = Page::create(['status' => 'draft', 'display_order' => 0]);
        $page->translations()->create([
            'locale' => self::LOCALE,
            'slug' => 'editorial-page',
            'title' => 'Page title',
        ]);

        $this->actingAs($this->userWith(['pages.view']))
            ->get(route('cms.pages.edit', ['locale' => self::LOCALE, 'content' => $page->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $pageProps) => $pageProps
                ->component('Cms/Content/Edit')
                ->where('options.media', []));
    }

    private function bootstrapUser(): User
    {
        return User::factory()->create([
            'name' => 'CMS Editor',
            'email' => self::BOOTSTRAP_EMAIL,
            'password' => Hash::make('password'),
        ]);
    }

    /** @param list<string> $keys */
    private function userWith(array $keys): User
    {
        $role = Role::create(['key' => 'limited-'.implode('-', $keys), 'name' => 'Limited user']);

        foreach ($keys as $key) {
            $permission = Permission::create([
                'key' => $key,
                'name' => $key,
                'module' => 'content',
            ]);
            $role->permissions()->attach($permission);
        }

        $user = User::factory()->create();
        app(AccessManagementService::class)->syncRoles($user, [$role->getKey()]);

        return $user;
    }

    private function createMedia(string $name, MediaType $type, bool $restricted): Media
    {
        return Media::create([
            'uuid' => (string) Str::uuid(),
            'disk' => $restricted ? 'private' : 'public',
            'path' => 'media/2026/11/'.$name,
            'type' => $type->value,
            'mime_type' => $type === MediaType::Image ? 'image/png' : 'application/pdf',
            'extension' => $type === MediaType::Image ? 'png' : 'pdf',
            'size_bytes' => 1024,
            'width' => $type === MediaType::Image ? 800 : null,
            'height' => $type === MediaType::Image ? 600 : null,
            'checksum' => hash('sha256', $name),
            'original_name' => $name,
            'is_restricted' => $restricted,
        ]);
    }

    private function createContactMessage(string $status): ContactMessage
    {
        return ContactMessage::create([
            'reference' => (string) Str::ulid(),
            'category' => 'general',
            'name' => 'Test sender',
            'email' => 'sender@example.test',
            'subject' => 'Test subject',
            'message' => 'Test message',
            'locale' => self::LOCALE,
            'consent' => true,
            'status' => $status,
        ]);
    }
}
