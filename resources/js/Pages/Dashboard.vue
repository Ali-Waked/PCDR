<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import Card from '../Components/Ui/Card.vue'
import { useLocale } from '../Composables/useLocale'

defineOptions({ layout: AppLayout })

defineProps({ statistics: { type: Object, required: true } })

const { t } = useLocale()
const cards = [
    { key: 'users', label: t('dashboard.users'), value: 'users' },
    { key: 'roles', label: t('dashboard.roles'), value: 'roles' },
    { key: 'permissions', label: t('dashboard.permissions'), value: 'permissions' },
    { key: 'events', label: t('dashboard.events'), value: 'recent_events' },
]
</script>

<template>
    <div class="pcdr-enter">
        <Head :title="t('dashboard.title')" />
        <p class="mb-6 text-sm text-muted">{{ t('dashboard.subtitle') }}</p>
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <Card v-for="card in cards" :key="card.key">
                <p class="text-sm text-muted">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold text-navy-900">{{ statistics[card.value] }}</p>
            </Card>
        </div>
    </div>
</template>
