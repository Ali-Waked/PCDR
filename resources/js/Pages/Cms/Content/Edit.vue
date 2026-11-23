<script setup>
import { computed, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import CmsStatusBadge from '../../../Components/Dashboard/CmsStatusBadge.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
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

const { t, localPath, formatDate, richText } = useI18n()
const locales = ['ar', 'en']
const richFields = ['body', 'description', 'objectives']

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
const activeLocale = ref('ar')

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

const hasExtras = computed(() => Object.keys(initialExtras()).length > 0)

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

function isRichField(field) {
    return richFields.includes(field)
}

const mediaOptions = computed(() => Array.isArray(props.options?.media) ? props.options.media : [])

const mediaFieldLabels = {
    featured_image_id: 'ui.cms_media',
    cover_media_id: 'ui.cms_media',
    document_media_id: 'documents',
}

const mediaFields = computed(() => {
    if (props.resource === 'publications') {
        return ['document_media_id', 'cover_media_id']
    }
    if (props.resource === 'news') {
        return ['cover_media_id']
    }
    if (['pillars', 'programs', 'projects'].includes(props.resource)) {
        return ['featured_image_id']
    }
    return []
})

function mediaFieldLabel(field) {
    return t(mediaFieldLabels[field] || field)
}

function selectedMedia(field) {
    const id = form[field]
    if (! id) {
        return null
    }
    return mediaOptions.value.find((item) => String(item.id) === String(id)) || null
}

const openMediaPickers = ref({})

function toggleMediaPicker(field) {
    openMediaPickers.value[field] = ! openMediaPickers.value[field]
}

function closeMediaPicker(field) {
    openMediaPickers.value[field] = false
}

function insertMedia(field, item) {
    form[field] = item.id
    closeMediaPicker(field)
}

function clearMedia(field) {
    form[field] = ''
    closeMediaPicker(field)
}

const fieldRefs = ref({})

function setFieldRef(key, el) {
    if (el) {
        fieldRefs.value[key] = el
    } else {
        delete fieldRefs.value[key]
    }
}

function replaceRange(el, replacement, start, end, innerLength) {
    if (typeof el.setRangeText === 'function') {
        el.setRangeText(replacement, start, end, 'end')
    } else {
        el.value = el.value.slice(0, start) + replacement + el.value.slice(end)
    }
    el.focus()
    if (typeof el.setSelectionRange === 'function') {
        const caret = start + innerLength
        el.setSelectionRange(caret, caret)
    }
}

function wrapSelection(code, field, prefix, suffix) {
    const el = fieldRefs.value[`${code}.${field}`]
    if (! el) {
        return
    }
    const start = el.selectionStart ?? el.value.length
    const end = el.selectionEnd ?? start
    const selected = el.value.slice(start, end)
    replaceRange(el, `${prefix}${selected}${suffix}`, start, end, prefix.length + selected.length)
    form.translations[code][field] = el.value
}

function wrapList(code, field) {
    const el = fieldRefs.value[`${code}.${field}`]
    if (! el) {
        return
    }
    const start = el.selectionStart ?? el.value.length
    const end = el.selectionEnd ?? start
    const items = el.value.slice(start, end).split('\n').map((line) => `<li>${line}</li>`).join('')
    const prefix = '<ul>'
    replaceRange(el, `${prefix}${items}</ul>`, start, end, prefix.length + items.length)
    form.translations[code][field] = el.value
}

function insertLink(code, field) {
    const answer = window.prompt(t('cms_toolbar_link'), 'https://')
    if (answer === null) {
        return
    }
    const href = String(answer).trim().replace(/["'<>\s]/g, '')
    const safeHref = /^(https?:\/\/|mailto:|\/|#)/i.test(href) ? href : '#'
    wrapSelection(code, field, `<a href="${safeHref}">`, '</a>')
}

const showPreview = ref(false)

function togglePreview() {
    showPreview.value = ! showPreview.value
}

const previewEntries = computed(() => locales.flatMap((code) => richFields
    .filter((field) => Object.prototype.hasOwnProperty.call(form.translations[code] || {}, field))
    .map((field) => ({
        key: `${code}.${field}`,
        heading: `${code === 'ar' ? t('cms_arabic') : t('cms_english')} — ${fieldLabel(field)}`,
        text: richText(form.translations[code][field]),
    }))))

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
</script>

<template>
    <CmsDashboardLayout :title="isCreate ? t('cms_new_item') : t('cms_edit')" :subtitle="label">
        <div class="pc-stack pcdr-enter">
            <div class="pc-toolbar">
                <Link :href="basePath" class="pc-btn pc-btn--secondary pc-btn--sm">
                    <UiIcon name="arrowRight" :size="15" />
                    {{ t('ui.back_to_list') }}
                </Link>
                <CmsStatusBadge v-if="record" :status="record.status" />
                <div class="pc-toolbar__spacer" />
                <button class="pc-btn pc-btn--secondary pc-btn--sm" type="button" @click="togglePreview">
                    <UiIcon :name="showPreview ? 'eyeOff' : 'eye'" :size="15" />
                    {{ showPreview ? t('cms_close') : t('cms_preview') }}
                </button>
            </div>

            <form class="pc-stack" @submit.prevent="submit">
                <div class="pc-grid pc-grid--content">
                    <div class="pc-stack">
                        <section class="pc-card">
                            <div class="pc-card__head">
                                <div>
                                    <h2 class="pc-card__title">{{ t('cms_translations') }}</h2>
                                    <p class="pc-card__subtitle">{{ t('ui.dashboard_editorial') }}</p>
                                </div>
                                <div class="pc-tabs" role="tablist" :aria-label="t('cms_translations')">
                                    <button
                                        v-for="code in locales"
                                        :key="`tab-${code}`"
                                        type="button"
                                        class="pc-tab"
                                        :class="{ 'is-active': activeLocale === code }"
                                        :aria-selected="activeLocale === code ? 'true' : 'false'"
                                        @click="activeLocale = code"
                                    >
                                        {{ code === 'ar' ? t('cms_arabic') : t('cms_english') }}
                                    </button>
                                </div>
                            </div>
                            <div class="pc-card__body pc-stack">
                                <div
                                    v-for="code in locales"
                                    :key="code"
                                    v-show="activeLocale === code"
                                    class="pc-translation-pane"
                                >
                                    <div v-for="(value, field) in form.translations[code]" :key="`${code}-${field}`" class="pc-field">
                                        <label class="pc-label" :for="`translation-${code}-${field}`">
                                            {{ fieldLabel(field) }}
                                            <span class="pc-label__optional">{{ ['title', 'slug'].includes(field) ? t('cms_required') : t('cms_optional') }}</span>
                                        </label>

                                        <div v-if="isRichField(field)" class="pc-editor">
                                            <div class="pc-editor__toolbar" role="toolbar" :aria-label="t('cms_toolbar')">
                                                <span class="pc-eyebrow">{{ t('cms_toolbar') }}</span>
                                                <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" :title="t('cms_toolbar_bold')" @click="wrapSelection(code, field, '<strong>', '</strong>')">{{ t('cms_toolbar_bold') }}</button>
                                                <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" :title="t('cms_toolbar_italic')" @click="wrapSelection(code, field, '<em>', '</em>')">{{ t('cms_toolbar_italic') }}</button>
                                                <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" :title="t('cms_toolbar_heading')" @click="wrapSelection(code, field, '<h3>', '</h3>')">{{ t('cms_toolbar_heading') }}</button>
                                                <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" :title="t('cms_toolbar_list')" @click="wrapList(code, field)">{{ t('cms_toolbar_list') }}</button>
                                                <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" :title="t('cms_toolbar_link')" @click="insertLink(code, field)">{{ t('cms_toolbar_link') }}</button>
                                                <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" :title="t('cms_toolbar_quote')" @click="wrapSelection(code, field, '<blockquote>', '</blockquote>')">{{ t('cms_toolbar_quote') }}</button>
                                            </div>
                                            <textarea
                                                :id="`translation-${code}-${field}`"
                                                :ref="(el) => setFieldRef(`${code}.${field}`, el)"
                                                v-model="form.translations[code][field]"
                                                class="pc-textarea pc-editor__area"
                                                :rows="field === 'body' ? 8 : 4"
                                                :aria-invalid="!! form.errors[`translations.${code}.${field}`]"
                                            ></textarea>
                                        </div>
                                        <textarea
                                            v-else-if="isLongField(field)"
                                            :id="`translation-${code}-${field}`"
                                            v-model="form.translations[code][field]"
                                            class="pc-textarea"
                                            :rows="field === 'body' ? 8 : 4"
                                            :aria-invalid="!! form.errors[`translations.${code}.${field}`]"
                                        ></textarea>
                                        <input
                                            v-else
                                            :id="`translation-${code}-${field}`"
                                            v-model="form.translations[code][field]"
                                            class="pc-input"
                                            type="text"
                                            :dir="['slug', 'canonical_url', 'og_image'].includes(field) ? 'ltr' : undefined"
                                            :aria-invalid="!! form.errors[`translations.${code}.${field}`]"
                                        >
                                        <span v-if="form.errors[`translations.${code}.${field}`]" class="pc-error" role="alert">{{ form.errors[`translations.${code}.${field}`] }}</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section v-if="showPreview" class="pc-card">
                            <div class="pc-card__head">
                                <h2 class="pc-card__title">{{ t('cms_preview') }}</h2>
                                <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="togglePreview">
                                    <UiIcon name="close" :size="14" />
                                    {{ t('cms_close') }}
                                </button>
                            </div>
                            <div class="pc-card__body pc-stack">
                                <div v-for="entry in previewEntries" :key="entry.key" class="pc-translation-pane">
                                    <h3 class="pc-eyebrow">{{ entry.heading }}</h3>
                                    <p class="pc-preview-text">{{ entry.text }}</p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="pc-stack">
                        <section class="pc-card">
                            <div class="pc-card__head">
                                <h2 class="pc-card__title">{{ t('cms_basic_data') }}</h2>
                            </div>
                            <div class="pc-card__body pc-form-grid">
                                <input type="hidden" :value="form.status" name="status">
                                <label class="pc-field">
                                    <span class="pc-label">{{ t('cms_publish_status') }}</span>
                                    <select class="pc-select" :value="form.status" @change="applyTransition($event.target.value)">
                                        <option v-for="status in allowedStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                                    </select>
                                </label>
                                <label class="pc-field">
                                    <span class="pc-label">{{ t('published_on') }}</span>
                                    <input v-model="form.published_at" class="pc-input" type="date" dir="ltr">
                                </label>
                                <label class="pc-field">
                                    <span class="pc-label">{{ t('cms_order') }}</span>
                                    <input v-model.number="form.display_order" class="pc-input" type="number" min="0" dir="ltr">
                                </label>
                            </div>
                        </section>

                        <section v-if="hasExtras" class="pc-card">
                            <div class="pc-card__head">
                                <h2 class="pc-card__title">{{ t('cms_relations') }}</h2>
                            </div>
                            <div class="pc-card__body pc-form-grid">
                                <template v-if="resource === 'pages'">
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('parent_page') }}</span>
                                        <input v-model="form.parent_id" class="pc-input" type="number" min="1" dir="ltr">
                                    </label>
                                    <label class="pc-checkbox"><input v-model="form.is_system" type="checkbox"><span>{{ t('institution') }}</span></label>
                                </template>

                                <template v-if="resource === 'pillars'">
                                    <label class="pc-field"><span class="pc-label">Code</span><input v-model="form.code" class="pc-input" type="text" maxlength="2" dir="ltr"></label>
                                    <label class="pc-checkbox"><input v-model="form.is_featured" type="checkbox"><span>{{ t('cms_section_enabled') }}</span></label>
                                </template>

                                <template v-if="resource === 'programs'">
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('navigation.pillars') }}</span>
                                        <select v-model="form.strategic_pillar_id" class="pc-select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                    </label>
                                    <label class="pc-field"><span class="pc-label">{{ t('start') }}</span><input v-model="form.start_date" class="pc-input" type="date" dir="ltr"></label>
                                    <label class="pc-field"><span class="pc-label">{{ t('end') }}</span><input v-model="form.end_date" class="pc-input" type="date" dir="ltr"></label>
                                </template>

                                <template v-if="resource === 'projects'">
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('ui.cms_programs') }}</span>
                                        <select v-model="form.program_id" class="pc-select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.programs || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                    </label>
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('navigation.pillars') }}</span>
                                        <select v-model="form.strategic_pillar_id" class="pc-select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                    </label>
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('cms_publish_status') }}</span>
                                        <select v-model="form.project_status" class="pc-select"><option v-for="item in options.projectStatuses || []" :key="item.value" :value="item.value">{{ item.label }}</option></select>
                                    </label>
                                    <label class="pc-field"><span class="pc-label">{{ t('start') }}</span><input v-model="form.start_date" class="pc-input" type="date" dir="ltr"></label>
                                    <label class="pc-field"><span class="pc-label">{{ t('end') }}</span><input v-model="form.end_date" class="pc-input" type="date" dir="ltr"></label>
                                </template>

                                <template v-if="resource === 'news'">
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('ui.category') }}</span>
                                        <select v-model="form.article_category_id" class="pc-select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.categories || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                    </label>
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('navigation.pillars') }}</span>
                                        <select v-model="form.strategic_pillar_id" class="pc-select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                    </label>
                                    <label class="pc-field"><span class="pc-label">{{ t('activity') }}</span><input v-model="form.event_date" class="pc-input" type="date" dir="ltr"></label>
                                    <label class="pc-checkbox"><input v-model="form.is_featured" type="checkbox"><span>{{ t('cms_section_enabled') }}</span></label>
                                    <fieldset v-if="options.tags?.length" class="pc-field pc-field--full">
                                        <legend class="pc-label">{{ t('available_options') }}</legend>
                                        <div class="pc-selection-list">
                                            <label v-for="tag in options.tags" :key="tag.id" class="pc-checkbox"><input v-model="form.tags" type="checkbox" :value="tag.id"><span>{{ tag.label }}</span></label>
                                        </div>
                                    </fieldset>
                                </template>

                                <template v-if="resource === 'publications'">
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('ui.category') }}</span>
                                        <select v-model="form.category" class="pc-select"><option v-for="item in options.categories || []" :key="item.value" :value="item.value">{{ item.label }}</option></select>
                                    </label>
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('navigation.pillars') }}</span>
                                        <select v-model="form.strategic_pillar_id" class="pc-select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                    </label>
                                    <label class="pc-field"><span class="pc-label">{{ t('published_on') }}</span><input v-model="form.publication_date" class="pc-input" type="date" dir="ltr"></label>
                                    <label class="pc-field"><span class="pc-label">{{ t('version') }}</span><input v-model="form.version" class="pc-input" type="text" maxlength="32" dir="ltr"></label>
                                    <label class="pc-checkbox"><input v-model="form.is_restricted" type="checkbox"><span>{{ t('cms_restricted_file') }}</span></label>
                                </template>

                                <div v-for="field in mediaFields" :key="`media-${field}`" class="pc-field pc-field--full pc-picker">
                                    <span class="pc-label">{{ mediaFieldLabel(field) }}</span>
                                    <div class="pc-picker__current">
                                        <img
                                            v-if="selectedMedia(field)?.url && (! selectedMedia(field)?.type || selectedMedia(field)?.type === 'image')"
                                            class="pc-media-cover"
                                            :src="selectedMedia(field).url"
                                            :alt="selectedMedia(field).alt || selectedMedia(field).name"
                                            loading="lazy"
                                        >
                                        <span class="pc-picker__name">{{ selectedMedia(field)?.name || t('cms_select_media') }}</span>
                                        <button class="pc-btn pc-btn--secondary pc-btn--sm" type="button" @click="toggleMediaPicker(field)">
                                            {{ selectedMedia(field) ? t('ui.edit') : t('cms_select_media') }}
                                        </button>
                                        <button v-if="selectedMedia(field)" class="pc-btn pc-btn--danger pc-btn--sm" type="button" @click="clearMedia(field)">
                                            {{ t('cms_remove_item') }}
                                        </button>
                                    </div>
                                    <div v-if="openMediaPickers[field]" class="pc-picker__list">
                                        <span class="pc-eyebrow">{{ t('cms_media_picker') }}</span>
                                        <div v-if="mediaOptions.length" class="pc-stack">
                                            <div v-for="item in mediaOptions" :key="item.id" class="pc-picker__option">
                                                <img
                                                    v-if="item.url && (! item.type || item.type === 'image')"
                                                    class="pc-media-cover"
                                                    :src="item.url"
                                                    :alt="item.alt || item.name"
                                                    loading="lazy"
                                                >
                                                <span class="pc-picker__name">{{ item.name }}</span>
                                                <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="insertMedia(field, item)">{{ t('cms_insert_media') }}</button>
                                            </div>
                                        </div>
                                        <p v-else class="pc-hint">{{ t('cms_select_media') }}</p>
                                        <button class="pc-btn pc-btn--ghost pc-btn--sm" type="button" @click="closeMediaPicker(field)">{{ t('cms_close') }}</button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section v-if="revisions.length" class="pc-card">
                            <div class="pc-card__head">
                                <h2 class="pc-card__title">{{ t('cms_revisions') }}</h2>
                                <span class="pc-badge pc-badge--slate">{{ revisions.length }}</span>
                            </div>
                            <div class="pc-card__body pc-timeline">
                                <article v-for="revision in revisions" :key="revision.id" class="pc-timeline__item">
                                    <span class="pc-timeline__dot" aria-hidden="true"></span>
                                    <div class="pc-table__actions">
                                        <CmsStatusBadge :status="revision.status" />
                                        <span class="pc-badge pc-badge--slate" dir="ltr">{{ revision.locale?.toUpperCase() }}</span>
                                    </div>
                                    <p class="pc-timeline__body">{{ revision.note }}</p>
                                    <p class="pc-timeline__meta">{{ revision.actor || '—' }} · {{ formatDate(revision.created_at) }}</p>
                                </article>
                            </div>
                        </section>
                        <EmptyState v-else :title="t('cms_no_revisions')" icon="history" />
                    </div>
                </div>

                <div class="pc-form-actions">
                    <button class="pc-btn pc-btn--primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                        <UiIcon name="check" :size="16" />
                        {{ t('cms_save') }}
                    </button>
                    <span v-if="form.errors.status" class="pc-error" role="alert">{{ form.errors.status }}</span>
                </div>
            </form>
        </div>
    </CmsDashboardLayout>
</template>
