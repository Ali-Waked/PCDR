<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import Button from '../../../Components/Ui/Button.vue'
import Pagination from '../../../Components/Ui/Pagination.vue'
import { useLocale } from '../../../Composables/useLocale'
import { usePermission } from '../../../Composables/usePermission'

defineOptions({ layout: AppLayout })

const props = defineProps({
    permissions: { type: Object, required: true },
    filters: { type: Object, required: true },
})

const { t, localePath } = useLocale()
const { can } = usePermission()
const search = useForm({ search: props.filters.search ?? '' })
</script>

<template>
    <div class="pcdr-enter">
        <Head :title="t('permissions_page.title')" />
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <p class="text-sm text-muted">{{ t('permissions_page.subtitle') }}</p>
            <Link v-if="can('permissions.create')" :href="localePath('/admin/permissions/create')" class="focus-ring rounded-md bg-navy-700 px-4 py-2.5 text-sm font-semibold text-white">
                {{ t('permissions_page.new') }}
            </Link>
        </div>
        <Card>
            <form class="mb-5 flex flex-wrap items-end gap-3" @submit.prevent="search.get(localePath('/admin/permissions'), { preserveState: true })">
                <div class="min-w-64 flex-1"><TextField v-model="search.search" name="search" :label="t('search')" /></div>
                <Button type="submit" variant="secondary">{{ t('search') }}</Button>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] text-sm">
                    <thead><tr class="border-b border-subtle text-muted"><th class="py-3 text-start">{{ t('permissions_page.module') }}</th><th class="py-3 text-start">{{ t('roles_page.key') }}</th><th class="py-3 text-start">{{ t('users_page.name') }}</th><th class="py-3 text-start">{{ t('permissions_page.protected') }}</th><th class="py-3 text-start">{{ t('actions') }}</th></tr></thead>
                    <tbody>
                        <tr v-for="permission in permissions.data" :key="permission.id" class="border-b border-subtle last:border-0">
                            <td class="py-3 text-muted" dir="ltr">{{ permission.module }}</td>
                            <td class="py-3 font-medium text-ink" dir="ltr">{{ permission.key }}</td>
                            <td class="py-3 text-muted">{{ permission.name }}</td>
                            <td class="py-3"><Badge v-if="permission.is_protected" tone="warning">{{ t('permissions_page.protected') }}</Badge><span v-else>—</span></td>
                            <td class="py-3"><Link :href="localePath(`/admin/permissions/${permission.id}/edit`)" class="focus-ring rounded border border-subtle px-2.5 py-1.5 text-xs">{{ t('edit') }}</Link></td>
                        </tr>
                        <tr v-if="!permissions.data.length"><td colspan="5" class="py-8 text-center text-muted">{{ t('no_results') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="permissions" />
        </Card>
    </div>
</template>
