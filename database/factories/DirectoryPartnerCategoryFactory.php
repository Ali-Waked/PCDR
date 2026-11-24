<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DirectoryPartnerCategory;
use App\Models\DirectoryPartnerCategoryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DirectoryPartnerCategory> */
final class DirectoryPartnerCategoryFactory extends Factory
{
    protected $model = DirectoryPartnerCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'slug' => Str::slug((string) $this->faker->unique()->words(2, true)).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function configure(): self
    {
        return $this->afterCreating(function (DirectoryPartnerCategory $category): void {
            foreach (['ar' => 'شريك', 'en' => 'Partner'] as $locale => $name) {
                DirectoryPartnerCategoryTranslation::query()->create([
                    'directory_partner_category_id' => $category->id,
                    'locale' => $locale,
                    'name' => $name.' '.$category->id,
                    'description' => $locale === 'ar' ? 'وصف التصنيف' : 'Category description',
                ]);
            }
        });
    }
}
