import { ref } from 'vue'

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')
const snackbarTimeout = ref(3000)

export function useSnackbar() {
  function showSnackbar(message, type = 'success', timeout = 3000) {
    snackbarText.value = message
    snackbarColor.value = type
    snackbarTimeout.value = timeout
    snackbar.value = true
  }

  return {
    snackbar,
    snackbarText,
    snackbarColor,
    snackbarTimeout,
    showSnackbar
  }
}