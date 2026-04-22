import { router } from '@inertiajs/vue3'

export function saveCI(payload, options = {}) {
  return router.post('/projetos/criar-ci', payload, options)
}
