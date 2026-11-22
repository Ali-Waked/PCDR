<script setup>
import { useForm } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
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
    <CmsLayout :title="t('ui.cms_homepage')" :subtitle="t('cms_sections')">
        <form @submit.prevent="save">
            <section v-for="(section, index) in form.sections" :key="section.id" class="cms-card">
                <details :open="index < 3">
                    <summary style="cursor: pointer; font-weight: 700">
                        {{ section.translations.ar.title || section.translations.en.title || props.sections[index]?.key }}
                    </summary>

                    <div class="form-grid form-grid--two" style="margin-block-start: 1rem">
                        <label class="checkbox-row"><input v-model="section.is_enabled" type="checkbox"><span>{{ t('cms_section_enabled') }}</span></label>
                        <label class="field"><span>{{ t('cms_order') }}</span><input v-model.number="section.display_order" class="input" type="number" min="0" dir="ltr"></label>
                    </div>

                    <div v-for="code in locales" :key="code" class="translation-pane">
                        <h3 class="small-caps">{{ code === 'ar' ? t('cms_arabic') : t('cms_english') }}</h3>
                        <div class="form-grid">
                            <label class="field"><span>{{ t('cms_intro') }}</span><input v-model="section.translations[code].eyebrow" class="input" type="text" maxlength="120"></label>
                            <label class="field"><span>{{ t('cms_title') }}</span><input v-model="section.translations[code].title" class="input" type="text" maxlength="255"></label>
                            <label class="field"><span>{{ t('cms_body') }}</span><textarea v-model="section.translations[code].body" class="textarea" rows="3" maxlength="5000"></textarea></label>
                            <label class="field"><span>{{ t('cms_cta') }}</span><input v-model="section.translations[code].cta_label" class="input" type="text" maxlength="120"></label>
                            <label class="field"><span>{{ t('ui.website') }}</span><input v-model="section.translations[code].cta_link" class="input" type="text" maxlength="255" dir="ltr"></label>
                        </div>
                    </div>

                    <div v-if="section.items.length" style="margin-block-start: 1rem">
                        <h3 class="small-caps">{{ t('cms_add_item') }}</h3>
                        <div v-for="(item, itemIndex) in section.items" :key="itemIndex" class="form-grid form-grid--two" style="margin-block-end: 0.6rem">
                            <label class="field"><span>{{ t('category') }}</span>
                                <select v-model="item.item_type" class="select">
                                    <option v-for="type in ['pillar', 'article', 'publication', 'page', 'project']" :key="type" :value="type">{{ type }}</option>
                                </select>
                            </label>
                            <label class="field"><span>ID</span><input v-model="item.item_id" class="input" type="number" min="1" dir="ltr" required></label>
                            <button class="btn btn-danger btn-small" type="button" @click="section.items.splice(itemIndex, 1)">{{ t('cms_remove_item') }}</button>
                        </div>
                    </div>
                    <button class="btn btn-secondary btn-small" type="button" @click="addItem(section)">{{ t('cms_add_item') }}</button>
                </details>
            </section>

            <section class="cms-card">
                <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ t('cms_preview') }}</h2>
                <ul class="revision-list">
                    <li v-for="section in preview" :key="section.key">
                        <strong>{{ section.title || section.key }}</strong>
                        <p v-if="section.body" style="margin: 0.25rem 0; font-size: 0.85rem">{{ section.body }}</p>
                        <span class="small-caps">{{ section.items.length }} {{ t('cms_add_item') }}</span>
                    </li>
                </ul>
            </section>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">{{ t('cms_save') }}</button>
            </div>
        </form>
    </CmsLayout>
</template>

<style scoped>
.translation-pane {
    margin-block-start: 1.25rem;
    padding-block-start: 1rem;
    border-block-start: 1px solid var(--line);
}
</style>
