
import { useSnackbar } from '@/Composables/useSnackbar'

export default {
  mounted(el, binding) {
    const { showSnackbar } = useSnackbar()
    
    const options = normalize(binding.value)

    function hasAccess() {

      if (typeof options.condition === 'boolean') {
        return options.condition
      }

      return true
    }

    function handleClick(e) {
      if (!hasAccess()) {
        e.preventDefault()
        e.stopPropagation()

        showSnackbar(
          options.message || 'Você não tem permissão para realizar esta ação',
          'error'
        )
      }
    }

    if (!hasAccess()) {
      el.classList.add('cursor-not-allowed', 'opacity-60')
    }

    el.addEventListener('click', handleClick)

    el._permissionCleanup = () => {
      el.removeEventListener('click', handleClick)
    }
  },

  unmounted(el) {
    el._permissionCleanup && el._permissionCleanup()
  }
}

function normalize(value) {
  if (typeof value === 'string') {
    return { permission: value }
  }

  return value || {}
}