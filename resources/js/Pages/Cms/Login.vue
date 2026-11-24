<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '../../Layouts/AuthLayout.vue'
import { useI18n } from '../../composables/useI18n'

defineOptions({ layout: AuthLayout })

defineProps({
    bootstrapHint: { type: Boolean, default: false },
})

const { t, localPath } = useI18n()

const form = useForm({
    email: '',
    password: '',
})

function submit() {
    form.post(localPath('cms/login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div>
        <Head :title="t('cms_login')" />
        <div class="pc-auth__heading">
            <h1>{{ t('cms_login') }}</h1>
            <p>{{ t('cms_auth_note') }}</p>
        </div>

        <form class="pc-stack" @submit.prevent="submit">
            <div class="pc-field">
                <label class="pc-label" for="cms-login-email">{{ t('ui.email') }}</label>
                <input
                    id="cms-login-email"
                    v-model="form.email"
                    class="pc-input pc-directional"
                    type="email"
                    name="email"
                    autocomplete="username"
                    required
                    :aria-invalid="form.errors.email ? 'true' : 'false'"
                >
                <p v-if="form.errors.email" class="pc-error" role="alert">{{ form.errors.email }}</p>
            </div>
            <div class="pc-field">
                <label class="pc-label" for="cms-login-password">{{ t('cms_password') }}</label>
                <input
                    id="cms-login-password"
                    v-model="form.password"
                    class="pc-input"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    :aria-invalid="form.errors.password ? 'true' : 'false'"
                >
                <p v-if="form.errors.password" class="pc-error" role="alert">{{ form.errors.password }}</p>
            </div>

            <p v-if="bootstrapHint" class="pc-hint">{{ t('cms_login_hint') }}</p>

            <div class="pc-auth__links">
                <Link :href="localPath()" class="pc-link">{{ t('back_home') }}</Link>
                <button class="pc-btn pc-btn--primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                    {{ t('cms_login') }}
                </button>
            </div>
        </form>
    </div>
</template>
