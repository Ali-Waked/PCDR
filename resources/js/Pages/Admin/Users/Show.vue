<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import Button from '../../../Components/Ui/Button.vue'
import { useLocale } from '../../../Composables/useLocale'

defineOptions({ layout: AppLayout })

defineProps({ target: { type: Object, required: true } })
const { t, localePath } = useLocale()
const resetForm = useForm({})
</script>

<template>
    <Card>
        <Head :title="target.name" />
        <dl class="pc-detail-list">
            <div class="pc-detail-list__item">
                <dt>{{ t('users_page.name') }}</dt>
                <dd>{{ target.name }}</dd>
            </div>
            <div class="pc-detail-list__item">
                <dt>{{ t('users_page.email') }}</dt>
                <dd class="pc-directional">{{ target.email }}</dd>
            </div>
            <div class="pc-detail-list__item">
                <dt>{{ t('status') }}</dt>
                <dd><Badge :tone="target.status === 'active' ? 'success' : 'warning'">{{ target.status }}</Badge></dd>
            </div>
            <div class="pc-detail-list__item">
                <dt>{{ t('users_page.roles') }}</dt>
                <dd>{{ target.roles.map((role) => role.name).join(', ') || '—' }}</dd>
            </div>
        </dl>
        <div class="pc-form-actions">
            <Button variant="secondary" :processing="resetForm.processing" @click="resetForm.post(localePath(`/admin/users/${target.id}/reset-password`))">
                {{ t('reset_link_sent') }}
            </Button>
        </div>
    </Card>
</template>
