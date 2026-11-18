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

    if (!hasAccess()) {
      el.classList.add('opacity-60', 'cursor-not-allowed')

      if (getComputedStyle(el).position === 'static') {
        el.style.position = 'relative'
      }

      const overlay = document.createElement('div')
      overlay.style.position = 'absolute'
      overlay.style.inset = '0'
      overlay.style.zIndex = '10'
      overlay.style.cursor = 'not-allowed'
      overlay.style.background = 'transparent'

      overlay.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()

        showSnackbar(
          options.message || 'Você não tem permissão para realizar esta ação',
          'error'
        )
      })

      el.appendChild(overlay)

      el._permissionCleanup = () => {
        overlay.remove()
      }
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