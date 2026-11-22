<script setup>
import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import StatusPill from '../../../Components/StatusPill.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    resource: { type: String, required: true },
    label: { type: String, required: true },
    routeBase: { type: String, required: true },
    record: { type: Object, default: null },
    translations: { type: Object, required: true },
    statuses: { type: Array, default: () => [] },
    revisions: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
})

const { t, localPath, formatDate } = useI18n()
const locales = ['ar', 'en']

const segments = {
    'cms.pages': 'pages',
    'cms.pillars': 'pillars',
    'cms.programs': 'programs',
    'cms.projects': 'projects',
    'cms.news': 'news',
    'cms.publications': 'publications',
}

const basePath = computed(() => localPath(`cms/${segments[props.routeBase] || props.resource}`))
const isCreate = computed(() => props.record === null)

function initialExtras() {
    const attrs = props.record?.attributes || {}

    switch (props.resource) {
        case 'pages':
            return { parent_id: attrs.parent_id ?? '', is_system: Boolean(attrs.is_system) }
        case 'pillars':
            return { code: attrs.code ?? '', featured_image_id: attrs.featured_image_id ?? '', is_featured: Boolean(attrs.is_featured) }
        case 'programs':
            return {
                strategic_pillar_id: attrs.strategic_pillar_id ?? '',
                start_date: attrs.start_date ?? '',
                end_date: attrs.end_date ?? '',
                featured_image_id: attrs.featured_image_id ?? '',
            }
        case 'projects':
            return {
                program_id: attrs.program_id ?? '',
                strategic_pillar_id: attrs.strategic_pillar_id ?? '',
                project_status: attrs.status ?? (props.options.projectStatuses?.[0]?.value || 'planned'),
                start_date: attrs.start_date ?? '',
                end_date: attrs.end_date ?? '',
                featured_image_id: attrs.featured_image_id ?? '',
            }
        case 'news':
            return {
                article_category_id: attrs.article_category_id ?? '',
                strategic_pillar_id: attrs.strategic_pillar_id ?? '',
                cover_media_id: attrs.cover_media_id ?? '',
                event_date: attrs.event_date ?? '',
                is_featured: Boolean(attrs.is_featured),
                tags: [],
            }
        case 'publications':
            return {
                category: attrs.category ?? 'institutional',
                strategic_pillar_id: attrs.strategic_pillar_id ?? '',
                document_media_id: attrs.document_media_id ?? '',
                cover_media_id: attrs.cover_media_id ?? '',
                publication_date: attrs.publication_date ?? '',
                version: attrs.version ?? '',
                is_restricted: Boolean(attrs.is_restricted),
            }
        default:
            return {}
    }
}

const form = useForm({
    status: props.record?.status || 'draft',
    published_at: props.record?.publishedAt || '',
    display_order: props.record?.displayOrder ?? 0,
    translations: JSON.parse(JSON.stringify(props.translations)),
    ...initialExtras(),
})

function fieldLabel(field) {
    const map = {
        title: 'cms_title',
        slug: 'cms_slug',
        summary: 'cms_summary',
        excerpt: 'cms_summary',
        body: 'cms_body',
        description: 'cms_description',
        objectives: 'ui.approach',
        target_groups: 'ui.target_groups',
        geographic_coverage: 'governance',
        seo_title: 'cms_seo',
        seo_description: 'cms_seo',
        short_title: 'cms_title',
        canonical_url: 'cms_seo',
        og_image: 'cms_seo',
        robots: 'cms_seo',
    }

    return t(map[field] || field)
}

function isLongField(field) {
    return ['body', 'description', 'objectives', 'target_groups'].includes(field)
}

const transitions = {
    draft: ['draft', 'pending_review', 'archived'],
    pending_review: ['pending_review', 'draft', 'approved', 'archived'],
    approved: ['approved', 'draft', 'published', 'archived'],
    published: ['published', 'draft', 'archived'],
    archived: ['archived', 'draft'],
}

const allowedStatuses = computed(() => {
    const current = props.record?.status || 'draft'
    const allowed = transitions[current] || [current]
    return props.statuses.filter((item) => allowed.includes(item.value))
})

function applyTransition(status) {
    if (! props.record) {
        form.status = status
        return
    }

    router.post(`${basePath.value}/${props.record.id}/status`, { status }, {
        preserveScroll: true,
        onSuccess: () => { form.status = status },
    })
}

