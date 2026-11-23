<script setup>
import { computed } from 'vue'
import { useLocale } from '../Composables/useLocale'

const props = defineProps({
    compact: { type: Boolean, default: false },
    inverse: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
})

const { t } = useLocale()

// The approved static PNG is referenced in place; it is never copied,
// regenerated, or replaced by the animated marketing variant.
const logoSource = '/assets/pcdr-homepage/logo/pcdr-logo.png'

const sizeClass = computed(() => `pcdr-brand--${props.size}`)
</script>

<template>
    <span class="pcdr-brand" :class="[sizeClass, { 'pcdr-brand--inverse': inverse }]">
        <img
            class="pcdr-brand__mark"
            :src="logoSource"
            :alt="compact ? t('app_name') : ''"
            :aria-hidden="compact ? undefined : 'true'"
            width="48"
            height="48"
        >
        <span v-if="!compact" class="pcdr-brand__wordmark">
            <strong>{{ t('app_name') }}</strong>
            <small>{{ t('admin_brand.tagline') }}</small>
        </span>
    </span>
</template>
