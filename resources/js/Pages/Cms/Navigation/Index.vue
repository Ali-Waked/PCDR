<script setup>
import CmsLayout from '../../../Layouts/CmsLayout.vue'
import NavigationMenuEditor from '../../../Components/Cms/NavigationMenuEditor.vue'
import { useI18n } from '../../../composables/useI18n'

defineProps({
    menus: { type: Array, default: () => [] },
    routeKeys: { type: Array, default: () => [] },
})

const { t } = useI18n()
</script>

<template>
    <CmsLayout :title="t('ui.cms_navigation')" :subtitle="t('cms_auth_note')">
        <NavigationMenuEditor v-for="menu in menus" :key="menu.id" :menu="menu" :route-keys="routeKeys" />
    </CmsLayout>
</template>
