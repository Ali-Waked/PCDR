<script setup>
import { reactive } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Card from '../../../Components/Ui/Card.vue'
import TextField from '../../../Components/Ui/TextField.vue'
import Button from '../../../Components/Ui/Button.vue'
import Badge from '../../../Components/Ui/Badge.vue'
import Alert from '../../../Components/Ui/Alert.vue'
import { useLocale } from '../../../Composables/useLocale'

defineOptions({ layout: AppLayout })

const props = defineProps({
    definitions: { type: Array, required: true },
    values: { type: Object, required: true },
})

const { t, localePath } = useLocale()
const form = useForm({})
const draft = reactive({})

for (const definition of props.definitions) {
    const value = props.values[definition.key]
    draft[definition.key] = definition.type === 'json' ? JSON.stringify(value ?? []) : String(value ?? '')
}

function submit() {
    for (const definition of props.definitions) {
        const raw = draft[definition.key]
        if (definition.type === 'boolean') {
            form[definition.key] = ['1', 'true', 'on'].includes(String(raw).toLowerCase())
        } else if (definition.type === 'integer') {
            form[definition.key] = Number(raw)
        } else if (definition.type === 'json') {
            try {
                form[definition.key] = JSON.parse(raw)
            } catch {
                form[definition.key] = raw
            }
        } else {
            form[definition.key] = raw
        }
    }

    form.put(localePath('/admin/settings'))
}
</script>

<template>
    <Card>
        <Head :title="t('settings_page.title')" />
        <p class="mb-5 text-sm text-muted">{{ t('settings_page.subtitle') }}</p>
        <Alert v-if="Object.keys(form.errors).length" type="error" :message="Object.values(form.errors)[0]" class="mb-5" />
        <form class="grid gap-5 md:grid-cols-2" @submit.prevent="submit">
            <div v-for="definition in definitions" :key="definition.key">
                <TextField v-model="draft[definition.key]" :name="definition.key" :label="definition.label" :error="form.errors[definition.key]" dir="auto" />
                <Badge v-if="definition.public" class="mt-2">{{ t('settings_page.public') }}</Badge>
            </div>
            <div class="md:col-span-2"><Button type="submit" :processing="form.processing">{{ t('save') }}</Button></div>
        </form>
    </Card>
</template>
