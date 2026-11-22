<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import LocaleSwitcher from '../Components/LocaleSwitcher.vue'
import Alert from '../Components/Ui/Alert.vue'
import { useLocale } from '../Composables/useLocale'

const { t } = useLocale()
const page = usePage()
const flash = computed(() => page.props.flash ?? {})
</script>

<template>
    <div class="min-h-screen bg-surface-alt">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded focus:bg-navy-700 focus:px-4 focus:py-2 focus:text-white">
            {{ t('skip_to_content') }}
        </a>
        <header class="mx-auto flex max-w-6xl items-center justify-between px-5 py-6">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-md bg-navy-700 text-lg font-bold text-white">PCDR</span>
                <div>
                    <p class="font-semibold text-ink">{{ t('app_name') }}</p>
                    <p class="text-xs text-muted">Gaza, Palestine</p>
                </div>
            </div>
            <LocaleSwitcher />
        </header>
        <main id="main" class="mx-auto flex max-w-6xl flex-col items-center gap-8 px-5 pb-16 pt-6 lg:flex-row lg:items-start">
            <div class="w-full pcdr-enter lg:w-1/2">
                <div class="border-s-4 border-teal bg-surface p-8 shadow-sm">
                    <p class="text-sm font-semibold text-teal-700">{{ t('welcome.badge') }}</p>
                    <h1 class="mt-3 text-3xl font-semibold text-navy-900">من الاستجابة الإنسانية إلى التعافي والتنمية المستدامة</h1>
                    <p class="mt-4 text-sm leading-relaxed text-muted">{{ t('welcome.note') }}</p>
                </div>
            </div>
            <div class="w-full lg:w-1/2">
                <Alert v-if="flash.success" type="success" :message="flash.success" />
                <div class="pcdr-card p-7">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
