<script setup>
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { useI18n } from '../../../composables/useI18n'

const props = defineProps({
    inquiries: { type: Object, required: true },
})

const { t, formatDate } = useI18n()

const groups = [
    { key: 'contact', label: t('ui.cms_inquiries_contact'), rows: props.inquiries.contact || [] },
    { key: 'volunteer', label: t('ui.cms_inquiries_volunteer'), rows: props.inquiries.volunteer || [] },
    { key: 'partnership', label: t('ui.cms_inquiries_partnership'), rows: props.inquiries.partnership || [] },
]

function subject(row, key) {
    if (key === 'contact') {
        return row.subject || row.category
    }
    if (key === 'volunteer') {
        return row.area_of_interest
    }

    return row.interest
}

function organization(row, key) {
    return key === 'partnership' ? row.organization : row.name
}
</script>

<template>
    <CmsLayout :title="t('ui.cms_inquiries')" :subtitle="t('privacy_notice')">
        <section v-for="group in groups" :key="group.key" class="cms-card">
            <h2 style="font-size: 1.05rem; margin-block-start: 0">{{ group.label }}</h2>

            <div v-if="group.rows.length" class="table-wrap">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>{{ t('cms_reference') }}</th>
                            <th>{{ t('institution') }}</th>
                            <th>{{ t('ui.email') }}</th>
                            <th>{{ t('subject') }}</th>
                            <th>{{ t('cms_status') }}</th>
                            <th>{{ t('cms_created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in group.rows" :key="row.id">
                            <td dir="ltr">{{ row.reference }}</td>
                            <td>{{ organization(row, group.key) }}</td>
                            <td dir="ltr">{{ row.email }}</td>
                            <td>{{ subject(row, group.key) }}</td>
                            <td><span class="pill">{{ t('cms_status_new') }}</span></td>
                            <td class="small-caps">{{ formatDate(row.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else :body="t('cms_no_records')" />
        </section>
    </CmsLayout>
</template>

<style scoped>
.cms-card + .cms-card {
    margin-block-start: 1rem;
}
</style>
