import { router } from '@inertiajs/vue3'

export function createCI(payload, options = {}) {
  return router.post('/projetos/criar-ci', payload, options)
}