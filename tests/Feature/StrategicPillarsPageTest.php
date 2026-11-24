<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\StrategicPillar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class StrategicPillarsPageTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'ar';

    private const BOOTSTRAP_EMAIL = 'admin@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cms.bootstrap.email', self::BOOTSTRAP_EMAIL);
        config()->set('cms.local_auth_enabled', true);
    }

    private function loginAsBootstrap(): User
    {
        $user = User::factory()->create([
            'name' => 'CMS Admin',
            'email' => self::BOOTSTRAP_EMAIL,
            'password' => Hash::make('password'),
        ]);
        auth()->login($user);

        return $user;
    }

    /** @param array<string, mixed> $ar @param array<string, mixed> $en */
    private function createPillar(
        string $code,
        string $status = 'draft',
        array $ar = [],
        array $en = [],
    ): StrategicPillar {
        $pillar = StrategicPillar::create([
            'code' => $code,
            'status' => $status,
        ]);

        $slug = strtolower($code);

        $pillar->translations()->create(array_merge([
            'locale' => 'ar',
            'slug' => "{$slug}-ar",
            'title' => "المحور {$code}",
            'short_title' => "مختصر {$code}",
            'description' => "وصف عربي {$code}",
        ], $ar));

        $pillar->translations()->create(array_merge([
            'locale' => 'en',
            'slug' => "{$slug}-en",
            'title' => "Pillar {$code}",
            'short_title' => "Short {$code}",
            'description' => "English description {$code}",
        ], $en));

        return $pillar;
    }

    public function test_index_renders_dedicated_localized_page_with_executed_workflow_graph(): void
    {
        $this->loginAsBootstrap();
        $this->createPillar('PH');

        $this->get(route('cms.pillars.index', ['locale' => self::LOCALE]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cms/Pillars/Index')
                ->where('pageTitle', 'المحاور الاستراتيجية')
                ->where('filters', ['search' => '', 'status' => 'all'])
                ->where('workflow.graph.draft', ['pending_review', 'archived'])
                ->where('workflow.graph.pending_review', ['draft', 'approved', 'archived'])
                ->where('workflow.graph.approved', ['draft', 'published', 'archived'])
                ->where('workflow.graph.published', ['draft', 'archived'])
                ->where('workflow.graph.archived', ['draft'])
                ->where('workflow.actions.draft.0.value', 'pending_review')
                ->where('items.0.code', 'PH')
                ->has('statuses', 5)
                ->has('pagination')
                ->etc(),
            );
    }

    public function test_backend_search_matches_code_slug_and_translated_fields_in_both_locales(): void
    {
        $this->loginAsBootstrap();

        $this->createPillar('PH', 'draft', [
            'title' => 'الصحة العامة',
            'short_title' => 'الصحة',
            'slug' => 'al-sihah',
            'description' => 'تحسين الخدمات الصحية',
        ], [
            'title' => 'Public Health',
            'short_title' => 'Health',
            'slug' => 'public-health',
            'description' => 'Improving health services',
        ]);
        $this->createPillar('ED', 'published');

        foreach (['PH', 'public-health', 'Health', 'الصحة', 'تحسين الخدمات الصحية', 'al-sihah'] as $term) {
            $this->get(route('cms.pillars.index', ['locale' => self::LOCALE, 'search' => $term]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Cms/Pillars/Index')
                    ->where('filters.search', $term)
                    ->has('items', 1)
                    ->where('items.0.code', 'PH')
                    ->etc(),
                );
        }
    }

    public function test_status_filter_and_pagination_preserve_the_query_string(): void
    {
        $this->loginAsBootstrap();

        $this->createPillar('PH', 'draft');
        $this->createPillar('ED', 'published');

        $this->get(route('cms.pillars.index', ['locale' => self::LOCALE, 'status' => 'published']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'published')
                ->has('items', 1)
                ->where('items.0.code', 'ED')
                ->where('counts.published', 1)
                ->where('counts.draft', 1)
                ->etc(),
            );

        for ($index = 1; $index <= 13; $index++) {
            $this->createPillar(sprintf('%02d', $index));
        }

        $this->get(route('cms.pillars.index', [
            'locale' => self::LOCALE,
            'search' => 'Pillar',
            'status' => 'draft',
            'page' => 2,
        ]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'Pillar')
            ->where('filters.status', 'draft')
            ->where('pagination.currentPage', 2)
            ->where('pagination.perPage', 12)
            ->has('items', 2)
            ->etc(),
        );
    }

    public function test_invalid_inertia_transition_redirects_with_localized_workflow_feedback(): void
    {
        $this->loginAsBootstrap();
        $pillar = $this->createPillar('PH');

        $response = $this->withHeader('X-Inertia', 'true')->post(
            route('cms.pillars.status', ['locale' => self::LOCALE, 'content' => $pillar->getKey()]),
            [
                'status' => 'published',
                'filters' => ['search' => 'health', 'status' => 'draft', 'page' => 2],
            ],
        );

        $response->assertStatus(303)->assertRedirect(route('cms.pillars.index', [
            'locale' => self::LOCALE,
            'search' => 'health',
            'status' => 'draft',
            'page' => 2,
        ]));

        $response->assertSessionHas('flash.workflow.type', 'invalid_transition');
        $response->assertSessionHas('flash.workflow.current.value', 'draft');
        $response->assertSessionHas('flash.workflow.requested.value', 'published');

        $this->assertSame(4, count(session('flash.workflow.sequence')));
        $this->assertSame('مسودة', session('flash.workflow.sequence.0'));
        $this->assertSame('قيد المراجعة', session('flash.workflow.sequence.1'));
        $this->assertSame('معتمدة', session('flash.workflow.sequence.2'));
        $this->assertSame('منشور', session('flash.workflow.sequence.3'));
        $this->assertStringNotContainsString('draft -> published', (string) json_encode(session('flash.workflow')));
        $this->assertSame('draft', $pillar->fresh()->status->value);
    }

    public function test_non_inertia_json_clients_keep_the_conflict_contract(): void
    {
        $this->loginAsBootstrap();
        $pillar = $this->createPillar('PH');

        $this->postJson(route('cms.pillars.status', [
            'locale' => self::LOCALE,
            'content' => $pillar->getKey(),
        ]), ['status' => 'published'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Invalid workflow transition: draft -> published');

        $this->assertSame('draft', $pillar->fresh()->status->value);
    }

    public function test_valid_transition_still_uses_the_existing_workflow_action(): void
    {
        $this->loginAsBootstrap();
        $pillar = $this->createPillar('PH');

        $this->post(route('cms.pillars.status', [
            'locale' => self::LOCALE,
            'content' => $pillar->getKey(),
        ]), ['status' => 'pending_review'])
            ->assertRedirect();

        $this->assertSame('pending_review', $pillar->fresh()->status->value);
    }
}
