<script setup>
import { useForm } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import SelectionControl from '../../../Components/Selection/SelectionControl.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    sections: { type: Array, default: () => [] },
    preview: { type: Array, default: () => [] },
})

const { t, localPath } = useI18n()
const locales = ['ar', 'en']
const itemTypes = ['inline', 'pillar', 'article', 'publication', 'page', 'project']
const itemTypeOptions = itemTypes.map((type) => ({ value: type, label: type }))

function emptyItemTranslations() {
    return Object.fromEntries(locales.map((code) => [code, {
        label: '',
        title: '',
        body: '',
        display_value: '',
        url: '',
    }]))
}

function cloneSections() {
    return props.sections.map((section) => ({
        id: section.id,
        is_enabled: section.enabled !== false,
        display_order: section.order || 0,
        translations: Object.fromEntries(locales.map((code) => [code, {
            eyebrow: section.translations?.[code]?.eyebrow || '',
            title: section.translations?.[code]?.title || '',
            body: section.translations?.[code]?.body || '',
            cta_label: section.translations?.[code]?.cta_label || '',
            cta_link: section.translations?.[code]?.cta_link || '',
        }])),
        items: (section.items || []).map((item) => ({
            id: item.id,
            item_type: item.itemType,
            item_id: item.itemId,
            item_key: item.itemKey || '',
            is_published: item.isPublished !== false,
            numeric_value: item.numericValue ?? '',
            translations: Object.fromEntries(locales.map((code) => [code, {
                label: item.translations?.[code]?.label || '',
                title: item.translations?.[code]?.title || '',
                body: item.translations?.[code]?.body || '',
                display_value: item.translations?.[code]?.display_value || '',
                url: item.translations?.[code]?.url || '',
            }])),
        })),
    }))
}

const form = useForm({ sections: cloneSections() })

function addItem(section) {
    section.items.push({
        item_type: 'inline',
        item_id: '',
        item_key: '',
        is_published: true,
        numeric_value: '',
        translations: emptyItemTranslations(),
    })
}

function save() {
    form.put(localPath('cms/homepage'), { preserveScroll: true })
}
</script>