function submit() {
    const url = isCreate.value ? basePath.value : `${basePath.value}/${props.record.id}`

    if (isCreate.value) {
        form.post(url, { preserveScroll: true })
        return
    }

    form.put(url, { preserveScroll: true })
}

function dateValue(key) {
    return form[key] ? String(form[key]).slice(0, 10) : ''
}
</script>

<template>
    <CmsLayout :title="isCreate ? t('cms_new_item') : t('cms_edit')" :subtitle="label">
        <div class="cms-toolbar">
            <Link :href="basePath" class="btn btn-secondary btn-small">{{ t('ui.back_to_list') }}</Link>
            <StatusPill v-if="record" :status="record.status" />
        </div>

        <form @submit.prevent="submit">
            <div class="cms-form-grid cms-form-grid--split">
                <div>
                    <section class="cms-card">
                        <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_translations') }}</h2>
                        <div v-for="code in locales" :key="code" class="translation-pane">
                            <h3 class="small-caps">{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }}</h3>
                            <div class="form-grid">
                                <label v-for="(value, field) in form.translations[code]" :key="`${code}-${field}`" class="field">
                                    <span>{{ fieldLabel(field) }} <span class="hint">{{ ['title', 'slug'].includes(field) ? t('cms_required') : t('cms_optional') }}</span></span>
                                    <textarea
                                        v-if="isLongField(field)"
                                        v-model="form.translations[code][field]"
                                        class="textarea"
                                        :rows="field === 'body' ? 8 : 4"
                                        :aria-invalid="!! form.errors[`translations.${code}.${field}`]"
                                    ></textarea>
                                    <input
                                        v-else
                                        v-model="form.translations[code][field]"
                                        class="input"
                                        type="text"
                                        :dir="['slug', 'canonical_url', 'og_image'].includes(field) ? 'ltr' : undefined"
                                        :aria-invalid="!! form.errors[`translations.${code}.${field}`]"
                                    >
                                    <span v-if="form.errors[`translations.${code}.${field}`]" class="error" role="alert">{{ form.errors[`translations.${code}.${field}`] }}</span>
                                </label>
                            </div>
                        </div>
                    </section>
                </div>

                <div>
                    <section class="cms-card">
                        <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_basic_data') }}</h2>
                        <input type="hidden" :value="form.status" name="status">
                        <div class="form-grid">
                            <label class="field">
                                <span>{{ t('cms_publish_status') }}</span>
                                <select class="select" :value="form.status" @change="applyTransition($event.target.value)">
                                    <option v-for="status in allowedStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>{{ t('published_on') }}</span>
                                <input v-model="form.published_at" class="input" type="date" dir="ltr">
                            </label>
                            <label class="field">
                                <span>{{ t('cms_order') }}</span>
                                <input v-model.number="form.display_order" class="input" type="number" min="0" dir="ltr">
                            </label>
                        </div>
                    </section>

                    <section v-if="Object.keys(initialExtras()).length" class="cms-card">
                        <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_relations') }}</h2>
                        <div class="form-grid">
                            <template v-if="resource === 'pages'">
                                <label class="field">
                                    <span>{{ t('parent_page') }}</span>
                                    <input v-model="form.parent_id" class="input" type="number" min="1" dir="ltr">
                                </label>
                                <label class="checkbox-row"><input v-model="form.is_system" type="checkbox"><span>{{ t('institution') }}</span></label>
                            </template>

                            <template v-if="resource === 'pillars'">
                                <label class="field"><span>Code</span><input v-model="form.code" class="input" type="text" maxlength="2" dir="ltr"></label>
                                <label class="field"><span>{{ t('ui.cms_media') }}</span><input v-model="form.featured_image_id" class="input" type="number" min="1" dir="ltr"></label>
                                <label class="checkbox-row"><input v-model="form.is_featured" type="checkbox"><span>{{ t('cms_section_enabled') }}</span></label>
                            </template>

                            <template v-if="resource === 'programs'">
                                <label class="field"><span>{{ t('navigation.pillars') }}</span>
                                    <select v-model="form.strategic_pillar_id" class="select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('start') }}</span><input v-model="form.start_date" class="input" type="date" dir="ltr"></label>
                                <label class="field"><span>{{ t('end') }}</span><input v-model="form.end_date" class="input" type="date" dir="ltr"></label>
                                <label class="field"><span>{{ t('ui.cms_media') }}</span><input v-model="form.featured_image_id" class="input" type="number" min="1" dir="ltr"></label>
                            </template>

                            <template v-if="resource === 'projects'">
                                <label class="field"><span>{{ t('ui.cms_programs') }}</span>
                                    <select v-model="form.program_id" class="select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.programs || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('navigation.pillars') }}</span>
                                    <select v-model="form.strategic_pillar_id" class="select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('cms_publish_status') }}</span>
                                    <select v-model="form.project_status" class="select"><option v-for="item in options.projectStatuses || []" :key="item.value" :value="item.value">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('start') }}</span><input v-model="form.start_date" class="input" type="date" dir="ltr"></label>
                                <label class="field"><span>{{ t('end') }}</span><input v-model="form.end_date" class="input" type="date" dir="ltr"></label>
                                <label class="field"><span>{{ t('ui.cms_media') }}</span><input v-model="form.featured_image_id" class="input" type="number" min="1" dir="ltr"></label>
                            </template>

                            <template v-if="resource === 'news'">
                                <label class="field"><span>{{ t('ui.category') }}</span>
                                    <select v-model="form.article_category_id" class="select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.categories || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('navigation.pillars') }}</span>
                                    <select v-model="form.strategic_pillar_id" class="select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('ui.cms_media') }}</span><input v-model="form.cover_media_id" class="input" type="number" min="1" dir="ltr"></label>
                                <label class="field"><span>{{ t('activity') }}</span><input v-model="form.event_date" class="input" type="date" dir="ltr"></label>
                                <label class="checkbox-row"><input v-model="form.is_featured" type="checkbox"><span>{{ t('cms_section_enabled') }}</span></label>
                                <fieldset v-if="options.tags?.length" style="border: 0; padding: 0; margin: 0">
                                    <legend class="label">{{ t('available_options') }}</legend>
                                    <label v-for="tag in options.tags" :key="tag.id" class="checkbox-row"><input v-model="form.tags" type="checkbox" :value="tag.id"><span>{{ tag.label }}</span></label>
                                </fieldset>
                            </template>

                            <template v-if="resource === 'publications'">
                                <label class="field"><span>{{ t('ui.category') }}</span>
                                    <select v-model="form.category" class="select"><option v-for="item in options.categories || []" :key="item.value" :value="item.value">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('navigation.pillars') }}</span>
                                    <select v-model="form.strategic_pillar_id" class="select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('documents') }}</span><input v-model="form.document_media_id" class="input" type="number" min="1" dir="ltr"></label>
                                <label class="field"><span>{{ t('ui.cms_media') }}</span><input v-model="form.cover_media_id" class="input" type="number" min="1" dir="ltr"></label>
                                <label class="field"><span>{{ t('published_on') }}</span><input v-model="form.publication_date" class="input" type="date" dir="ltr"></label>
                                <label class="field"><span>{{ t('version') }}</span><input v-model="form.version" class="input" type="text" maxlength="32" dir="ltr"></label>
                                <label class="checkbox-row"><input v-model="form.is_restricted" type="checkbox"><span>{{ t('cms_restricted_file') }}</span></label>
                            </template>
                        </div>
                    </section>

                    <section v-if="revisions.length" class="cms-card">
                        <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_revisions') }}</h2>
                        <ul class="revision-list">
                            <li v-for="revision in revisions" :key="revision.id">
                                <div class="meta-row"><StatusPill :status="revision.status" /><span class="small-caps">{{ revision.locale?.toUpperCase() }}</span></div>
                                <p style="margin: 0.35rem 0; font-size: 0.85rem">{{ revision.note }}</p>
                                <span class="small-caps">{{ revision.actor || '—' }} · {{ formatDate(revision.created_at) }}</span>
                            </li>
                        </ul>
                    </section>
                    <EmptyState v-else :body="t('cms_no_revisions')" />
                </div>
            </div>

            <div class="form-actions" style="margin-block-start: 1.25rem">
                <button class="btn btn-primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">{{ t('cms_save') }}</button>
                <span v-if="form.errors.status" class="error" role="alert">{{ form.errors.status }}</span>
            </div>
        </form>
    </CmsLayout>
</template>

<style scoped>
.translation-pane + .translation-pane {
    margin-block-start: 1.5rem;
    padding-block-start: 1.25rem;
    border-block-start: 1px solid var(--line);
}
</style>
