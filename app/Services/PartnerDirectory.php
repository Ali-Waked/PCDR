<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DirectoryPartner;
use App\Models\DirectoryPartnerCategory;
use Illuminate\Database\Eloquent\Builder;

final class PartnerDirectory
{
    /**
     * Build the public, published-only directory payload.
     *
     * @return array{categories: list<array<string, mixed>>, groups: list<array<string, mixed>>, activeCategory: string, total: int, hasPartners: bool}
     */
    public function overview(string $locale, ?string $categorySlug = null): array
    {
        $partners = DirectoryPartner::query()
            ->published()
            ->where(static function (Builder $query): void {
                $query->whereNull('directory_partner_category_id')
                    ->orWhereHas('category', static fn (Builder $categoryQuery): Builder => $categoryQuery->active());
            })
            ->with(['translations', 'category.translations', 'logo'])
            ->ordered()
            ->get();

        $activeCategories = DirectoryPartnerCategory::query()
            ->active()
            ->with('translations')
            ->ordered()
            ->get();

        $categories = $activeCategories->map(fn (DirectoryPartnerCategory $category): array => [
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->t('name', $locale) ?: $category->slug,
            'description' => $category->t('description', $locale),
            'count' => $partners->where('directory_partner_category_id', $category->id)->count(),
        ])->values()->all();

        $uncategorized = $partners->whereNull('directory_partner_category_id')->values();
        if ($uncategorized->isNotEmpty()) {
            $categories[] = [
                'id' => null,
                'slug' => 'other',
                'name' => __('website.ui.partner_directory.other_partners', [], $locale),
                'description' => null,
                'count' => $uncategorized->count(),
            ];
        }

        $validSlugs = array_column($categories, 'slug');
        $active = is_string($categorySlug) && in_array($categorySlug, $validSlugs, true)
            ? $categorySlug
            : 'all';

        $groups = [];
        foreach ($activeCategories as $category) {
            if ($active !== 'all' && $active !== $category->slug) {
                continue;
            }

            $categoryPartners = $partners->where('directory_partner_category_id', $category->id)->values();
            if ($categoryPartners->isEmpty()) {
                continue;
            }

            $groups[] = [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->t('name', $locale) ?: $category->slug,
                'description' => $category->t('description', $locale),
                'partners' => $categoryPartners->map(fn (DirectoryPartner $partner): array => $this->partner($partner, $locale))->all(),
            ];
        }

        if ($uncategorized->isNotEmpty() && ($active === 'all' || $active === 'other')) {
            $groups[] = [
                'id' => null,
                'slug' => 'other',
                'name' => __('website.ui.partner_directory.other_partners', [], $locale),
                'description' => null,
                'partners' => $uncategorized->map(fn (DirectoryPartner $partner): array => $this->partner($partner, $locale))->all(),
            ];
        }

        return [
            'categories' => $categories,
            'groups' => $groups,
            'activeCategory' => $active,
            'total' => $partners->count(),
            'hasPartners' => $partners->isNotEmpty(),
        ];
    }

    /** @return array<string, mixed> */
    private function partner(DirectoryPartner $partner, string $locale): array
    {
        $logo = $partner->logo;
        $logoUrl = $logo !== null && ! $logo->is_restricted ? $logo->url() : null;

        return [
            'id' => $partner->id,
            'slug' => $partner->t('slug', $locale),
            'name' => $partner->t('name', $locale) ?: ('Partner '.$partner->id),
            'description' => $partner->t('description', $locale),
            'year' => $partner->partnership_year,
            'website' => $partner->website_url,
            'logo' => $logoUrl === null ? null : [
                'url' => $logoUrl,
                'alt' => $partner->t('name', $locale) ?: __('website.ui.partner_directory.partner_logo', [], $locale),
            ],
        ];
    }
}
