<script setup>
import { useForm } from '@inertiajs/vue3'
import UiIcon from '../Dashboard/UiIcon.vue'
import { useI18n } from '../../composables/useI18n'

const props = defineProps({
    menu: { type: Object, required: true },
    routeKeys: { type: Array, default: () => [] },
})

const { t, localPath } = useI18n()

function cloneItems() {
    return props.menu.items.map((item) => ({
        id: item.id,
        parent_id: item.parentId || '',
        link_type: item.linkType || 'internal',
        link_value: item.linkValue || '',
        is_enabled: item.enabled !== false,
        display_order: item.order || 0,
        labels: {
            ar: item.labels?.ar || '',
            en: item.labels?.en || '',
        },
    }))
}

const form = useForm({ items: cloneItems() })

function addItem() {
    form.items.push({
        id: null,
        parent_id: '',
        link_type: 'internal',
        link_value: props.routeKeys[0] || 'home',
        is_enabled: true,
        display_order: form.items.length,
        labels: { ar: '', en: '' },
    })
}

function removeItem(index) {
    form.items.splice(index, 1)
}

function save() {
    form.put(`${localPath('cms')}/navigation/${props.menu.id}`, { preserveScroll: true })
}
</script>

<template>
    <section class="pc-card">
        <div class="pc-card__head">
            <div>
                <h2 class="pc-card__title">
                    {{ menu.key === 'header' ? t('cms_menu_primary') : t('cms_menu_footer') }}
                </h2>
                <p class="pc-card__subtitle">{{ form.items.length }} {{ t('cms_add_item') }}</p>
            </div>
            <button class="pc-btn pc-btn--secondary pc-btn--sm" type="button" @click="addItem">
                <UiIcon name="plus" :size="15" />
                {{ t('cms_add_item') }}
            </button>
        </div>

        <form class="pc-card__body pc-stack" @submit.prevent="save">
            <div v-for="(item, index) in form.items" :key="item.id || `new-${index}`" class="pc-nav-item-row">
                <div class="pc-form-grid pc-form-grid--2">
                    <label class="pc-field">
                        <span class="pc-label">{{ t('cms_label_ar') }}</span>
                        <input v-model="item.labels.ar" class="pc-input" type="text" maxlength="120" required>
                    </label>
                    <label class="pc-field">
                        <span class="pc-label">{{ t('cms_label_en') }}</span>
                        <input v-model="item.labels.en" class="pc-input" type="text" maxlength="120" required dir="ltr">
                    </label>
                    <label class="pc-field">
                        <span class="pc-label">{{ t('cms_link_type') }}</span>
                        <select v-model="item.link_type" class="pc-select">
                            <option value="internal">internal</option>
                            <option value="external">external</option>
                        </select>
                    </label>
                    <label class="pc-field">
                        <span class="pc-label">{{ t('cms_link_key') }}</span>
                        <select v-if="item.link_type === 'internal'" v-model="item.link_value" class="pc-select">
                            <option v-for="key in routeKeys" :key="key" :value="key">{{ key }}</option>
                        </select>
                        <input v-else v-model="item.link_value" class="pc-input" type="url" dir="ltr" placeholder="https://">
                    </label>
                    <label class="pc-field">
                        <span class="pc-label">{{ t('cms_order') }}</span>
                        <input v-model.number="item.display_order" class="pc-input" type="number" min="0" dir="ltr">
                    </label>
                    <label class="pc-field">
                        <span class="pc-label">{{ t('cms_options') }}</span>
                        <select v-model="item.parent_id" class="pc-select">
                            <option value="">{{ t('cms_optional') }}</option>
                            <option v-for="parent in menu.items" :key="parent.id" :value="parent.id">{{ parent.labels?.ar || parent.linkValue }}</option>
                        </select>
                    </label>
                </div>

                <div class="pc-nav-item-row__foot">
                    <label class="pc-checkbox">
                        <input v-model="item.is_enabled" type="checkbox">
                        <span>{{ t('cms_section_enabled') }}</span>
                    </label>
                    <button class="pc-btn pc-btn--danger pc-btn--sm" type="button" @click="removeItem(index)">
                        <UiIcon name="trash" :size="14" />
                        {{ t('cms_remove_item') }}
                    </button>
                </div>
            </div>

            <p v-if="form.errors.items" class="pc-error" role="alert">{{ form.errors.items }}</p>

            <div class="pc-form-actions">
                <button class="pc-btn pc-btn--primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                    <UiIcon name="check" :size="16" />
                    {{ t('cms_save') }}
                </button>
            </div>
        </form>
    </section>
</template>
