<script setup>
import CmsDashboardLayout from '../../../Layouts/CmsDashboardLayout.vue'
import NavigationMenuEditor from '../../../Components/Cms/NavigationMenuEditor.vue'
import { useI18n } from '../../../composables/useI18n'

defineProps({
    menus: { type: Array, default: () => [] },
    routeKeys: { type: Array, default: () => [] },
})

const { t } = useI18n()
</script>

<template>
    <CmsDashboardLayout :title="t('ui.cms_navigation')" :subtitle="t('ui.dashboard_editorial')">
        <div class="pc-stack pcdr-enter">
            <NavigationMenuEditor v-for="menu in menus" :key="menu.id" :menu="menu" :route-keys="routeKeys" />
        </div>
    </CmsDashboardLayout>
</template>
