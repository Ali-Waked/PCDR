<script setup>
import { useForm } from '@inertiajs/vue3'
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import UiIcon from '../../../Components/Dashboard/UiIcon.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    sections: { type: Array, default: () => [] },
    preview: { type: Array, default: () => [] },
})

const { t, localPath } = useI18n()
const locales = ['ar', 'en']

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
        items: section.items.map((item) => ({
            item_type: item.itemType,
            item_id: item.itemId,
        })),
    }))
}

const form = useForm({ sections: cloneSections() })

function addItem(section) {
    section.items.push({ item_type: 'page', item_id: '' })
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

                            <div v-if="section.items.length" class="pc-stack">
                                <h3 class="pc-eyebrow">{{ t('cms_add_item') }}</h3>
                                <div v-for="(item, itemIndex) in section.items" :key="itemIndex" class="pc-form-grid pc-form-grid--2 pc-picker">
                                    <label class="pc-field">
                                        <span class="pc-label">{{ t('ui.category') }}</span>
                                        <select v-model="item.item_type" class="pc-select">
                                            <option v-for="type in ['pillar', 'article', 'publication', 'page', 'project']" :key="type" :value="type">{{ type }}</option>
                                        </select>
                                    </label>
                                    <label class="pc-field">
                                        <span class="pc-label">ID</span>
                                        <input v-model="item.item_id" class="pc-input" type="number" min="1" dir="ltr" required>
                                    </label>
                                    <button class="pc-btn pc-btn--danger pc-btn--sm" type="button" @click="section.items.splice(itemIndex, 1)">
                                        <UiIcon name="trash" :size="14" />
                                        {{ t('cms_remove_item') }}
                                    </button>
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
