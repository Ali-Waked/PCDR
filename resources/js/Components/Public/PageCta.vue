<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    title: { type: String, required: true },
    lead: { type: String, default: '' },
    lede: { type: String, default: '' },
    primary: { type: Object, default: null },
    secondary: { type: Object, default: null },
    actions: { type: Array, default: () => [] },
    eyebrow: { type: String, default: '' },
})

const resolvedLead = computed(() => props.lead || props.lede || '')
const resolvedPrimary = computed(() => {
    if (props.primary?.label && props.primary?.href) return props.primary
    const a = props.actions.find((x) => x.primary) || props.actions[0]
    return a ? { label: a.label, href: a.url || a.href } : null
})
const resolvedSecondary = computed(() => {
    if (props.secondary?.label && props.secondary?.href) return props.secondary
    const a = props.actions.find((x) => !x.primary && x !== (props.actions.find((p) => p.primary) || props.actions[0]))
    return a ? { label: a.label, href: a.url || a.href } : null
})
</script>

<template>
    <section class="ps-cta" :aria-label="title">
        <div class="ps-container">
            <p v-if="eyebrow" class="ps-eyebrow">{{ eyebrow }}</p>
            <h2>{{ title }}</h2>
            <p v-if="resolvedLead" class="ps-lede">{{ resolvedLead }}</p>
            <div v-if="resolvedPrimary || resolvedSecondary" class="ps-actions">
                <Link v-if="resolvedPrimary" :href="resolvedPrimary.href" class="ps-btn ps-cta__btn ps-cta__btn--primary">
                    {{ resolvedPrimary.label }}
                </Link>
                <Link v-if="resolvedSecondary" :href="resolvedSecondary.href" class="ps-btn ps-cta__btn ps-cta__btn--secondary">
                    {{ resolvedSecondary.label }}
                </Link>
            </div>
        </div>
    </section>
</template>
