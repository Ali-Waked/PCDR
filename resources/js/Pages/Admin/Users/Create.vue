<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import SelectField from '../../../Components/Ui/SelectField.vue'
import Button from '../../../Components/Ui/Button.vue'
import FormSection from '../../../Components/Dashboard/FormSection.vue'
import { useLocale } from '../../../Composables/useLocale'
import { useI18n } from '../../../composables/useI18n'

defineOptions({ layout: AppLayout })

defineProps({ availableRoles: { type: Array, default: () => [] } })

const { t, localePath } = useLocale()
const { t: tWebsite } = useI18n()
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
        <form @submit.prevent="form.post(localePath('/admin/users'))">
            <FormSection :title="tWebsite('ui.cms_basic_data')" :hint="t('users_page.subtitle')">
                <div class="pc-form-grid">
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
                        :options="[{ value: 'active', label: tWebsite('ui.active') }, { value: 'inactive', label: tWebsite('ui.inactive') }, { value: 'suspended', label: tWebsite('ui.suspended') }]"
                        :error="form.errors.status"
                    />
                </div>
            </FormSection>

            <FormSection :title="t('users_page.roles')" :hint="t('roles_page.permissions')">
                <div class="pc-form-grid">
                    <label v-for="role in availableRoles" :key="role.id" class="pc-checkbox">
                        <input type="checkbox" :checked="form.roles.includes(role.id)" @change="toggleRole(role.id)">
                        <span>{{ role.name }}</span>
                    </label>
                    <p v-if="!availableRoles.length" class="pc-hint">{{ t('no_results') }}</p>
                </div>
            </FormSection>

            <div class="pc-form-actions">
                <Button type="submit" :processing="form.processing">{{ t('create') }}</Button>
            </div>
        </form>
    </Card>
</template>
