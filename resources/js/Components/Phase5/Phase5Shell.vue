<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import { useI18n } from '../../composables/useI18n'
import { label } from './phase5'

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    notice: { type: String, default: '' },
    back: { type: String, default: '' },
    backLabel: { type: String, default: '' },
})

const { t, locale } = useI18n()

const backText = () => props.backLabel || label('back', locale.value) || t('phase5.back')
</script>

<template>
    <AppLayout :title="title" :subtitle="subtitle" :eyebrow="eyebrow">
        <template #actions>
            <Link v-if="back" :href="back" class="pc-btn pc-btn--ghost pc-btn--sm">
                <span aria-hidden="true">←</span> {{ backText() }}
            </Link>
            <slot name="actions" />
        </template>

        <div class="pc-stack">
            <div v-if="notice" class="pc-callout pc-callout--sand" role="note">{{ notice }}</div>
            <slot />
        </div>
    </AppLayout>
</template>
