<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import Button from '../../Components/Ui/Button.vue'
import TextField from '../../Components/Ui/TextField.vue'
import CheckboxField from '../../Components/Ui/CheckboxField.vue'
import Alert from '../../Components/Ui/Alert.vue'
import { useLocale } from '../../Composables/useLocale'

defineOptions({ layout: AuthLayout })

const { t, localePath } = useLocale()
const form = useForm({ email: '', password: '', remember: false })

function submit() {
    form.post(localePath('/login'), { preserveScroll: true })
}
</script>

<template>
    <div>
        <Head :title="t('auth.login_title')" />
        <h2 class="text-2xl font-semibold text-navy-900">{{ t('auth.login_title') }}</h2>
        <p class="mt-2 text-sm text-muted">{{ t('auth.login_subtitle') }}</p>

        <Alert v-if="form.errors.email" type="error" :message="form.errors.email" class="mt-5" />

        <form class="mt-6 space-y-5" novalidate @submit.prevent="submit">
            <TextField
                v-model="form.email"
                name="email"
                :label="t('auth.email')"
                type="email"
                autocomplete="username"
                inputmode="email"
                required
                :error="form.errors.email"
            />
            <TextField
                v-model="form.password"
                name="password"
                :label="t('auth.password')"
                type="password"
                autocomplete="current-password"
                required
                :error="form.errors.password"
            />
            <CheckboxField v-model="form.remember" name="remember" :label="t('auth.remember')" />
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link :href="localePath('/forgot-password')" class="focus-ring rounded text-sm font-medium text-teal-700 hover:underline">
                    {{ t('auth.forgot') }}
                </Link>
                <Button type="submit" :processing="form.processing">{{ t('auth.submit') }}</Button>
            </div>
        </form>
    </div>
</template>
