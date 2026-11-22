<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import Button from '../../../Components/Ui/Button.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import Alert from '../../../Components/Ui/Alert.vue'
import { useLocale } from '../../../Composables/useLocale'

defineOptions({ layout: AppLayout })

const props = defineProps({ permission: { type: Object, required: true } })
const { t, localePath } = useLocale()
const form = useForm({
    key: props.permission.key,
    name: props.permission.name,
    module: props.permission.module,
    description: props.permission.description ?? '',
})
</script>

<template>
    <Card>
        <Head :title="permission.name" />
        <Alert v-if="Object.keys(form.errors).length" type="error" :message="Object.values(form.errors)[0]" class="mb-5" />
        <Badge v-if="permission.is_protected" tone="warning" class="mb-4">{{ t('permissions_page.protected') }}</Badge>
        <form class="max-w-2xl space-y-5" @submit.prevent="form.put(localePath(`/admin/permissions/${permission.id}`))">
            <TextField v-model="form.key" name="key" :label="t('roles_page.key')" required :disabled="permission.is_protected" :error="form.errors.key" dir="ltr" />
            <TextField v-model="form.name" name="name" :label="t('users_page.name')" required :error="form.errors.name" />
            <TextField v-model="form.module" name="module" :label="t('permissions_page.module')" required :error="form.errors.module" dir="ltr" />
            <TextField v-model="form.description" name="description" :label="t('roles_page.description')" :error="form.errors.description" />
            <Button type="submit" :processing="form.processing">{{ t('save') }}</Button>
        </form>
    </Card>
</template>
