import { router } from '@inertiajs/vue3';
import axios from 'axios';

export function saveCI(payload, options = {}) {
    return router.post('/projetos/criar-ci', payload, options);
}

export async function downloadDocumentsZip(projectIds) {
    const response = await axios.post(
        '/projetos/documentos/download-zip',
        { project_ids: projectIds },
        { responseType: 'blob' }
    );

    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/zip' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'documentos.zip';
    link.click();
    URL.revokeObjectURL(url);
}
