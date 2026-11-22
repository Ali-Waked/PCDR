<script setup>
import { computed, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import CmsLayout from '../../Layouts/CmsLayout.vue'
import { useI18n } from '../../composables/useI18n'
import { formatCount, humanize, operationRoute } from '../../Components/Operations/operations'

const props = defineProps({
    resource: { type: String, default: 'warehouses' },
    warehouses: { type: Array, default: () => [] },
    items: { type: Array, default: () => [] },
    balances: { type: Array, default: () => [] },
    movements: { type: Array, default: () => [] },
    movementTypes: { type: Array, default: () => [] },
    valuationEnabled: { type: Boolean, default: false },
    permissions: { type: Object, default: () => ({}) },
})

const { t, locale, direction, flash, page, formatDate } = useI18n()

const movementForm = reactive({
    warehouse_id: '',
    destination_warehouse_id: '',
    inventory_item_id: '',
    movement_type: '',
    direction: 'in',
    quantity: '',
    idempotency_key: '',
    reason: '',
    source_reference: '',
})

const processing = ref(false)
const errors = ref({})
const isTransfer = computed(() => movementForm.movement_type === 'transfer')

const typePermissions = {
    opening: 'receive', receipt: 'receive', return: 'receive', transfer: 'transfer',
    issue: 'issue', reservation: 'issue', release: 'issue',
}
const canSubmit = computed(() => props.permissions?.[typePermissions[movementForm.movement_type] || 'adjust'] === true)

const authPermissions = computed(() => [
    ...(Array.isArray(page.props.auth?.permissions) ? page.props.auth.permissions : []),
    ...(Array.isArray(page.props.auth?.cmsPermissions) ? page.props.auth.cmsPermissions : []),
])
const canReverse = computed(() => authPermissions.value.includes('inventory.reverse') || props.permissions?.adjust === true)

const warehouseOptions = computed(() => (Array.isArray(props.warehouses) ? props.warehouses : []))
const itemOptions = computed(() => (Array.isArray(props.items) ? props.items : []))
const balanceRows = computed(() => (Array.isArray(props.balances) ? props.balances : []))
const movementRows = computed(() => (Array.isArray(props.movements) ? props.movements : []))
const types = computed(() => (Array.isArray(props.movementTypes) ? props.movementTypes : []))

function resetForm() {
    Object.assign(movementForm, {
        warehouse_id: '', destination_warehouse_id: '', inventory_item_id: '',
        movement_type: '', direction: 'in', quantity: '',
        idempotency_key: '', reason: '', source_reference: '',
    })
}

function postMovement() {
    if (!canSubmit.value || processing.value) {
        return
    }

    const payload = {
        warehouse_id: movementForm.warehouse_id,
        inventory_item_id: movementForm.inventory_item_id,
        movement_type: movementForm.movement_type,
        direction: movementForm.direction,
        quantity: movementForm.quantity,
        idempotency_key: movementForm.idempotency_key,
        reason: movementForm.reason,
        source_reference: movementForm.source_reference,
    }
    if (isTransfer.value) {
        payload.destination_warehouse_id = movementForm.destination_warehouse_id
    }

    router.post(operationRoute('operations.inventory.movements.store', {}, locale.value), payload, {
        preserveScroll: true,
        onStart: () => { processing.value = true; errors.value = {} },
        onFinish: () => { processing.value = false },
        onError: (validationErrors) => { errors.value = validationErrors || {} },
        onSuccess: () => resetForm(),
    })
}

function reverseMovement(movement) {
    if (!canReverse.value) {
        return
    }

    router.post(
        operationRoute('operations.inventory.movements.reverse', { movement: movement.id }, locale.value),
        { reason: 'Correction reversal' },
        { preserveScroll: true },
    )
}

function isReversible(movement) {
    return !movement.reversal_of && movement.movement_type !== 'reversal'
}

function errorText(error) {
    return Array.isArray(error) ? error.join(', ') : String(error ?? '')
}
</script>

<template>
    <CmsLayout :title="humanize('inventory_ledger')" subtitle="Append-only ledger">
        <div :dir="direction" class="space-y-5 text-ink">
            <div
                v-if="flash?.success || flash?.error"
                class="rounded-lg border px-4 py-3 text-sm"
                :class="flash?.error ? 'border-danger/30 bg-danger/5 text-danger' : 'border-success/30 bg-success/5 text-success'"
                role="status"
            >
                {{ flash?.error || flash?.success }}
            </div>

            <p class="rounded-lg border border-teal-700/20 bg-teal-soft px-4 py-3 text-sm text-teal-deep">
                {{ t('ui.append_only_notice', 'The inventory ledger is append-only; corrections are recorded as new reversal movements.') }}
            </p>

            <form class="rounded-xl border border-border-subtle bg-surface p-5" @submit.prevent="postMovement">
                <h2 class="mb-4 text-base font-semibold">{{ humanize('stock_movement') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('warehouse_id') }}</span>
                        <select v-model="movementForm.warehouse_id" name="warehouse_id" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm">
                            <option value="">{{ t('ui.filter') }}</option>
                            <option v-for="warehouse in warehouseOptions" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name || `#${warehouse.id}` }}</option>
                        </select>
                        <span v-if="errors.warehouse_id" class="text-xs text-danger">{{ errorText(errors.warehouse_id) }}</span>
                    </label>
                    <label v-if="isTransfer" class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('destination_warehouse_id') }}</span>
                        <select v-model="movementForm.destination_warehouse_id" name="destination_warehouse_id" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm">
                            <option value="">{{ t('ui.filter') }}</option>
                            <option v-for="warehouse in warehouseOptions.filter((entry) => String(entry.id) !== String(movementForm.warehouse_id))" :key="warehouse.id" :value="warehouse.id">
                                {{ warehouse.name || `#${warehouse.id}` }}
                            </option>
                        </select>
                        <span v-if="errors.destination_warehouse_id" class="text-xs text-danger">{{ errorText(errors.destination_warehouse_id) }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('inventory_item_id') }}</span>
                        <select v-model="movementForm.inventory_item_id" name="inventory_item_id" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm">
                            <option value="">{{ t('ui.filter') }}</option>
                            <option v-for="item in itemOptions" :key="item.id" :value="item.id">{{ item.name || item.sku || `#${item.id}` }}</option>
                        </select>
                        <span v-if="errors.inventory_item_id" class="text-xs text-danger">{{ errorText(errors.inventory_item_id) }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('movement_type') }}</span>
                        <select v-model="movementForm.movement_type" name="movement_type" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm">
                            <option value="">{{ t('ui.filter') }}</option>
                            <option v-for="type in types" :key="type" :value="type">{{ humanize(type) }}</option>
                        </select>
                        <span v-if="errors.movement_type" class="text-xs text-danger">{{ errorText(errors.movement_type) }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('direction') }}</span>
                        <select v-model="movementForm.direction" name="direction" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm">
                            <option value="in">{{ humanize('in') }}</option>
                            <option value="out">{{ humanize('out') }}</option>
                        </select>
                        <span v-if="errors.direction" class="text-xs text-danger">{{ errorText(errors.direction) }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('quantity') }}</span>
                        <input v-model="movementForm.quantity" type="number" step="0.001" min="0.001" name="quantity" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm" />
                        <span v-if="errors.quantity" class="text-xs text-danger">{{ errorText(errors.quantity) }}</span>
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('idempotency_key') }}</span>
                        <input v-model="movementForm.idempotency_key" type="text" name="idempotency_key" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm" dir="ltr" />
                    </label>
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium">{{ humanize('source_reference') }}</span>
                        <input v-model="movementForm.source_reference" type="text" name="source_reference" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm" dir="ltr" />
                    </label>
                    <label class="flex flex-col gap-1 text-sm sm:col-span-2">
                        <span class="font-medium">{{ humanize('reason') }}</span>
                        <textarea v-model="movementForm.reason" name="reason" rows="2" class="rounded-md border border-border-subtle bg-surface px-3 py-2 text-sm"></textarea>
                        <span v-if="errors.reason" class="text-xs text-danger">{{ errorText(errors.reason) }}</span>
                    </label>
                </div>
                <button type="submit" class="focus-ring mt-4 rounded-md bg-navy-700 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60" :disabled="!canSubmit || processing">
                    {{ processing ? t('ui.loading', humanize('loading')) : humanize('post_movement') }}
                </button>
                <p v-if="!canSubmit && movementForm.movement_type" class="mt-2 text-xs text-warning">{{ humanize('no_movement_permission') }}</p>
            </form>

            <p v-if="valuationEnabled" class="rounded-lg bg-navy-100 px-4 py-3 text-sm text-navy-700">
                {{ humanize('valuation_enabled_notice') }}
            </p>

            <section class="overflow-hidden rounded-xl border border-border-subtle bg-surface">
                <h2 class="px-5 py-4 text-base font-semibold">{{ humanize('balances') }}</h2>
                <div v-if="!balanceRows.length" class="px-5 pb-5 text-sm text-muted">{{ t('ui.no_results') }}</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-max border-collapse text-sm">
                        <thead>
                            <tr class="border-y border-border-subtle text-start text-muted">
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('warehouse') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('item') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('sku') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('unit') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('on_hand') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('reserved') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('damaged') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('expired') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="balance in balanceRows" :key="balance.id" class="border-b border-border-subtle last:border-0">
                                <td class="px-3 py-3">{{ balance.warehouse || '—' }}</td>
                                <td class="px-3 py-3">{{ balance.item || '—' }}</td>
                                <td class="px-3 py-3" dir="ltr">{{ balance.sku || '—' }}</td>
                                <td class="px-3 py-3">{{ balance.unit || '—' }}</td>
                                <td class="px-3 py-3">{{ formatCount(balance.quantity, locale) }}</td>
                                <td class="px-3 py-3">{{ formatCount(balance.reserved_quantity, locale) }}</td>
                                <td class="px-3 py-3">{{ formatCount(balance.damaged_quantity, locale) }}</td>
                                <td class="px-3 py-3">{{ formatCount(balance.expired_quantity, locale) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-border-subtle bg-surface">
                <h2 class="px-5 py-4 text-base font-semibold">{{ humanize('movement_ledger') }}</h2>
                <div v-if="!movementRows.length" class="px-5 pb-5 text-sm text-muted">{{ t('ui.no_results') }}</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-max border-collapse text-sm">
                        <thead>
                            <tr class="border-y border-border-subtle text-start text-muted">
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('id') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('warehouse') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('destination') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('item') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('movement_type') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('direction') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('quantity') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('before_after') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('source_reference') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ humanize('created_at') }}</th>
                                <th class="px-3 py-3 text-start font-medium">{{ t('ui.actions', humanize('actions')) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="movement in movementRows" :key="movement.id" class="border-b border-border-subtle last:border-0">
                                <td class="px-3 py-3">#{{ movement.id }}</td>
                                <td class="px-3 py-3">{{ movement.warehouse || '—' }}</td>
                                <td class="px-3 py-3">{{ movement.destination || '—' }}</td>
                                <td class="px-3 py-3">{{ movement.item || '—' }}</td>
                                <td class="px-3 py-3">{{ humanize(movement.movement_type) }}</td>
                                <td class="px-3 py-3">{{ humanize(movement.direction) }}</td>
                                <td class="px-3 py-3">{{ formatCount(movement.quantity, locale) }}</td>
                                <td class="px-3 py-3" dir="ltr">{{ movement.before_quantity }} → {{ movement.after_quantity }}</td>
                                <td class="px-3 py-3" dir="ltr">{{ movement.source_reference || '—' }}</td>
                                <td class="px-3 py-3">{{ formatDate(movement.created_at) }}</td>
                                <td class="px-3 py-3">
                                    <button
                                        v-if="canReverse && isReversible(movement)"
                                        type="button"
                                        class="focus-ring rounded border border-border-subtle px-2.5 py-1.5 text-xs"
                                        @click="reverseMovement(movement)"
                                    >
                                        {{ humanize('reverse') }}
                                    </button>
                                    <span v-else class="text-xs text-muted">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </CmsLayout>
</template>
