<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\MediaType;
use App\Enums\ProjectStatus;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Project;
use App\Models\Publication;
use App\Models\Role;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\AccessManagementService;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminDashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', '12345678901234567890123456789012');
        config()->set('dashboard_features.hidden_modules_access', false);

        $this->withoutVite();
    }

    public function test_overview_reports_real_website_data_without_identifying_people(): void
    {
        $owner = User::factory()->platformOwner()->create();

        Article::query()->create(['status' => ContentStatus::Published->value, 'published_at' => now()->subDay()]);
        Article::query()->create(['status' => ContentStatus::Draft->value]);
        Program::query()->create(['status' => ContentStatus::Published->value, 'published_at' => now()->subDay()]);
        Project::query()->create([
            'status' => ProjectStatus::Ongoing->value,
            'workflow_status' => ContentStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
        Publication::query()->create([
            'category' => 'annual_report',
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
        Media::query()->create($this->mediaAttributes(false));
        Media::query()->create($this->mediaAttributes(true));
        ContactMessage::query()->create($this->inquiryAttributes($owner));

        AuditLog::query()->create([
            'actor_id' => $owner->id,
            'action' => 'cms.settings.update',
            'subject_type' => 'website_settings',
            'subject_id' => null,
            'context' => [],
            'created_at' => now(),
        ]);
        AuditLog::query()->create([
            'actor_id' => $owner->id,
            'action' => 'security.test_event',
            'subject_type' => 'users',
            'subject_id' => (string) $owner->id,
            'context' => [],
            'created_at' => now(),
        ]);

        WebsiteSetting::query()->create([
            'group' => 'organization',
            'key' => 'organization.logo',
            'type' => 'url',
            'is_public' => true,
            'is_localized' => false,
            'value' => '/assets/pcdr-homepage/logo/pcdr-logo.png',
        ]);
        app(SiteSettings::class)->flush();

        $this->actingAs($owner)->get('/ar/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('overview.kpis', fn ($kpis): bool => $kpis->firstWhere('key', 'published_news')['value'] === 1
                    && $kpis->firstWhere('key', 'active_programs')['value'] === 1
                    && $kpis->firstWhere('key', 'active_projects')['value'] === 1
                    && $kpis->firstWhere('key', 'new_inquiries')['value'] === 1)
                ->where('overview.content', fn ($content): bool => $content->firstWhere('key', 'news')['metrics'][1]['value'] === 1
                    && $content->firstWhere('key', 'news')['metrics'][2]['value'] === 1
                    && $content->firstWhere('key', 'media')['metrics'][1]['value'] === 1
                    && $content->firstWhere('key', 'media')['metrics'][2]['value'] === 1)
                ->where('overview.pending', fn ($pending): bool => $pending->firstWhere('key', 'inquiries')['value'] === 1)
                ->where('overview.activity', fn ($activity): bool => $activity->count() === 1
                    && $activity->contains(fn (array $event): bool => $event['action'] === 'cms.settings.update')
                    && $activity->every(fn (array $event): bool => str_starts_with($event['action'], 'cms.')
                        && ! array_key_exists('actor', $event)
                        && ! array_key_exists('email', $event)
                        && ! array_key_exists('name', $event)))
                ->where('overview.website.saved_settings', 1)
                ->where('overview.website.logo_configured', true)
                ->where('statistics.operational_records', 0)
                ->where('statistics.phase5_tasks', 0)
                ->where('statistics.phase5_workflows', 0));
    }

    public function test_dashboard_hides_unauthorized_cms_metrics_and_audit_activity(): void
    {
        $user = $this->userWith(['users.view']);

        Article::query()->create(['status' => ContentStatus::Published->value, 'published_at' => now()]);
        ContactMessage::query()->create($this->inquiryAttributes($user));
        AuditLog::query()->create([
            'actor_id' => $user->id,
            'action' => 'cms.settings.update',
            'subject_type' => 'website_settings',
            'subject_id' => null,
            'context' => [],
            'created_at' => now(),
        ]);

        $this->actingAs($user)->get('/ar/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('overview.permissions.news', false)
                ->where('overview.permissions.inquiries', false)
                ->where('overview.permissions.audit_logs', false)
                ->where('overview.kpis', [])
                ->where('overview.content', [])
                ->where('overview.pending', [])
                ->where('overview.activity', [])
                ->where('overview.website', null)
                ->missing('overview.access')
                ->where('statistics.content_items', 0)
                ->where('statistics.inquiries', 0)
                ->where('statistics.recent_events', 0)
                ->where('latestEvent', null));
    }

    public function test_empty_dashboard_contains_zero_based_values_only(): void
    {
        $owner = User::factory()->platformOwner()->create();
        app(SiteSettings::class)->flush();

        $this->actingAs($owner)->get('/ar/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('overview.kpis', fn ($kpis): bool => $kpis->isNotEmpty()
                    && $kpis->every(fn (array $kpi): bool => $kpi['value'] === 0))
                ->where('overview.content', fn ($content): bool => $content->every(fn (array $row): bool => collect($row['metrics'])
                    ->every(fn (array $metric): bool => $metric['value'] === 0)))
                ->where('overview.activity', [])
                ->where('overview.website.saved_settings', 0));
    }

    public function test_overview_greeting_is_localized_for_arabic_and_english(): void
    {
        $owner = User::factory()->platformOwner()->create();

        $arabic = $this->actingAs($owner)->get('/ar/dashboard')->assertOk();
        $english = $this->actingAs($owner)->get('/en/dashboard')->assertOk();

        $arabic->assertInertia(fn (Assert $page) => $page
            ->where('overview.greeting', fn ($value): bool => is_string($value) && $value !== '')
            ->where('overview.description', fn ($value): bool => is_string($value) && $value !== ''));

        $english->assertInertia(fn (Assert $page) => $page
            ->where('overview.greeting', fn ($value): bool => is_string($value) && $value !== '')
            ->where('overview.description', fn ($value): bool => is_string($value) && $value !== ''));
    }

    /** @param list<string> $keys */
    private function userWith(array $keys): User
    {
        $role = Role::query()->create([
            'key' => 'dashboard-limited-'.count($keys),
            'name' => 'Dashboard limited user',
        ]);

        foreach ($keys as $key) {
            $definition = (array) config('permissions.'.$key, []);
            $permission = Permission::query()->firstOrCreate(['key' => $key], [
                'name' => $definition['label'] ?? $key,
                'module' => $definition['module'] ?? 'identity',
                'is_protected' => false,
            ]);
            $role->permissions()->attach($permission);
        }

        $user = User::factory()->create();
        app(AccessManagementService::class)->syncRoles($user, [$role->getKey()]);

        return $user;
    }

    /** @return array<string, mixed> */
    private function mediaAttributes(bool $restricted): array
    {
        return [
            'uuid' => (string) Str::ulid(),
            'disk' => 'public',
            'path' => 'media/test/'.($restricted ? 'restricted.png' : 'public.png'),
            'type' => MediaType::Image->value,
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 1024,
            'checksum' => str_repeat($restricted ? 'a' : 'b', 64),
            'original_name' => $restricted ? 'restricted.png' : 'public.png',
            'is_restricted' => $restricted,
        ];
    }

    /** @return array<string, mixed> */
    private function inquiryAttributes(User $user): array
    {
        return [
            'reference' => (string) Str::ulid(),
            'category' => 'general',
            'name' => 'Private Person',
            'email' => 'private-person@example.test',
            'message' => 'This identifying data must never reach the dashboard.',
            'locale' => 'ar',
            'consent' => true,
            'status' => 'new',
        ];
    }
}
