<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import SelectField from '../../../Components/Ui/SelectField.vue'
import Button from '../../../Components/Ui/Button.vue'
import Alert from '../../../Components/Ui/Alert.vue'
import { useLocale } from '../../../Composables/useLocale'

defineOptions({ layout: AppLayout })

const props = defineProps({
    target: { type: Object, required: true },
    availableRoles: { type: Array, default: () => [] },
})

const { t, localePath } = useLocale()
const form = useForm({
    name: props.target.name,
    email: props.target.email,
    status: props.target.status,
    locale: props.target.locale,
})
const roles = useForm({ roles: props.target.roleIds ?? [] })

function toggleRole(id) {
    roles.roles = roles.roles.includes(id) ? roles.roles.filter((item) => item !== id) : [...roles.roles, id]
}
</script>

<template>
    <div class="grid gap-5 lg:grid-cols-2">
        <Head :title="t('edit')" />
        <Card>
            <h2 class="mb-5 text-lg font-semibold text-navy-900">{{ t('users_page.title') }}</h2>
            <Alert v-if="Object.keys(form.errors).length" type="error" :message="Object.values(form.errors)[0]" class="mb-5" />
            <form class="space-y-5" @submit.prevent="form.put(localePath(`/admin/users/${target.id}`))">
                <TextField v-model="form.name" name="name" :label="t('users_page.name')" required :error="form.errors.name" />
                <TextField v-model="form.email" name="email" :label="t('users_page.email')" type="email" required :error="form.errors.email" />
                <SelectField
                    v-model="form.locale"
                    name="locale"
                    :label="t('users_page.locale')"
                    :options="[{ value: 'ar', label: 'العربية' }, { value: 'en', label: 'English' }]"
                />
                <SelectField
                    v-model="form.status"
                    name="status"
                    :label="t('status')"
                    :options="[{ value: 'active', label: 'active' }, { value: 'inactive', label: 'inactive' }, { value: 'suspended', label: 'suspended' }]"
                />
                <Button type="submit" :processing="form.processing">{{ t('save') }}</Button>
            </form>
        </Card>

        <Card>
            <h2 class="mb-5 text-lg font-semibold text-navy-900">{{ t('users_page.roles') }}</h2>
            <form class="space-y-4" @submit.prevent="roles.put(localePath(`/admin/users/${target.id}/roles`))">
                <div class="grid gap-2 sm:grid-cols-2">
                    <label v-for="role in availableRoles" :key="role.id" class="flex min-h-11 items-center gap-2 rounded-md border border-subtle px-3 py-2 text-sm">
                        <input type="checkbox" :checked="roles.roles.includes(role.id)" @change="toggleRole(role.id)" />
                        {{ role.name }}
                    </label>
                    <p v-if="!availableRoles.length" class="text-sm text-muted">{{ t('no_results') }}</p>
                </div>
                <Button type="submit" variant="secondary" :processing="roles.processing">{{ t('save') }}</Button>
            </form>
        </Card>
    </div>
</template>
