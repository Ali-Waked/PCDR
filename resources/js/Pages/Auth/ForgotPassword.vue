<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import Button from '../../Components/Ui/Button.vue'
import TextField from '../../Components/Ui/TextField.vue'
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
        <div class="pc-auth__heading">
            <h1>{{ t('auth.forgot_title') }}</h1>
            <p>{{ t('auth.forgot_subtitle') }}</p>
        </div>

        <form class="pc-stack" novalidate @submit.prevent="submit">
            <TextField v-model="form.email" name="email" :label="t('auth.email')" type="email" autocomplete="email" required :error="form.errors.email" />
            <div class="pc-auth__links">
                <Link :href="localePath('/login')" class="pc-link">{{ t('auth.login_title') }}</Link>
                <Button type="submit" :processing="form.processing">{{ t('auth.send_link') }}</Button>
            </div>
        </form>
    </div>
</template>
