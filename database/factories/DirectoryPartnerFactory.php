<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\DirectoryPartner;
use App\Models\DirectoryPartnerCategory;
use App\Models\DirectoryPartnerTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DirectoryPartner> */
final class DirectoryPartnerFactory extends Factory
{
    protected $model = DirectoryPartner::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'directory_partner_category_id' => DirectoryPartnerCategory::factory(),
            'logo_media_id' => null,
            'website_url' => 'https://example.org',
            'partnership_year' => 2026,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
            'display_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function configure(): self
    {
        return $this->afterCreating(function (DirectoryPartner $partner): void {
            foreach (['ar' => 'شريك', 'en' => 'Partner'] as $locale => $name) {
                DirectoryPartnerTranslation::query()->create([
                    'directory_partner_id' => $partner->id,
                    'locale' => $locale,
                    'slug' => 'partner-'.$partner->id.'-'.$locale,
                    'name' => $name.' '.$partner->id,
                    'description' => $locale === 'ar' ? 'وصف مختصر للشريك' : 'Short partner description',
                ]);
            }
        });
    }
}
