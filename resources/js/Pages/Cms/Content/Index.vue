<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import StatusPill from '../../../Components/StatusPill.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import Pagination from '../../../Components/Pagination.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    resource: { type: String, required: true },
    label: { type: String, required: true },
    routeBase: { type: String, required: true },
    items: { type: Array, default: () => [] },
    pagination: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
})

const { t, localPath, formatDate } = useI18n()

const segments = {
    'cms.pages': 'pages',
    'cms.pillars': 'pillars',
    'cms.programs': 'programs',
    'cms.projects': 'projects',
    'cms.news': 'news',
    'cms.publications': 'publications',
}

const basePath = computed(() => localPath(`cms/${segments[props.routeBase] || props.resource}`))
const confirmId = ref(null)

const statuses = ['draft', 'pending_review', 'approved', 'published', 'archived']

function changeStatus(id, status) {
    router.post(`${basePath.value}/${id}/status`, { status }, { preserveScroll: true })
}

function remove(id) {
    router.delete(`${basePath.value}/${id}`, { preserveScroll: true, onFinish: () => { confirmId.value = null } })
}
</script>

<template>
    <CmsLayout :title="label" :subtitle="t('cms_publish_status')">
        <div class="cms-metric-grid" style="margin-block-end: 1rem">
            <div class="cms-metric">
                <strong>{{ counts.all || 0 }}</strong>
                <span>{{ t('ui.all') }}</span>
            </div>
            <div class="cms-metric">
                <strong>{{ counts.published || 0 }}</strong>
                <span>{{ t('ui.published') }}</span>
            </div>
            <div class="cms-metric">
                <strong>{{ counts.draft || 0 }}</strong>
                <span>{{ t('ui.draft') }}</span>
            </div>
        </div>

        <div class="cms-card">
            <div class="cms-card__head">
                <h2 style="font-size: 1.05rem; margin: 0">{{ label }}</h2>
                <Link :href="`${basePath}/create`" class="btn btn-primary btn-small">{{ t('cms_new_item') }}</Link>
            </div>

            <div v-if="items.length" class="table-wrap">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>{{ t('cms_title') }}</th>
                            <th>{{ t('cms_status') }}</th>
                            <th>{{ t('available_options') }}</th>
                            <th>{{ t('updated_on') }}</th>
                            <th>{{ t('cms_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td>
                                <Link :href="`${basePath}/${item.id}/edit`" style="font-weight: 650">{{ item.title || `#${item.id}` }}</Link>
                            </td>
                            <td><StatusPill :status="item.status" /></td>
                            <td>
                                <span v-for="code in item.locales" :key="code" class="pill" style="margin-inline-end: 0.3rem">{{ code.toUpperCase() }}</span>
                            </td>
                            <td class="small-caps">{{ formatDate(item.updatedAt) }}</td>
                            <td>
                                <div class="header-actions">
                                    <Link :href="`${basePath}/${item.id}/edit`" class="btn btn-secondary btn-small">{{ t('cms_edit') }}</Link>
                                    <select
                                        class="select"
                                        style="inline-size: auto; min-block-size: 36px; font-size: 0.8rem"
                                        :value="item.status"
                                        :aria-label="`${t('cms_status')}: ${item.title}`"
                                        @change="changeStatus(item.id, $event.target.value)"
                                    >
                                        <option v-for="status in statuses" :key="status" :value="status">{{ t(status) }}</option>
                                    </select>
                                    <button class="btn btn-danger btn-small" type="button" @click="confirmId = item.id">{{ t('cms_delete') }}</button>
                                </div>
                                <div v-if="confirmId === item.id" class="alert alert--error" style="margin-block-start: 0.6rem" role="alert">
                                    <span>{{ t('cms_confirm_delete') }}</span>
                                    <button class="btn btn-danger btn-small" type="button" @click="remove(item.id)">{{ t('cms_delete') }}</button>
                                    <button class="btn btn-ghost btn-small" type="button" @click="confirmId = null">×</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :body="t('cms_no_records')">
                <Link :href="`${basePath}/create`" class="btn btn-primary" style="margin-block-start: 1rem">{{ t('cms_new_item') }}</Link>
            </EmptyState>

            <Pagination :pagination="pagination" />
        </div>
    </CmsLayout>
</template>
