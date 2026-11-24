<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import Button from '../../Components/Ui/Button.vue'
import { useLocale } from '../../Composables/useLocale'

defineOptions({ layout: AuthLayout })

const { t, localePath } = useLocale()
const form = useForm({})
</script>

<template>
    <div>
        <Head :title="t('auth.verify_title')" />
        <div class="pc-auth__heading">
            <h1>{{ t('auth.verify_title') }}</h1>
            <p>{{ t('auth.verify_subtitle') }}</p>
        </div>

        <form @submit.prevent="form.post(localePath('/email/verification-notification'))">
            <Button type="submit" :processing="form.processing">{{ t('auth.resend') }}</Button>
        </form>
    </div>
</template>
