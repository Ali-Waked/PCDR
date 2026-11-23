<script setup>
import UiIcon from './UiIcon.vue'

defineProps({
    search: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    summary: { type: String, default: '' },
})

defineEmits(['update:search'])
</script>

<template>
    <div class="pc-toolbar">
        <label class="pc-search">
            <span class="sr-only">{{ placeholder }}</span>
            <UiIcon name="search" :size="16" />
            <input
                type="search"
                :value="search"
                :placeholder="placeholder"
                @input="$emit('update:search', $event.target.value)"
            >
        </label>
        <div class="pc-toolbar__spacer" />
        <slot />
        <p v-if="summary" class="pc-filter-summary">{{ summary }}</p>
    </div>
</template>
