<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessManagementService;
use App\Services\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class WebsiteCmsEmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALE = 'ar';

    private const BOOTSTRAP_EMAIL = 'template-editor@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cms.bootstrap.email', self::BOOTSTRAP_EMAIL);
        config()->set('cms.local_auth_enabled', true);

        $this->withoutVite();
    }

    public function test_template_management_requires_explicit_permissions(): void
    {
        $viewer = $this->userWith(['email_templates.view']);
        $this->actingAs($viewer)->get(route('cms.email-templates.index', ['locale' => self::LOCALE]))->assertOk();

        $creator = $this->userWith(['email_templates.create']);
        $unauthorized = $this->userWith(['pages.view']);

        $this->actingAs($creator)->get(route('cms.email-templates.index', ['locale' => self::LOCALE]))->assertForbidden();
        $this->actingAs($unauthorized)->post(
            route('cms.email-templates.store', ['locale' => self::LOCALE]),
            $this->payload('unauthorized_template'),
        )->assertForbidden();
    }

    public function test_templates_can_be_created_updated_and_deleted_with_audit(): void
    {
        $editor = $this->bootstrapUser();

        $this->actingAs($editor)->post(
            route('cms.email-templates.store', ['locale' => self::LOCALE]),
            $this->payload('Bad-Key'),
        )->assertSessionHasErrors('key');

        $this->post(route('cms.email-templates.store', ['locale' => self::LOCALE]), $this->payload('custom_followup'))
            ->assertRedirect()
            ->assertSessionHas('flash.success');

        $template = EmailTemplate::query()->where('key', 'custom_followup')->firstOrFail();

        $this->assertSame(2, $template->translations()->count());
        $this->assertSame('متابعة مخصصة', $template->translationFor(self::LOCALE)->name);
        $this->assertDatabaseHas('email_template_translations', [
            'email_template_id' => $template->id,
            'locale' => 'en',
            'subject' => 'Custom follow-up {{ inquiry_reference }}',
        ]);

        $this->put(route('cms.email-templates.update', ['locale' => self::LOCALE, 'template' => $template->id]), [
            'is_active' => false,
            'sort_order' => 5,
            'translations' => [
                'ar' => ['name' => 'متابعة محدثة', 'subject' => 'متابعة {{ inquiry_reference }}', 'body' => 'يرجى الرد على {{ inquiry_reference }}'],
                'en' => ['name' => 'Updated follow-up', 'subject' => 'Updated {{ inquiry_reference }}', 'body' => 'Please reply to {{ inquiry_reference }}'],
            ],
        ])->assertRedirect();

        $template->refresh();

        $this->assertFalse($template->is_active);
        $this->assertSame(5, $template->sort_order);
        $this->assertSame('متابعة محدثة', $template->translationFor(self::LOCALE)->name);

        $this->delete(route('cms.email-templates.destroy', ['locale' => self::LOCALE, 'template' => $template->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'email_templates.create']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'email_templates.update']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'email_templates.delete']);
    }

    public function test_preview_interpolates_only_allowlisted_variables_and_never_evaluates_code(): void
    {
        $response = $this->actingAs($this->bootstrapUser())->postJson(
            route('cms.email-templates.preview', ['locale' => self::LOCALE]),
            [
                'subject' => 'Re: {{ inquiry_reference }} / {{ unknown_variable }}',
                'body' => 'Hello {{ sender_name }} — {{ 7*7 }} — {{ system("id") }}',
            ],
        )->assertOk();

        $body = (string) $response->json('body');

        $this->assertSame('Re: 01JPREVIEW00000000000000000 / ', $response->json('subject'));
        $this->assertStringContainsString((string) __('website.cms.preview_sender_name'), $body);
        $this->assertStringNotContainsString('49', $body);
        $this->assertStringNotContainsString('uid=', $body);

        $rendered = app(TemplateRenderer::class)->render(
            '{{ sender_name }}',
            ['sender_name' => '<script>alert("xss")</script>'],
            false,
        );

        $this->assertStringNotContainsString('<script>', $rendered);
    }

    /** @return array<string, mixed> */
    private function payload(string $key): array
    {
        return [
            'key' => $key,
            'is_active' => true,
            'sort_order' => 0,
            'translations' => [
                'ar' => [
                    'name' => 'متابعة مخصصة',
                    'description' => 'قالب تجريبي',
                    'subject' => 'متابعة {{ inquiry_reference }}',
                    'body' => 'نرجو الرد على {{ inquiry_reference }}',
                ],
                'en' => [
                    'name' => 'Custom follow-up',
                    'description' => 'Test template',
                    'subject' => 'Custom follow-up {{ inquiry_reference }}',
                    'body' => 'Please reply to {{ inquiry_reference }}',
                ],
            ],
        ];
    }

    private function bootstrapUser(): User
    {
        return User::factory()->create([
            'name' => 'Template Editor',
            'email' => self::BOOTSTRAP_EMAIL,
            'password' => Hash::make('password'),
        ]);
    }

    /** @param list<string> $keys */
    private function userWith(array $keys): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'key' => 'template-limited-'.$user->id,
            'name' => 'Template limited user',
        ]);

        foreach ($keys as $key) {
            $definition = (array) config('permissions.'.$key, []);
            $permission = Permission::query()->firstOrCreate(['key' => $key], [
                'name' => $definition['label'] ?? $key,
                'module' => $definition['module'] ?? 'website',
                'is_protected' => false,
            ]);
            $role->permissions()->attach($permission);
        }

        app(AccessManagementService::class)->syncRoles($user, [$role->getKey()]);

        return $user;
    }
}
