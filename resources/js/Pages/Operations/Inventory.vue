<script setup>
import { computed, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import SelectionControl from '../../Components/Selection/SelectionControl.vue'
import { useI18n } from '../../composables/useI18n'
import { useAdminNotifications } from '../../composables/useAdminNotifications'
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
const { confirmReverse, finishConfirmation } = useAdminNotifications()

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

const warehouseSelectOptions = computed(() => warehouseOptions.value.map((warehouse) => ({
    value: warehouse.id,
    label: warehouse.name || `#${warehouse.id}`,
})))
const destinationSelectOptions = computed(() => warehouseOptions.value
    .filter((warehouse) => String(warehouse.id) !== String(movementForm.warehouse_id))
    .map((warehouse) => ({ value: warehouse.id, label: warehouse.name || `#${warehouse.id}` })))
const itemSelectOptions = computed(() => itemOptions.value.map((item) => ({
    value: item.id,
    label: item.name || item.sku || `#${item.id}`,
})))
const typeSelectOptions = computed(() => types.value.map((type) => ({
    value: type,
    label: humanize(type),
})))
const directionSelectOptions = [
    { value: 'in', label: humanize('in') },
    { value: 'out', label: humanize('out') },
]

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

async function reverseMovement(movement) {
    if (!canReverse.value) {
        return
    }
    if (! (await confirmReverse(`#${movement.id}`))) {
        return
    }

    router.post(
        operationRoute('operations.inventory.movements.reverse', { movement: movement.id }, locale.value),
        { reason: 'Correction reversal' },
        { preserveScroll: true, onFinish: finishConfirmation },
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
    <AppLayout :title="humanize('inventory_ledger')" subtitle="Append-only ledger">
        <div class="pc-stack">

            <p class="pc-callout">
                {{ t('ui.append_only_notice', 'The inventory ledger is append-only; corrections are recorded as new reversal movements.') }}
            </p>

            <form class="pc-card pc-card--padded" @submit.prevent="postMovement">
                <h2 class="mb-4 text-base font-semibold">{{ humanize('stock_movement') }}</h2>
                <div class="pc-form-grid">
                    <div class="pc-field">
                        <SelectionControl
                            v-model="movementForm.warehouse_id"
                            :label="humanize('warehouse_id')"
                            name="warehouse_id"
                            :options="warehouseSelectOptions"
                            :placeholder="t('ui.filter')"
                            :error="errorText(errors.warehouse_id)"
                            searchable
                        />
                    </div>
                    <div v-if="isTransfer" class="pc-field">
                        <SelectionControl
                            v-model="movementForm.destination_warehouse_id"
                            :label="humanize('destination_warehouse_id')"
                            name="destination_warehouse_id"
                            :options="destinationSelectOptions"
                            :placeholder="t('ui.filter')"
                            :error="errorText(errors.destination_warehouse_id)"
                            searchable
                        />
                    </div>
                    <div class="pc-field">
                        <SelectionControl
                            v-model="movementForm.inventory_item_id"
                            :label="humanize('inventory_item_id')"
                            name="inventory_item_id"
                            :options="itemSelectOptions"
                            :placeholder="t('ui.filter')"
                            :error="errorText(errors.inventory_item_id)"
                            searchable
                        />
                    </div>
                    <div class="pc-field">
                        <SelectionControl
                            v-model="movementForm.movement_type"
                            :label="humanize('movement_type')"
                            name="movement_type"
                            :options="typeSelectOptions"
                            :placeholder="t('ui.filter')"
                            :error="errorText(errors.movement_type)"
                            :searchable="typeSelectOptions.length > 6"
                        />
                    </div>
                    <div class="pc-field">
                        <SelectionControl
                            v-model="movementForm.direction"
                            :label="humanize('direction')"
                            name="direction"
                            :options="directionSelectOptions"
                            :searchable="false"
                            :error="errorText(errors.direction)"
                        />
                    </div>
                    <label class="pc-field">
                        <span class="font-medium">{{ humanize('quantity') }}</span>
                        <input v-model="movementForm.quantity" type="number" step="0.001" min="0.001" name="quantity" class="pc-input" />
                        <span v-if="errors.quantity" class="pc-error">{{ errorText(errors.quantity) }}</span>
                    </label>
                    <label class="pc-field">
                        <span class="font-medium">{{ humanize('idempotency_key') }}</span>
                        <input v-model="movementForm.idempotency_key" type="text" name="idempotency_key" class="pc-input" dir="ltr" />
                    </label>
                    <label class="pc-field">
                        <span class="font-medium">{{ humanize('source_reference') }}</span>
                        <input v-model="movementForm.source_reference" type="text" name="source_reference" class="pc-input" dir="ltr" />
                    </label>
                    <label class="pc-field pc-field--full">
                        <span class="font-medium">{{ humanize('reason') }}</span>
                        <textarea v-model="movementForm.reason" name="reason" rows="2" class="pc-textarea"></textarea>
                        <span v-if="errors.reason" class="pc-error">{{ errorText(errors.reason) }}</span>
                    </label>
                </div>
                <button type="submit" class="pc-btn pc-btn--primary" :disabled="!canSubmit || processing">
                    {{ processing ? t('ui.loading', humanize('loading')) : humanize('post_movement') }}
                </button>
                <p v-if="!canSubmit && movementForm.movement_type" class="pc-hint">{{ humanize('no_movement_permission') }}</p>
            </form>

            <p v-if="valuationEnabled" class="pc-callout pc-callout--sand">
                {{ humanize('valuation_enabled_notice') }}
            </p>

            <section class="pc-card pc-card--padded">
                <h2 class="px-5 py-4 text-base font-semibold">{{ humanize('balances') }}</h2>
                <div v-if="!balanceRows.length" class="pc-card__body pc-hint">{{ t('ui.no_results') }}</div>
                <div v-else class="pc-table-wrap">
                    <table class="pc-table">
                        <thead>
                            <tr>
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
                            <tr v-for="balance in balanceRows" :key="balance.id" >
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

            <section class="pc-card pc-card--padded">
                <h2 class="px-5 py-4 text-base font-semibold">{{ humanize('movement_ledger') }}</h2>
                <div v-if="!movementRows.length" class="pc-card__body pc-hint">{{ t('ui.no_results') }}</div>
                <div v-else class="pc-table-wrap">
                    <table class="pc-table">
                        <thead>
                            <tr>
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
                            <tr v-for="movement in movementRows" :key="movement.id" >
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
                                        class="pc-btn pc-btn--secondary pc-btn--sm"
                                        @click="reverseMovement(movement)"
                                    >
                                        {{ humanize('reverse') }}
                                    </button>
                                    <span v-else class="pc-hint">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
