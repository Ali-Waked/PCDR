<script setup>
import { router } from '@inertiajs/vue3'
import { useLocale } from '../Composables/useLocale'

const { locale, pathForLocale } = useLocale()

function switchTo(item) {
    if (item === locale.value) return
    router.visit(pathForLocale(item), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    })
}
</script>

<template>
    <div class="flex items-center gap-2 text-sm" role="group" aria-label="Language">
        <button
            v-for="item in ['ar', 'en']"
            :key="item"
            type="button"
            class="focus-ring rounded-md border px-2.5 py-1"
            :class="locale === item ? 'border-navy-700 bg-navy-700 text-white' : 'border-subtle text-ink hover:bg-surface-alt'"
            :aria-current="locale === item ? 'true' : undefined"
            :aria-pressed="locale === item ? 'true' : 'false'"
            @click="switchTo(item)"
        >
            {{ item === 'ar' ? 'العربية' : 'English' }}
        </button>
    </div>
</template>
