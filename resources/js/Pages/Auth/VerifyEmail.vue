<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import Button from '../../Components/Ui/Button.vue'
import Alert from '../../Components/Ui/Alert.vue'
import { useLocale } from '../../Composables/useLocale'

defineOptions({ layout: AuthLayout })

const { t, localePath } = useLocale()
const form = useForm({})
</script>

<template>
    <div>
        <Head :title="t('auth.verify_title')" />
        <h2 class="text-2xl font-semibold text-navy-900">{{ t('auth.verify_title') }}</h2>
        <p class="mt-2 text-sm text-muted">{{ t('auth.verify_subtitle') }}</p>
        <Alert v-if="$page.props.flash.success" type="success" :message="$page.props.flash.success" class="mt-5" />
        <form class="mt-6" @submit.prevent="form.post(localePath('/email/verification-notification'))">
            <Button type="submit" :processing="form.processing">{{ t('auth.resend') }}</Button>
        </form>
    </div>
</template>
