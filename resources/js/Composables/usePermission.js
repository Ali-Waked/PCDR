import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * UI-only permission helper. Laravel remains the sole authorization authority;
 * this helper only mirrors the server's decisions for presentation.
 */
export function usePermission(additionalPermissions = []) {
    const page = usePage()
    const user = computed(() => page.props.auth?.user ?? null)
    const permissions = computed(() => [
        ...(page.props.auth?.permissions ?? []),
        ...(additionalPermissions.value ?? additionalPermissions ?? []),
    ])
    const isPlatformOwner = computed(() => user.value?.is_platform_owner === true)
    const authority = computed(() => page.props.authority ?? {})
    const deniedPatterns = computed(() => authority.value?.denied_permission_patterns ?? [])

    function isAuthorityPermission(permission) {
        if (typeof permission !== 'string' || permission === '' || permission === '*') {
            return false
        }

        const normalized = permission.toLowerCase()

        return deniedPatterns.value.some((pattern) => {
            const value = String(pattern).toLowerCase()

            return value.startsWith('.')
                ? normalized.endsWith(value) || normalized === value.slice(1)
                : normalized.startsWith(value)
        })
    }

    function can(permission) {
        if (isAuthorityPermission(permission)) {
            return false
        }

        return isPlatformOwner.value || permissions.value.includes(permission)
    }

    function canAny(list) {
        return list.some(can)
    }

    const canInstitutionalApprove = computed(
        () => authority.value?.institutional_approval?.granted === true && can('institutional.approve'),
    )

    return {
        can,
        canAny,
        isPlatformOwner,
        permissions,
        isAuthorityPermission,
        canInstitutionalApprove,
    }
}
