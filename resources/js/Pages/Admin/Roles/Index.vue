<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import { useLocale } from '../../../Composables/useLocale'
import { usePermission } from '../../../Composables/usePermission'

defineOptions({ layout: AppLayout })

defineProps({ roles: { type: Array, required: true } })

const { t, localePath } = useLocale()
const { can } = usePermission()
</script>

<template>
    <div class="pcdr-enter">
        <Head :title="t('roles_page.title')" />
        <div class="mb-5 flex justify-end">
            <Link v-if="can('roles.create')" :href="localePath('/admin/roles/create')" class="focus-ring rounded-md bg-navy-700 px-4 py-2.5 text-sm font-semibold text-white">
                {{ t('roles_page.new') }}
            </Link>
        </div>
        <Card>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] text-sm">
                    <thead><tr class="border-b border-subtle text-muted"><th class="py-3 text-start">{{ t('roles_page.key') }}</th><th class="py-3 text-start">{{ t('roles_page.description') }}</th><th class="py-3 text-start">{{ t('roles_page.permissions') }}</th><th class="py-3 text-start">{{ t('roles_page.members') }}</th><th class="py-3 text-start">{{ t('actions') }}</th></tr></thead>
                    <tbody>
                        <tr v-for="role in roles" :key="role.id" class="border-b border-subtle last:border-0">
                            <td class="py-3 font-medium text-ink" dir="ltr">{{ role.key }} <Badge v-if="role.is_system" class="ms-1">system</Badge></td>
                            <td class="py-3 text-muted">{{ role.description || '—' }}</td>
                            <td class="py-3" dir="ltr">{{ role.permissions_count }}</td>
                            <td class="py-3" dir="ltr">{{ role.users_count }}</td>
                            <td class="py-3"><Link :href="localePath(`/admin/roles/${role.id}/edit`)" class="focus-ring rounded border border-subtle px-2.5 py-1.5 text-xs">{{ t('edit') }}</Link></td>
                        </tr>
                        <tr v-if="!roles.length"><td colspan="5" class="py-8 text-center text-muted">{{ t('no_results') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
