<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_public_settings_are_exposed_to_the_frontend(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('settings.public', fn ($public) => $public->get('general.site_name') === 'PCDR'
                    && $public->get('localization.default_locale') === 'ar'
                    && ! $public->has('security.login_rate_limit')
                    && ! $public->has('security.lockout_threshold')));
    }

    public function test_authorized_settings_update_persists_values_and_is_audited(): void
    {
        $owner = User::factory()->platformOwner()->create();

        $this->actingAs($owner)->put('/ar/admin/settings', [
            'general.site_name' => 'PCDR Test',
            'security.login_rate_limit' => 6,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'site_name',
            'value' => 'PCDR Test',
            'is_public' => 1,
        ]);
        $this->assertDatabaseHas('settings', [
            'group' => 'security',
            'key' => 'login_rate_limit',
            'value' => '6',
            'is_public' => 0,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings.update',
            'subject_id' => 'general.site_name',
            'actor_id' => $owner->id,
        ]);

        $this->assertSame('PCDR Test', app(SettingsService::class)->get('general.site_name'));
        $this->assertSame(6, app(SettingsService::class)->get('security.login_rate_limit'));
    }

    public function test_invalid_settings_value_is_rejected(): void
    {
        $owner = User::factory()->platformOwner()->create();

        $this->actingAs($owner)
            ->from('/ar/admin/settings')
            ->put('/ar/admin/settings', ['localization.default_locale' => 'fr'])
            ->assertRedirect('/ar/admin/settings')
            ->assertSessionHasErrors('localization.default_locale');

        $this->assertDatabaseMissing('settings', [
            'group' => 'localization',
            'key' => 'default_locale',
            'value' => 'fr',
        ]);
    }

    public function test_unknown_settings_keys_are_ignored(): void
    {
        $owner = User::factory()->platformOwner()->create();

        $this->actingAs($owner)->put('/ar/admin/settings', [
            'not.defined' => 'ignored-value',
        ])->assertRedirect();

        $this->assertDatabaseMissing('settings', ['value' => 'ignored-value']);
    }
}
