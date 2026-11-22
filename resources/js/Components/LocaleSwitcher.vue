<script setup>
import { Link } from '@inertiajs/vue3'
import { useLocale } from '../Composables/useLocale'

const { locale, pathForLocale } = useLocale()
</script>

<template>
    <div class="flex items-center gap-2 text-sm" role="group" aria-label="Language">
        <Link
            v-for="item in ['ar', 'en']"
            :key="item"
            :href="pathForLocale(item)"
            :only="['locale', 'direction', 'translations', 'settings', 'auth']"
            class="focus-ring rounded-md border px-2.5 py-1"
            :class="locale === item ? 'border-navy-700 bg-navy-700 text-white' : 'border-subtle text-ink hover:bg-surface-alt'"
            :aria-current="locale === item ? 'true' : undefined"
        >
            {{ item === 'ar' ? 'العربية' : 'English' }}
        </Link>
    </div>
</template>
