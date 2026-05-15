import { ref, computed } from 'vue';
import { useSnackbar } from '@/Composables/useSnackbar';

export function useLegalAnalysis(project) {
    const { showSnackbar } = useSnackbar();

    const groups = ref([]);
    const loadingFiles = ref(false);
    const in_progress = ref(false);

    const allFilesEvaluated = computed(
        () => groups.value.length > 0 && groups.value.every((g) => g.files.every((f) => f.status !== null))
    );

    async function fetchFiles() {
        loadingFiles.value = true;
        try {
            const { data } = await window.axios.get(`/projetos/${project.value.id}/analise-juridica`);
            groups.value = data;
        } catch {
            showSnackbar('Erro ao carregar documentos.', 'error');
        } finally {
            loadingFiles.value = false;
        }
    }

    function onStatusUpdated({ fileId, status }) {
        for (const group of groups.value) {
            const file = group.files.find((f) => f.id === fileId);
            if (file) {
                file.status = status;
                break;
            }
        }
    }

    async function process() {
        if (!allFilesEvaluated.value) {
            showSnackbar('Avalie todos os documentos antes de tramitar.', 'warning');
            return;
        }
        in_progress.value = true;
        try {
            showSnackbar('Análise jurídica tramitada com sucesso!', 'success');
        } finally {
            in_progress.value = false;
        }
    }

    return {
        groups,
        loadingFiles,
        in_progress,
        allFilesEvaluated,
        fetchFiles,
        onStatusUpdated,
        process,
    };
}
