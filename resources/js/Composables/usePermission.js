import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * UI-only permission helper. The Laravel backend remains the sole authority.
 */
export function usePermission() {
    const page = usePage()
    const user = computed(() => page.props.auth?.user ?? null)
    const permissions = computed(() => page.props.auth?.permissions ?? [])
    const isPlatformOwner = computed(() => user.value?.is_platform_owner === true)

    function can(permission) {
        return isPlatformOwner.value || permissions.value.includes(permission)
    }

    function canAny(list) {
        return list.some(can)
    }

    return { can, canAny, isPlatformOwner, permissions }
}
