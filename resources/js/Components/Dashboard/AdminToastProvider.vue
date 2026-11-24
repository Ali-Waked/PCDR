<script setup>
import { onMounted, watch } from 'vue'
import { useI18n } from '../../composables/useI18n'
import { useAdminNotifications } from '../../composables/useAdminNotifications'
import AdminToast from './AdminToast.vue'
import AdminConfirmDialog from './AdminConfirmDialog.vue'

const { t, page, locale } = useI18n()
const { notifications, labels, configureTranslations, handleInitialFlash, installEvents, clear } = useAdminNotifications()

function syncLabels() {
    configureTranslations((key, fallback) => t(key, fallback))
}

watch(locale, syncLabels)
onMounted(() => {
    syncLabels()
    installEvents()
    handleInitialFlash(page.props.flash)
})
</script>

<template>
    <slot />
    <Teleport to="body">
        <div class="pc-toast-viewport" :dir="page.props.direction || (locale === 'ar' ? 'rtl' : 'ltr')">
            <div
                v-if="notifications.length"
                class="pc-toast-stack"
                role="region"
                :aria-label="labels.notificationsTitle"
                aria-live="polite"
                aria-atomic="false"
                aria-relevant="additions"
            >
                <AdminToast v-for="item in notifications" :key="item.id" :item="item" />
                <button v-if="notifications.length > 1" type="button" class="pc-toast-clear" @click="clear">
                    {{ labels.clearAll }}
                </button>
            </div>
        </div>
        <AdminConfirmDialog />
    </Teleport>
</template>
