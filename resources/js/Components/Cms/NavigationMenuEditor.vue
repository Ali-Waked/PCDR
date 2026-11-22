<script setup>
import { useForm } from '@inertiajs/vue3'
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
    <section class="cms-card">
        <div class="cms-card__head">
            <h2 style="font-size: 1.05rem; margin: 0">{{ menu.key === 'header' ? t('cms_menu_primary') : t('cms_menu_footer') }}</h2>
            <button class="btn btn-secondary btn-small" type="button" @click="addItem">{{ t('cms_add_item') }}</button>
        </div>

        <form @submit.prevent="save">
            <div v-for="(item, index) in form.items" :key="item.id || `new-${index}`" class="navigation-item">
                <div class="form-grid form-grid--two">
                    <label class="field"><span>{{ t('cms_label_ar') }}</span><input v-model="item.labels.ar" class="input" type="text" maxlength="120" required></label>
                    <label class="field"><span>{{ t('cms_label_en') }}</span><input v-model="item.labels.en" class="input" type="text" maxlength="120" required dir="ltr"></label>
                    <label class="field"><span>{{ t('cms_link_type') }}</span>
                        <select v-model="item.link_type" class="select">
                            <option value="internal">internal</option>
                            <option value="external">external</option>
                        </select>
                    </label>
                    <label class="field"><span>{{ t('cms_link_key') }}</span>
                        <select v-if="item.link_type === 'internal'" v-model="item.link_value" class="select">
                            <option v-for="key in routeKeys" :key="key" :value="key">{{ key }}</option>
                        </select>
                        <input v-else v-model="item.link_value" class="input" type="url" dir="ltr" placeholder="https://">
                    </label>
                    <label class="field"><span>{{ t('cms_order') }}</span><input v-model.number="item.display_order" class="input" type="number" min="0" dir="ltr"></label>
                    <label class="field"><span>{{ t('cms_options') }}</span>
                        <select v-model="item.parent_id" class="select">
                            <option value="">{{ t('cms_optional') }}</option>
                            <option v-for="parent in menu.items" :key="parent.id" :value="parent.id">{{ parent.labels?.ar || parent.linkValue }}</option>
                        </select>
                    </label>
                </div>
                <div class="header-actions" style="margin-block-start: 0.6rem">
                    <label class="checkbox-row"><input v-model="item.is_enabled" type="checkbox"><span>{{ t('cms_section_enabled') }}</span></label>
                    <button class="btn btn-danger btn-small" type="button" @click="removeItem(index)">{{ t('cms_remove_item') }}</button>
                </div>
            </div>

            <p v-if="form.errors.items" class="error" role="alert">{{ form.errors.items }}</p>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">{{ t('cms_save') }}</button>
            </div>
        </form>
    </section>
</template>

<style scoped>
.navigation-item + .navigation-item {
    margin-block-start: 1rem;
    padding-block-start: 1rem;
    border-block-start: 1px solid var(--line);
}
</style>
