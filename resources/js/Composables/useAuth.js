import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import pluralize from 'pluralize'

export function useAuth() {
    const page = usePage()

    const user = computed(() => page.props.auth?.user ?? null)
    const permissions = computed(() => page.props.auth?.permissions ?? [])
    const roles = computed(() => page.props.auth?.roles ?? [])
    const currentRoute = computed(() => page.props.auth?.currentRoute ?? null)
    
    const singularize = (word) => {
        if (!word) return
        return pluralize?.singular(word)
    }
    
    const can = (permission) => {
        return permissions.value.includes(permission)
    }

    const parseRoute = () => {
        if (!currentRoute.value) return {}

        const parts = currentRoute.value.split('.')
        
        return {
            object: singularize(parts[0]) ?? null,
            module: singularize(parts[1]) ?? null,
            phase: singularize(parts[2]) ?? null,
            action: parts[3] ?? null,
        }
    }
    
    
    const hasRole = (role) => {
        if (Array.isArray(role)) {
            return role.some(r => roles.value.includes(r))
        }
        return roles.value.includes(role)
    }

    const canPerform = (action) => {
        const { phase, module } = parseRoute()
        
        const scope = phase || module
        
        if (!scope) return true

        return can(`${scope}.${action}`)
    }

    return {
        user,
        permissions,
        roles,
        currentRoute,
        can,
        parseRoute,
        hasRole,
        canPerform,
    }
}