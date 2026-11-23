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

const props = defineProps({
    target: { type: Object, required: true },
    availableRoles: { type: Array, default: () => [] },
})

const { t, localePath } = useLocale()
const { t: tWebsite } = useI18n()
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
    <div class="pc-grid pc-grid--2">
        <Head :title="t('edit')" />
        <Card>
            <form @submit.prevent="form.put(localePath(`/admin/users/${target.id}`))">
                <FormSection :title="tWebsite('ui.cms_basic_data')" :hint="t('users_page.subtitle')">
                    <div class="pc-stack">
                        <TextField v-model="form.name" name="name" :label="t('users_page.name')" required :error="form.errors.name" />
                        <TextField v-model="form.email" name="email" :label="t('users_page.email')" type="email" required :error="form.errors.email" />
                        <div class="pc-form-grid">
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
                                :options="[{ value: 'active', label: tWebsite('ui.active') }, { value: 'inactive', label: tWebsite('ui.inactive') }, { value: 'suspended', label: tWebsite('ui.suspended') }]"
                            />
                        </div>
                    </div>
                </FormSection>
                <div class="pc-form-actions">
                    <Button type="submit" :processing="form.processing">{{ t('save') }}</Button>
                </div>
            </form>
        </Card>

        <Card>
            <form @submit.prevent="roles.put(localePath(`/admin/users/${target.id}/roles`))">
                <FormSection :title="t('users_page.roles')" :hint="t('roles_page.permissions')">
                    <div class="pc-form-grid">
                        <label v-for="role in availableRoles" :key="role.id" class="pc-checkbox">
                            <input type="checkbox" :checked="roles.roles.includes(role.id)" @change="toggleRole(role.id)">
                            <span>{{ role.name }}</span>
                        </label>
                        <p v-if="!availableRoles.length" class="pc-hint">{{ t('no_results') }}</p>
                    </div>
                </FormSection>
                <div class="pc-form-actions">
                    <Button type="submit" variant="secondary" :processing="roles.processing">{{ t('save') }}</Button>
                </div>
            </form>
        </Card>
    </div>
</template>
