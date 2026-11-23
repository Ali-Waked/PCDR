<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import Pagination from '../../../Components/Ui/Pagination.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import Button from '../../../Components/Ui/Button.vue'
import EmptyState from '../../../Components/Dashboard/EmptyState.vue'
import { useLocale } from '../../../Composables/useLocale'
import { useI18n } from '../../../composables/useI18n'
import { usePermission } from '../../../Composables/usePermission'

defineOptions({ layout: AppLayout })

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, required: true },
})

const { t, localePath } = useLocale()
const { t: tWebsite } = useI18n()
const { can } = usePermission()
const search = useForm({ search: props.filters.search ?? '' })

function applySearch() {
    search.get(localePath('/admin/users'), { preserveState: true, preserveScroll: true })
}

function toggleStatus(user) {
    router.patch(localePath(`/admin/users/${user.id}/status`), {
        status: user.status === 'active' ? 'inactive' : 'active',
    }, { preserveScroll: true })
}
</script>

<template>
    <div class="pc-stack pcdr-enter">
        <Head :title="t('users_page.title')" />

        <div class="pc-page-actions">
            <Link v-if="can('users.create')" :href="localePath('/admin/users/create')" class="pc-btn pc-btn--primary">
                {{ t('users_page.new') }}
            </Link>
        </div>

        <Card>
            <form class="pc-form-grid" @submit.prevent="applySearch">
                <TextField v-model="search.search" name="search" :label="t('search')" />
                <div class="pc-field" style="align-self: end">
                    <Button type="submit" variant="secondary">{{ t('search') }}</Button>
                </div>
            </form>
        </Card>

        <Card>
            <div class="pc-table-wrap">
                <table class="pc-table">
                    <thead>
                        <tr>
                            <th>{{ t('users_page.name') }}</th>
                            <th>{{ t('users_page.email') }}</th>
                            <th>{{ t('status') }}</th>
                            <th>{{ t('users_page.roles') }}</th>
                            <th>{{ t('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="pc-table__primary">
                                {{ user.name }}
                                <Badge v-if="user.is_platform_owner" tone="success" class="ms-2">{{ t('users_page.platform_owner') }}</Badge>
                            </td>
                            <td class="pc-directional">{{ user.email }}</td>
                            <td><Badge :tone="user.status === 'active' ? 'success' : 'warning'">{{ user.status }}</Badge></td>
                            <td>{{ user.roles.join(', ') || '—' }}</td>
                            <td>
                                <div class="pc-table__actions">
                                    <Link :href="localePath(`/admin/users/${user.id}`)" class="pc-btn pc-btn--secondary pc-btn--sm">{{ t('view') }}</Link>
                                    <Link v-if="!user.is_platform_owner && can('users.update')" :href="localePath(`/admin/users/${user.id}/edit`)" class="pc-btn pc-btn--secondary pc-btn--sm">{{ t('edit') }}</Link>
                                    <button v-if="!user.is_platform_owner && can('users.update')" type="button" class="pc-btn pc-btn--ghost pc-btn--sm" @click="toggleStatus(user)">
                                        {{ user.status === 'active' ? tWebsite('ui.inactive') : tWebsite('ui.active') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td colspan="5"><EmptyState :title="t('no_results')" icon="users" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="users" />
        </Card>
    </div>
</template>
