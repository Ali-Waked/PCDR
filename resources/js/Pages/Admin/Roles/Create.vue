<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import Button from '../../../Components/Ui/Button.vue'
import Alert from '../../../Components/Ui/Alert.vue'
import { useLocale } from '../../../Composables/useLocale'

defineOptions({ layout: AppLayout })

const { t, localePath } = useLocale()
const form = useForm({ key: '', name: '', description: '' })
</script>

<template>
    <Card>
        <Head :title="t('roles_page.new')" />
        <Alert v-if="Object.keys(form.errors).length" type="error" :message="Object.values(form.errors)[0]" class="mb-5" />
        <form class="max-w-2xl space-y-5" @submit.prevent="form.post(localePath('/admin/roles'))">
            <TextField v-model="form.key" name="key" :label="t('roles_page.key')" required :error="form.errors.key" dir="ltr" />
            <TextField v-model="form.name" name="name" :label="t('users_page.name')" required :error="form.errors.name" />
            <TextField v-model="form.description" name="description" :label="t('roles_page.description')" :error="form.errors.description" />
            <Button type="submit" :processing="form.processing">{{ t('create') }}</Button>
        </form>
    </Card>
</template>
