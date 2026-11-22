<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import Button from '../../Components/Ui/Button.vue'
import TextField from '../../Components/Ui/TextField.vue'
import Alert from '../../Components/Ui/Alert.vue'
import { useLocale } from '../../Composables/useLocale'

defineOptions({ layout: AuthLayout })

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, required: true },
})

const { t, localePath } = useLocale()
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

function submit() {
    form.post(localePath('/reset-password'))
}
</script>

<template>
    <div>
        <Head :title="t('auth.reset_title')" />
        <h2 class="text-2xl font-semibold text-navy-900">{{ t('auth.reset_title') }}</h2>
        <p class="mt-2 text-sm text-muted">{{ t('auth.reset_subtitle') }}</p>

        <Alert v-if="form.errors.email" type="error" :message="form.errors.email" class="mt-5" />
        <form class="mt-6 space-y-5" novalidate @submit.prevent="submit">
            <TextField v-model="form.email" name="email" :label="t('auth.email')" type="email" required :error="form.errors.email" />
            <TextField v-model="form.password" name="password" :label="t('auth.password')" type="password" autocomplete="new-password" required :error="form.errors.password" />
            <TextField v-model="form.password_confirmation" name="password_confirmation" :label="t('auth.confirm_password')" type="password" autocomplete="new-password" required />
            <Button type="submit" :processing="form.processing">{{ t('auth.reset_title') }}</Button>
        </form>
    </div>
</template>
