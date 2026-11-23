<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import Button from '../../Components/Ui/Button.vue'
import TextField from '../../Components/Ui/TextField.vue'
import CheckboxField from '../../Components/Ui/CheckboxField.vue'
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
        <div class="pc-auth__heading">
            <h1>{{ t('auth.login_title') }}</h1>
            <p>{{ t('auth.login_subtitle') }}</p>
        </div>

        <form class="pc-stack" novalidate @submit.prevent="submit">
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
            <div class="pc-auth__links">
                <Link :href="localePath('/forgot-password')" class="pc-link">{{ t('auth.forgot') }}</Link>
                <Button type="submit" :processing="form.processing">{{ t('auth.submit') }}</Button>
            </div>
        </form>
    </div>
</template>
