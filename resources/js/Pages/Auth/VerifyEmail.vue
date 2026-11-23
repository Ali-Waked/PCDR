<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import Button from '../../Components/Ui/Button.vue'
import { useLocale } from '../../Composables/useLocale'

defineOptions({ layout: AuthLayout })

const { t, localePath } = useLocale()
const page = usePage()
const form = useForm({})
</script>

<template>
    <div>
        <Head :title="t('auth.verify_title')" />
        <div class="pc-auth__heading">
            <h1>{{ t('auth.verify_title') }}</h1>
            <p>{{ t('auth.verify_subtitle') }}</p>
        </div>

        <div v-if="page.props.flash.success" class="pc-callout" role="status">
            <span>{{ page.props.flash.success }}</span>
        </div>

        <form @submit.prevent="form.post(localePath('/email/verification-notification'))">
            <Button type="submit" :processing="form.processing">{{ t('auth.resend') }}</Button>
        </form>
    </div>
</template>
