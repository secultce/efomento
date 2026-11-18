import { router } from '@inertiajs/vue3';

export function assignSupervisor(payload, options = {}) {
    return router.post('/projetos/atribuir-fiscal', payload, options);
}

export function returnProcess(projectId, stageId, payload, options = {}) {
    return router.post(`/projetos/${projectId}/etapas/${stageId}/devolver`, payload, options);
}
