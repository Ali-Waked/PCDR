<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from '../../composables/useI18n'
import { label } from './phase5'

defineProps({
    paginator: { type: Object, default: null },
})

const { locale } = useI18n()
</script>

<template>
    <div v-if="paginator" class="phase5-pagination">
        <p class="phase5-table-meta">
            <template v-if="paginator.total !== undefined">
                {{ label('total', locale) }}: {{ paginator.total }}
            </template>
            <template v-if="paginator.from && paginator.to">
                &nbsp;·&nbsp; {{ paginator.from }}–{{ paginator.to }}
            </template>
        </p>
        <nav v-if="(paginator.links ?? []).length > 1" :aria-label="label('pagination', locale)" class="phase5-pagination__links">
            <template v-for="(link, index) in paginator.links" :key="`${link.label}-${index}`">
                <span v-if="!link.url" class="btn btn-small btn-ghost is-disabled" aria-disabled="true">
                    <span v-html="link.label" />
                </span>
                <Link
                    v-else
                    :href="link.url"
                    class="btn btn-small"
                    :class="link.active ? 'btn-primary' : 'btn-secondary'"
                    :aria-current="link.active ? 'page' : undefined"
                >
                    <span v-html="link.label" />
                </Link>
            </template>
        </nav>
    </div>
</template>
