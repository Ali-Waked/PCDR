<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import Button from '../../../Components/Ui/Button.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import Alert from '../../../Components/Ui/Alert.vue'
import { useLocale } from '../../../Composables/useLocale'

defineOptions({ layout: AppLayout })

const props = defineProps({
    role: { type: Object, required: true },
    permissionGroups: { type: Array, default: () => [] },
})

const { t, localePath } = useLocale()
const details = useForm({ key: props.role.key, name: props.role.name, description: props.role.description ?? '' })
const permissions = useForm({ permissions: props.role.permissionIds ?? [] })
const protectedCount = computed(() => props.permissionGroups.flatMap((group) => group.items).filter((item) => item.is_protected).length)

function togglePermission(id) {
    permissions.permissions = permissions.permissions.includes(id)
        ? permissions.permissions.filter((item) => item !== id)
        : [...permissions.permissions, id]
}
</script>

<template>
    <div class="grid gap-5 xl:grid-cols-3">
        <Head :title="role.name" />
        <Card class="xl:col-span-1">
            <Alert v-if="Object.keys(details.errors).length" type="error" :message="Object.values(details.errors)[0]" class="mb-5" />
            <form class="space-y-5" @submit.prevent="details.put(localePath(`/admin/roles/${role.id}`))">
                <TextField v-model="details.key" name="key" :label="t('roles_page.key')" required :disabled="role.is_system" :error="details.errors.key" dir="ltr" />
                <TextField v-model="details.name" name="name" :label="t('users_page.name')" required :error="details.errors.name" />
                <TextField v-model="details.description" name="description" :label="t('roles_page.description')" :error="details.errors.description" />
                <Badge v-if="role.is_system">system</Badge>
                <Button type="submit" :processing="details.processing">{{ t('save') }}</Button>
            </form>
        </Card>

        <Card class="xl:col-span-2">
            <h2 class="mb-1 text-lg font-semibold text-navy-900">{{ t('roles_page.permissions') }}</h2>
            <p class="mb-5 text-xs text-muted">{{ protectedCount }} protected permission(s) in catalog</p>
            <Alert v-if="Object.keys(permissions.errors).length" type="error" :message="Object.values(permissions.errors)[0]" class="mb-5" />
            <form class="space-y-6" @submit.prevent="permissions.put(localePath(`/admin/roles/${role.id}/permissions`))">
                <fieldset v-for="group in permissionGroups" :key="group.module">
                    <legend class="pcdr-label" dir="ltr">{{ group.module }}</legend>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label v-for="item in group.items" :key="item.id" class="flex min-h-11 items-start gap-2 rounded-md border border-subtle px-3 py-2 text-xs">
                            <input class="mt-0.5" type="checkbox" :checked="permissions.permissions.includes(item.id)" @change="togglePermission(item.id)" />
                            <span><span dir="ltr">{{ item.key }}</span><Badge v-if="item.is_protected" tone="warning" class="ms-1">{{ t('permissions_page.protected') }}</Badge></span>
                        </label>
                    </div>
                </fieldset>
                <Button type="submit" :processing="permissions.processing">{{ t('save') }}</Button>
            </form>

            <div class="mt-8 border-t border-subtle pt-5">
                <h3 class="text-sm font-semibold text-ink">{{ t('roles_page.members') }}</h3>
                <ul class="mt-2 space-y-1 text-sm text-muted">
                    <li v-for="member in role.users" :key="member.id">{{ member.name }} — {{ member.email }}</li>
                    <li v-if="!role.users.length">{{ t('no_results') }}</li>
                </ul>
            </div>
        </Card>
    </div>
</template>
