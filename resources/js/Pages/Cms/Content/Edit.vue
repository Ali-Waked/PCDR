<script setup>
import { computed, ref } from 'vue'
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

function dateValue(key) {
    return form[key] ? String(form[key]).slice(0, 10) : ''
}
</script>

<template>
    <CmsLayout :title="isCreate ? t('cms_new_item') : t('cms_edit')" :subtitle="label">
        <div class="cms-toolbar">
            <Link :href="basePath" class="btn btn-secondary btn-small">{{ t('ui.back_to_list') }}</Link>
            <StatusPill v-if="record" :status="record.status" />
            <button class="btn btn-secondary btn-small" type="button" @click="togglePreview">{{ t('cms_preview') }}</button>
        </div>

        <form @submit.prevent="submit">
            <div class="cms-form-grid cms-form-grid--split">
                <div>
                    <section class="cms-card">
                        <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_translations') }}</h2>
                        <div v-for="code in locales" :key="code" class="translation-pane">
                            <h3 class="small-caps">{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }}</h3>
                            <div class="form-grid">
                                <div v-for="(value, field) in form.translations[code]" :key="`${code}-${field}`" class="field">
                                    <label class="label" :for="`translation-${code}-${field}`">{{ fieldLabel(field) }} <span class="hint">{{ ['title', 'slug'].includes(field) ? t('cms_required') : t('cms_optional') }}</span></label>
                                    <div v-if="isRichField(field)" class="rich-toolbar" role="toolbar" :aria-label="t('cms_toolbar')">
                                        <span class="small-caps">{{ t('cms_toolbar') }}</span>
                                        <button class="btn btn-ghost btn-small" type="button" :title="t('cms_toolbar_bold')" @click="wrapSelection(code, field, '<strong>', '</strong>')">{{ t('cms_toolbar_bold') }}</button>
                                        <button class="btn btn-ghost btn-small" type="button" :title="t('cms_toolbar_italic')" @click="wrapSelection(code, field, '<em>', '</em>')">{{ t('cms_toolbar_italic') }}</button>
                                        <button class="btn btn-ghost btn-small" type="button" :title="t('cms_toolbar_heading')" @click="wrapSelection(code, field, '<h3>', '</h3>')">{{ t('cms_toolbar_heading') }}</button>
                                        <button class="btn btn-ghost btn-small" type="button" :title="t('cms_toolbar_list')" @click="wrapList(code, field)">{{ t('cms_toolbar_list') }}</button>
                                        <button class="btn btn-ghost btn-small" type="button" :title="t('cms_toolbar_link')" @click="insertLink(code, field)">{{ t('cms_toolbar_link') }}</button>
                                        <button class="btn btn-ghost btn-small" type="button" :title="t('cms_toolbar_quote')" @click="wrapSelection(code, field, '<blockquote>', '</blockquote>')">{{ t('cms_toolbar_quote') }}</button>
                                    </div>
                                    <textarea
                                        v-if="isLongField(field)"
                                        :id="`translation-${code}-${field}`"
                                        :ref="(el) => setFieldRef(`${code}.${field}`, el)"
                                        v-model="form.translations[code][field]"
                                        class="textarea"
                                        :rows="field === 'body' ? 8 : 4"
                                        :aria-invalid="!! form.errors[`translations.${code}.${field}`]"
                                    ></textarea>
                                    <input
                                        v-else
                                        :id="`translation-${code}-${field}`"
                                        v-model="form.translations[code][field]"
                                        class="input"
                                        type="text"
                                        :dir="['slug', 'canonical_url', 'og_image'].includes(field) ? 'ltr' : undefined"
                                        :aria-invalid="!! form.errors[`translations.${code}.${field}`]"
                                    >
                                    <span v-if="form.errors[`translations.${code}.${field}`]" class="error" role="alert">{{ form.errors[`translations.${code}.${field}`] }}</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="showPreview" class="cms-card">
                        <div class="cms-card__head">
                            <h2 style="font-size: 1.05rem; margin: 0">{{ t('cms_preview') }}</h2>
                            <button class="btn btn-ghost btn-small" type="button" @click="togglePreview">{{ t('cms_close') }}</button>
                        </div>
                        <div v-for="entry in previewEntries" :key="entry.key" class="preview-entry">
                            <h3 class="small-caps">{{ entry.heading }}</h3>
                            <p class="preview-text">{{ entry.text }}</p>
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
                                <label class="checkbox-row"><input v-model="form.is_featured" type="checkbox"><span>{{ t('cms_section_enabled') }}</span></label>
                            </template>

                            <template v-if="resource === 'programs'">
                                <label class="field"><span>{{ t('navigation.pillars') }}</span>
                                    <select v-model="form.strategic_pillar_id" class="select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('start') }}</span><input v-model="form.start_date" class="input" type="date" dir="ltr"></label>
                                <label class="field"><span>{{ t('end') }}</span><input v-model="form.end_date" class="input" type="date" dir="ltr"></label>
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
                            </template>

                            <template v-if="resource === 'news'">
                                <label class="field"><span>{{ t('ui.category') }}</span>
                                    <select v-model="form.article_category_id" class="select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.categories || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                </label>
                                <label class="field"><span>{{ t('navigation.pillars') }}</span>
                                    <select v-model="form.strategic_pillar_id" class="select"><option value="">{{ t('cms_optional') }}</option><option v-for="item in options.pillars || []" :key="item.id" :value="item.id">{{ item.label }}</option></select>
                                </label>
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
                                <label class="field"><span>{{ t('published_on') }}</span><input v-model="form.publication_date" class="input" type="date" dir="ltr"></label>
                                <label class="field"><span>{{ t('version') }}</span><input v-model="form.version" class="input" type="text" maxlength="32" dir="ltr"></label>
                                <label class="checkbox-row"><input v-model="form.is_restricted" type="checkbox"><span>{{ t('cms_restricted_file') }}</span></label>
                            </template>

                            <div v-for="field in mediaFields" :key="`media-${field}`" class="field media-picker">
                                <span class="label">{{ mediaFieldLabel(field) }}</span>
                                <div class="media-picker__current">
                                    <img
                                        v-if="selectedMedia(field)?.url && (! selectedMedia(field)?.type || selectedMedia(field)?.type === 'image')"
                                        class="media-picker__thumb"
                                        :src="selectedMedia(field).url"
                                        :alt="selectedMedia(field).alt || selectedMedia(field).name"
                                        loading="lazy"
                                    >
                                    <span class="media-picker__name">{{ selectedMedia(field)?.name || t('cms_select_media') }}</span>
                                    <button class="btn btn-secondary btn-small" type="button" @click="toggleMediaPicker(field)">{{ selectedMedia(field) ? t('cms_edit') : t('cms_select_media') }}</button>
                                    <button v-if="selectedMedia(field)" class="btn btn-danger btn-small" type="button" @click="clearMedia(field)">{{ t('cms_remove_item') }}</button>
                                </div>
                                <div v-if="openMediaPickers[field]" class="media-picker__list">
                                    <span class="small-caps">{{ t('cms_media_picker') }}</span>
                                    <div v-if="mediaOptions.length" class="media-picker__options">
                                        <div v-for="item in mediaOptions" :key="item.id" class="media-picker__option">
                                            <img
                                                v-if="item.url && (! item.type || item.type === 'image')"
                                                class="media-picker__thumb"
                                                :src="item.url"
                                                :alt="item.alt || item.name"
                                                loading="lazy"
                                            >
                                            <span class="media-picker__name">{{ item.name }}</span>
                                            <button class="btn btn-ghost btn-small" type="button" @click="insertMedia(field, item)">{{ t('cms_insert_media') }}</button>
                                        </div>
                                    </div>
                                    <p v-else class="hint" style="margin: 0">{{ t('cms_select_media') }}</p>
                                    <button class="btn btn-ghost btn-small" type="button" @click="closeMediaPicker(field)">{{ t('cms_close') }}</button>
                                </div>
                            </div>
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

.rich-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    padding-block-end: 0.25rem;
}

.media-picker__current,
.media-picker__option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.media-picker__thumb {
    inline-size: 40px;
    block-size: 40px;
    flex: none;
    object-fit: cover;
    border: 1px solid var(--line);
    border-radius: 0.35rem;
}

.media-picker__name {
    flex: 1;
    min-inline-size: 0;
    overflow-wrap: anywhere;
    font-size: 0.85rem;
}

.media-picker__list {
    display: grid;
    gap: 0.4rem;
    max-block-size: 220px;
    padding: 0.5rem;
    overflow: auto;
    border: 1px solid var(--line);
    border-radius: 0.5rem;
}

.media-picker__options {
    display: grid;
    gap: 0.35rem;
}

.preview-entry + .preview-entry {
    margin-block-start: 1rem;
    padding-block-start: 0.75rem;
    border-block-start: 1px solid var(--line);
}

.preview-text {
    margin: 0.25rem 0 0;
    white-space: pre-wrap;
    font-size: 0.9rem;
}
</style>
