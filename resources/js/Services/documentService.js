import { router } from '@inertiajs/vue3';
import axios from 'axios';

export function saveDocument(payload, options = {}) {
    return router.post('/projetos/criar-documento', payload, options);
}

export async function downloadDocumentsZip(projectIds, type) {
    const response = await axios.post(
        '/projetos/documentos/download-zip',
        { project_ids: projectIds, type: type },
        { responseType: 'blob' }
    );

    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/zip' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'documentos.zip';
    link.click();
    URL.revokeObjectURL(url);
}
