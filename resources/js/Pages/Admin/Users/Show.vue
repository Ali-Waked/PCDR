<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import Button from '../../../Components/Ui/Button.vue'
import Alert from '../../../Components/Ui/Alert.vue'
import { useLocale } from '../../../Composables/useLocale'

defineOptions({ layout: AppLayout })

defineProps({ target: { type: Object, required: true } })
const { t, localePath } = useLocale()
const resetForm = useForm({})
</script>

<template>
    <Card>
        <Head :title="target.name" />
        <Alert v-if="Object.keys(resetForm.errors).length" type="error" :message="Object.values(resetForm.errors)[0]" class="mb-5" />
        <dl class="grid gap-5 sm:grid-cols-2">
            <div>
                <dt class="text-xs text-muted">{{ t('users_page.name') }}</dt>
                <dd class="mt-1 font-medium text-ink">{{ target.name }}</dd>
            </div>
            <div>
                <dt class="text-xs text-muted">{{ t('users_page.email') }}</dt>
                <dd class="mt-1 text-ink" dir="ltr">{{ target.email }}</dd>
            </div>
            <div>
                <dt class="text-xs text-muted">{{ t('status') }}</dt>
                <dd class="mt-1"><Badge :tone="target.status === 'active' ? 'success' : 'warning'">{{ target.status }}</Badge></dd>
            </div>
            <div>
                <dt class="text-xs text-muted">{{ t('users_page.roles') }}</dt>
                <dd class="mt-1 text-ink">{{ target.roles.map((role) => role.name).join(', ') || '—' }}</dd>
            </div>
        </dl>
        <div class="mt-6">
            <Button variant="secondary" :processing="resetForm.processing" @click="resetForm.post(localePath(`/admin/users/${target.id}/reset-password`))">
                {{ t('reset_link_sent') }}
            </Button>
        </div>
    </Card>
</template>
