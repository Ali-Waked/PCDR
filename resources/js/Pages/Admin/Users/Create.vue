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

defineProps({ availableRoles: { type: Array, default: () => [] } })

const { t, localePath } = useLocale()
const form = useForm({
    name: '',
    email: '',
    password: '',
    status: 'active',
    locale: 'ar',
    roles: [],
})

function toggleRole(id) {
    form.roles = form.roles.includes(id) ? form.roles.filter((item) => item !== id) : [...form.roles, id]
}
</script>

<template>
    <Card>
        <Head :title="t('users_page.new')" />
        <Alert v-if="Object.keys(form.errors).length" type="error" :message="Object.values(form.errors)[0]" class="mb-5" />
        <form class="grid gap-5 md:grid-cols-2" @submit.prevent="form.post(localePath('/admin/users'))">
            <TextField v-model="form.name" name="name" :label="t('users_page.name')" required :error="form.errors.name" />
            <TextField v-model="form.email" name="email" :label="t('users_page.email')" type="email" required :error="form.errors.email" />
            <TextField v-model="form.password" name="password" :label="t('auth.password')" type="password" autocomplete="new-password" required :error="form.errors.password" />
            <SelectField
                v-model="form.locale"
                name="locale"
                :label="t('users_page.locale')"
                :options="[{ value: 'ar', label: 'العربية' }, { value: 'en', label: 'English' }]"
                :error="form.errors.locale"
            />
            <SelectField
                v-model="form.status"
                name="status"
                :label="t('status')"
                :options="[{ value: 'active', label: 'active' }, { value: 'inactive', label: 'inactive' }, { value: 'suspended', label: 'suspended' }]"
                :error="form.errors.status"
            />

            <fieldset class="md:col-span-2">
                <legend class="pcdr-label">{{ t('users_page.roles') }}</legend>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <label v-for="role in availableRoles" :key="role.id" class="flex min-h-11 items-center gap-2 rounded-md border border-subtle px-3 py-2 text-sm">
                        <input type="checkbox" :checked="form.roles.includes(role.id)" @change="toggleRole(role.id)" />
                        {{ role.name }}
                    </label>
                    <p v-if="!availableRoles.length" class="text-sm text-muted">{{ t('no_results') }}</p>
                </div>
            </fieldset>

            <div class="md:col-span-2">
                <Button type="submit" :processing="form.processing">{{ t('create') }}</Button>
            </div>
        </form>
    </Card>
</template>
