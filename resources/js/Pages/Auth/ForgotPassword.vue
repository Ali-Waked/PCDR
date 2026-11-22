<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import Button from '../../Components/Ui/Button.vue'
import TextField from '../../Components/Ui/TextField.vue'
import Alert from '../../Components/Ui/Alert.vue'
import { useLocale } from '../../Composables/useLocale'

defineOptions({ layout: AuthLayout })

const { t, localePath } = useLocale()
const form = useForm({ email: '' })

function submit() {
    form.post(localePath('/forgot-password'))
}
</script>

<template>
    <div>
        <Head :title="t('auth.forgot_title')" />
        <h2 class="text-2xl font-semibold text-navy-900">{{ t('auth.forgot_title') }}</h2>
        <p class="mt-2 text-sm text-muted">{{ t('auth.forgot_subtitle') }}</p>

        <Alert v-if="form.errors.email" type="error" :message="form.errors.email" class="mt-5" />
        <form class="mt-6 space-y-5" novalidate @submit.prevent="submit">
            <TextField v-model="form.email" name="email" :label="t('auth.email')" type="email" autocomplete="email" required :error="form.errors.email" />
            <div class="flex items-center justify-between gap-3">
                <Link :href="localePath('/login')" class="focus-ring rounded text-sm text-teal-700 hover:underline">{{ t('auth.submit') }}</Link>
                <Button type="submit" :processing="form.processing">{{ t('auth.send_link') }}</Button>
            </div>
        </form>
    </div>
</template>
