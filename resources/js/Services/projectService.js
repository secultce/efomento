import { router } from '@inertiajs/vue3'

export function assignSupervisor(payload, options = {}) {
  return router.post('/projetos/atribuir-fiscal', payload, options)
}