<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import PublicLayout from '../../Layouts/PublicLayout.vue'
import PublicPageHero from '../../Components/Public/PublicPageHero.vue'
import SectionHeading from '../../Components/SectionHeading.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    directory: { type: Object, default: () => ({}) },
    meta: { type: Object, default: null },
})

const { t, localPath } = useI18n()

const allPartnersUrl = localPath('partners')
const categories = computed(() => props.directory?.categories ?? [])
const groups = computed(() => props.directory?.groups ?? [])
const hasPartners = computed(() => Boolean(props.directory?.hasPartners))
const activeCategory = computed(() => props.directory?.activeCategory ?? 'all')
const total = computed(() => Number(props.directory?.total ?? 0))
const filteredEmpty = computed(() => hasPartners.value && groups.value.length === 0)

const heroActions = computed(() => [
    { label: t('nav.contact'), url: localPath('contact'), primary: false },
])

function translate(key, replacements = {}) {
    let value = String(t(key) ?? '')
    for (const [name, replacement] of Object.entries(replacements)) {
        value = value.replaceAll(`:${name}`, String(replacement ?? ''))
    }

    return value
}

function categoryUrl(slug) {
    return slug === 'all'
        ? allPartnersUrl
        : `${allPartnersUrl}?category=${encodeURIComponent(slug)}`
}

function initials(name) {
    return String(name || '')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
}
</script>

<template>
    <PublicLayout :meta="meta" page-class="partners-page">
        <PublicPageHero
            :title="t('ui.partner_directory.hero_title')"
            :lead="t('ui.partner_directory.hero_lead')"
            :breadcrumbs="[{ label: t('nav.partnerships') }]"
            :actions="heroActions"
            accent="navy"
            motif="rings"
        />

        <section class="ps-section ps-section--tight pd-intro">
            <div class="ps-container">
                <SectionHeading
                    :eyebrow="t('nav.partnerships')"
                    :title="t('ui.partner_directory.hero_title')"
                    :body="t('ui.partner_directory.intro')"
                />

                <div class="pd-intro__meta">
                    <span class="ps-eyebrow">{{ t('ui.partner_directory.category_label') }}</span>
                    <span v-if="hasPartners" class="pd-count">
                        {{ translate('ui.partner_directory.partners_count', { count: total }) }}
                    </span>
                </div>

                <nav
                    v-if="categories.length"
                    class="pd-categories"
                    :aria-label="t('ui.partner_directory.category_label')"
                >
                    <Link
                        :href="categoryUrl('all')"
                        class="pd-category"
                        :class="{ 'is-active': activeCategory === 'all' }"
                        :aria-current="activeCategory === 'all' ? 'page' : undefined"
                        preserve-scroll
                    >
                        <span>{{ t('ui.partner_directory.all_partners') }}</span>
                        <span class="pd-category__count">{{ total }}</span>
                    </Link>
                    <Link
                        v-for="category in categories"
                        :key="category.slug"
                        :href="categoryUrl(category.slug)"
                        class="pd-category"
                        :class="{ 'is-active': activeCategory === category.slug }"
                        :aria-current="activeCategory === category.slug ? 'page' : undefined"
                        preserve-scroll
                    >
                        <span>{{ category.name }}</span>
                        <span class="pd-category__count">{{ category.count }}</span>
                    </Link>
                </nav>
            </div>
        </section>

        <section class="ps-section ps-section--tight pd-directory">
            <div class="ps-container">
                <EmptyState
                    v-if="!hasPartners"
                    :title="t('ui.partner_directory.empty_title')"
                    :body="t('ui.partner_directory.empty_message')"
                />

                <EmptyState
                    v-else-if="filteredEmpty"
                    :title="t('ui.partner_directory.filtered_empty_title')"
                    :body="t('ui.partner_directory.filtered_empty_message')"
                >
                    <Link :href="allPartnersUrl" class="ps-btn ps-btn-primary">
                        {{ t('ui.partner_directory.browse_all') }}
                    </Link>
                </EmptyState>

                <div v-else class="pd-groups">
                    <article
                        v-for="group in groups"
                        :id="`category-${group.slug}`"
                        :key="group.slug"
                        class="pd-group"
                        v-reveal
                    >
                        <header class="pd-group__head">
                            <div>
                                <h2>{{ group.name }}</h2>
                                <p v-if="group.description" class="ps-note">{{ group.description }}</p>
                            </div>
                            <span class="pd-group__count">{{ group.partners.length }}</span>
                        </header>

                        <div class="pd-grid">
                            <article
                                v-for="partner in group.partners"
                                :key="partner.id"
                                class="pd-card"
                            >
                                <div class="pd-card__logo">
                                    <img
                                        v-if="partner.logo?.url"
                                        :src="partner.logo.url"
                                        :alt="partner.logo.alt || partner.name"
                                        loading="lazy"
                                    >
                                    <span v-else class="pd-card__monogram" aria-hidden="true">
                                        {{ initials(partner.name) }}
                                    </span>
                                </div>

                                <div class="pd-card__body">
                                    <h3>{{ partner.name }}</h3>
                                    <p v-if="partner.year" class="pd-card__year">
                                        {{ translate('ui.partner_directory.partnership_year', { year: partner.year }) }}
                                    </p>
                                    <p v-if="partner.description" class="pd-card__description">
                                        {{ partner.description }}
                                    </p>
                                    <a
                                        v-if="partner.website"
                                        class="pd-card__link"
                                        :href="partner.website"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        :aria-label="translate('ui.partner_directory.visit_website_label', { name: partner.name })"
                                    >
                                        <span>{{ t('ui.partner_directory.visit_website') }}</span>
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                </div>
                            </article>
                        </div>
                    </article>
                </div>

                <aside class="pd-note">
                    <span class="pd-note__mark" aria-hidden="true">i</span>
                    <p>{{ t('ui.partner_directory.directory_note') }}</p>
                </aside>
            </div>
        </section>
    </PublicLayout>
</template>