<template>
    <CmsDashboardLayout :title="t('ui.cms_homepage')" :subtitle="t('cms_sections')">
        <div class="pc-stack pcdr-enter">
            <div class="pc-page-actions">
                <button class="pc-btn pc-btn--primary" type="submit" form="homepage-form" :disabled="form.processing">
                    <UiIcon name="check" :size="16" />
                    {{ t('cms_save') }}
                </button>
            </div>

            <form id="homepage-form" class="pc-stack" @submit.prevent="save">
                <div class="pc-accordion">
                    <details
                        v-for="(section, index) in form.sections"
                        :key="section.id"
                        class="pc-accordion__item"
                        :open="index < 3"
                    >
                        <summary class="pc-accordion__trigger">
                            <UiIcon name="chevronRight" :size="17" />
                            <span>{{ section.translations.ar.title || section.translations.en.title || props.sections[index]?.key }}</span>
                            <span class="pc-badge pc-badge--slate">{{ section.display_order }}</span>
                        </summary>

                        <div class="pc-accordion__body">
                            <div class="pc-form-grid pc-form-grid--2">
                                <label class="pc-checkbox">
                                    <input v-model="section.is_enabled" type="checkbox">
                                    <span>{{ t('cms_section_enabled') }}</span>
                                </label>
                                <label class="pc-field">
                                    <span class="pc-label">{{ t('cms_order') }}</span>
                                    <input v-model.number="section.display_order" class="pc-input" type="number" min="0" dir="ltr">
                                </label>
                            </div>

                            <div v-for="code in locales" :key="code" class="pc-translation-pane">
                                <h3 class="pc-eyebrow">{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }}</h3>
                                <div class="pc-form-grid">
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('cms_intro') }}</span>
                                        <input v-model="section.translations[code].eyebrow" class="pc-input" type="text" maxlength="120">
                                    </label>
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('cms_title') }}</span>
                                        <input v-model="section.translations[code].title" class="pc-input" type="text" maxlength="255">
                                    </label>
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('cms_body') }}</span>
                                        <textarea v-model="section.translations[code].body" class="pc-textarea" rows="3" maxlength="5000"></textarea>
                                    </label>
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('cms_cta') }}</span>
                                        <input v-model="section.translations[code].cta_label" class="pc-input" type="text" maxlength="120">
                                    </label>
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('ui.website') }}</span>
                                        <input v-model="section.translations[code].cta_link" class="pc-input" type="text" maxlength="255" dir="ltr">
                                    </label>
                                </div>
                            </div>

                            <div class="pc-stack">
                                <h3 class="pc-eyebrow">{{ t('cms_add_item') }}</h3>
                                <div v-for="(item, itemIndex) in section.items" :key="item.id || `new-${itemIndex}`" class="pc-stack pc-picker">
                                    <div class="pc-form-grid pc-form-grid--2">
                                        <div class="pc-field">
                                            <SelectionControl
                                                v-model="item.item_type"
                                                :label="t('ui.category')"
                                                :options="itemTypeOptions"
                                                :searchable="false"
                                                size="sm"
                                            />
                                        </div>
                                        <label v-if="item.item_type !== 'inline'" class="pc-field">
                                            <span class="pc-label">ID</span>
                                            <input v-model="item.item_id" class="pc-input" type="number" min="1" dir="ltr" required>
                                        </label>
                                        <template v-else>
                                            <label class="pc-field">
                                                <span class="pc-label">Key</span>
                                                <input v-model="item.item_key" class="pc-input" type="text" maxlength="64" dir="ltr">
                                            </label>
                                            <label class="pc-field">
                                                <span class="pc-label">Numeric value</span>
                                                <input v-model="item.numeric_value" class="pc-input" type="number" step="any" dir="ltr">
                                            </label>
                                            <label class="pc-checkbox">
                                                <input v-model="item.is_published" type="checkbox">
                                                <span>{{ t('cms_section_enabled') }}</span>
                                            </label>
                                        </template>
                                        <button class="pc-btn pc-btn--danger pc-btn--sm" type="button" @click="section.items.splice(itemIndex, 1)">
                                            <UiIcon name="trash" :size="14" />
                                            {{ t('cms_remove_item') }}
                                        </button>
                                    </div>

                                    <div v-if="item.item_type === 'inline'" class="pc-stack">
                                        <div v-for="code in locales" :key="code" class="pc-translation-pane">
                                            <h4 class="pc-eyebrow">{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }}</h4>
                                            <div class="pc-form-grid">
                                                <label class="pc-field">
                                                    <span class="pc-label">{{ t('cms_title') }}</span>
                                                    <input v-model="item.translations[code].title" class="pc-input" type="text" maxlength="255">
                                                </label>
                                                <label class="pc-field">
                                                    <span class="pc-label">{{ t('cms_cta') }}</span>
                                                    <input v-model="item.translations[code].label" class="pc-input" type="text" maxlength="120">
                                                </label>
                                                <label class="pc-field">
                                                    <span class="pc-label">{{ t('cms_body') }}</span>
                                                    <textarea v-model="item.translations[code].body" class="pc-textarea" rows="2" maxlength="5000"></textarea>
                                                </label>
                                                <label class="pc-field">
                                                    <span class="pc-label">Display value</span>
                                                    <input v-model="item.translations[code].display_value" class="pc-input" type="text" maxlength="64" dir="ltr">
                                                </label>
                                                <label class="pc-field">
                                                    <span class="pc-label">{{ t('ui.website') }}</span>
                                                    <input v-model="item.translations[code].url" class="pc-input" type="text" maxlength="255" dir="ltr">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button class="pc-btn pc-btn--secondary pc-btn--sm" type="button" @click="addItem(section)">
                                <UiIcon name="plus" :size="15" />
                                {{ t('cms_add_item') }}
                            </button>
                        </div>
                    </details>
                </div>

                <section class="pc-card">
                    <div class="pc-card__head">
                        <h2 class="pc-card__title">{{ t('cms_preview') }}</h2>
                        <span class="pc-badge pc-badge--teal">{{ preview.length }}</span>
                    </div>
                    <ul class="pc-list">
                        <li v-for="section in preview" :key="section.key" class="pc-list__item">
                            <div class="pc-list__copy">
                                <span class="pc-list__title">{{ section.title || section.key }}</span>
                                <span v-if="section.body" class="pc-list__meta">{{ section.body }}</span>
                            </div>
                            <span class="pc-list__meta">{{ section.items.length }} {{ t('cms_add_item') }}</span>
                        </li>
                    </ul>
                </section>

                <div class="pc-form-actions">
                    <button class="pc-btn pc-btn--primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                        <UiIcon name="check" :size="16" />
                        {{ t('cms_save') }}
                    </button>
                </div>
            </form>
        </div>
    </CmsDashboardLayout>
</template>
