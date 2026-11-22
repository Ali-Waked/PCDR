<script setup>
import { Link } from '@inertiajs/vue3'
import CmsLayout from '../../Layouts/CmsLayout.vue'
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
    <CmsLayout :title="title" :subtitle="subtitle">
        <div class="phase5-page">
            <header class="phase5-page__head">
                <div>
                    <span v-if="eyebrow" class="small-caps phase5-page__eyebrow">{{ eyebrow }}</span>
                    <h1>{{ title }}</h1>
                    <p v-if="subtitle" class="phase5-page__subtitle">{{ subtitle }}</p>
                </div>
                <div class="phase5-page__actions">
                    <Link v-if="back" :href="back" class="btn btn-ghost btn-small">
                        <span aria-hidden="true">←</span> {{ backText() }}
                    </Link>
                    <slot name="actions" />
                </div>
            </header>

            <div v-if="notice" class="notice phase5-notice" role="note">{{ notice }}</div>

            <slot />
        </div>
    </CmsLayout>
</template>
