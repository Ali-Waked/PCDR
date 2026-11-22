<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { useI18n } from '../../composables/useI18n'

defineProps({
    bootstrapHint: { type: Boolean, default: false },
})

const { t, locale, alternateUrl, localPath } = useI18n()
const page = usePage()

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
    <div class="cms-login-shell">
        <main class="cms-login-card" id="main-content">
            <div class="header-actions" style="justify-content: space-between">
                <Link :href="localPath()" class="btn btn-ghost btn-small">{{ t('back_home') }}</Link>
                <div class="lang-switch" :aria-label="t('ui.switch_language')">
                    <a
                        v-for="code in ['ar', 'en']"
                        :key="code"
                        :href="alternateUrl(code)"
                        :class="{ 'is-active': code === locale }"
                    >
                        {{ code === 'ar' ? 'ع' : 'EN' }}
                    </a>
                </div>
            </div>

            <p class="eyebrow">{{ t('cms_welcome') }}</p>
            <h1 class="display-2">{{ t('cms_login') }}</h1>
            <p class="muted">{{ t('cms_auth_note') }}</p>

            <div v-if="page.props.flash?.error" class="alert alert--error" role="alert">
                {{ page.props.flash.error }}
            </div>

            <form @submit.prevent="submit">
                <div class="form-grid">
                    <label class="field">
                        <span>{{ t('ui.email') }}</span>
                        <input
                            v-model="form.email"
                            class="input"
                            type="email"
                            name="email"
                            autocomplete="username"
                            dir="ltr"
                            required
                            :aria-invalid="!! form.errors.email"
                        >
                        <span v-if="form.errors.email" class="error" role="alert">{{ form.errors.email }}</span>
                    </label>
                    <label class="field">
                        <span>{{ t('cms_password') }}</span>
                        <input
                            v-model="form.password"
                            class="input"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            :aria-invalid="!! form.errors.password"
                        >
                        <span v-if="form.errors.password" class="error" role="alert">{{ form.errors.password }}</span>
                    </label>
                </div>

                <p v-if="bootstrapHint" class="hint">{{ t('cms_login_hint') }}</p>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                        {{ t('cms_login') }}
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>

<style scoped>
.cms-login-shell {
    min-block-size: 100vh;
    display: grid;
    place-items: center;
    padding: 2rem 1rem;
    background: var(--brand-navy-soft);
}

.cms-login-card {
    inline-size: min(100%, 480px);
    padding: clamp(1.5rem, 4vw, 2.5rem);
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-card);
    box-shadow: 0 18px 45px rgb(43 58 84 / 10%);
}

.cms-login-card h1 {
    margin-block: 0.5rem;
}

.cms-login-card .form-grid {
    margin-block: 1.5rem 0;
}
</style>
