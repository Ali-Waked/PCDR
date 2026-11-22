<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import Pagination from '../../../Components/Ui/Pagination.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import Button from '../../../Components/Ui/Button.vue'
import { useLocale } from '../../../Composables/useLocale'
import { usePermission } from '../../../Composables/usePermission'

defineOptions({ layout: AppLayout })

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, required: true },
})

const { t, localePath } = useLocale()
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
    <div class="pcdr-enter">
        <Head :title="t('users_page.title')" />
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <p class="text-sm text-muted">{{ t('users_page.subtitle') }}</p>
            <Link v-if="can('users.create')" :href="localePath('/admin/users/create')" class="focus-ring rounded-md bg-navy-700 px-4 py-2.5 text-sm font-semibold text-white">
                {{ t('users_page.new') }}
            </Link>
        </div>

        <Card>
            <form class="mb-5 flex flex-wrap items-end gap-3" @submit.prevent="applySearch">
                <div class="min-w-64 flex-1">
                    <TextField v-model="search.search" name="search" :label="t('search')" />
                </div>
                <Button type="submit" variant="secondary">{{ t('search') }}</Button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-subtle text-start text-muted">
                            <th class="py-3 text-start font-medium">{{ t('users_page.name') }}</th>
                            <th class="py-3 text-start font-medium">{{ t('users_page.email') }}</th>
                            <th class="py-3 text-start font-medium">{{ t('status') }}</th>
                            <th class="py-3 text-start font-medium">{{ t('users_page.roles') }}</th>
                            <th class="py-3 text-start font-medium">{{ t('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="border-b border-subtle last:border-0">
                            <td class="py-3 font-medium text-ink">
                                {{ user.name }}
                                <Badge v-if="user.is_platform_owner" tone="success" class="ms-2">{{ t('users_page.platform_owner') }}</Badge>
                            </td>
                            <td class="py-3 text-muted" dir="ltr">{{ user.email }}</td>
                            <td class="py-3"><Badge :tone="user.status === 'active' ? 'success' : 'warning'">{{ user.status }}</Badge></td>
                            <td class="py-3 text-muted">{{ user.roles.join(', ') || '—' }}</td>
                            <td class="py-3">
                                <div class="flex flex-wrap gap-2">
                                    <Link :href="localePath(`/admin/users/${user.id}`)" class="focus-ring rounded border border-subtle px-2.5 py-1.5 text-xs">{{ t('view') }}</Link>
                                    <Link v-if="!user.is_platform_owner && can('users.update')" :href="localePath(`/admin/users/${user.id}/edit`)" class="focus-ring rounded border border-subtle px-2.5 py-1.5 text-xs">{{ t('edit') }}</Link>
                                    <button v-if="!user.is_platform_owner && can('users.update')" type="button" class="focus-ring rounded border border-subtle px-2.5 py-1.5 text-xs" @click="toggleStatus(user)">
                                        {{ user.status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td colspan="5" class="py-8 text-center text-muted">{{ t('no_results') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="users" />
        </Card>
    </div>
</template>
