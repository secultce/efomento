<script setup>
import { ref, computed, onMounted } from 'vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import DocumentEvaluationList from '@/Components/DocumentEvaluationList.vue';
import { useSnackbar } from '@/Composables/useSnackbar';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
});

const { showSnackbar } = useSnackbar();

const groups = ref([]);
const loadingFiles = ref(false);
const tramitando = ref(false);

const allFilesEvaluated = computed(
    () => groups.value.length > 0 && groups.value.every((g) => g.files.every((f) => f.status !== null))
);

onMounted(fetchFiles);

async function fetchFiles() {
    loadingFiles.value = true;
    try {
        const { data } = await window.axios.get(`/projetos/${props.project.id}/analise-juridica`);
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

async function tramitar() {
    if (!allFilesEvaluated.value) {
        showSnackbar('Avalie todos os documentos antes de tramitar.', 'warning');
        return;
    }

    tramitando.value = true;
    try {
        showSnackbar('Análise jurídica tramitada com sucesso!', 'success');
    } finally {
        tramitando.value = false;
    }
}
</script>

<template>
    <v-tabs-window-item value="2">
        <v-sheet class="pa-5">
            <div class="flex flex-col gap-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold">Campos para você avaliar</h2>
                    <v-btn
                        variant="outlined"
                        color="primary"
                        class="rounded-lg text-xs"
                        @click="showSnackbar('Alterações salvas com sucesso!', 'success')"
                    >
                        Salvar alterações
                    </v-btn>
                </div>

                <div>
                    <p class="text-xs text-gray-500 mb-1">Links auxiliares</p>
                    <AuxLinks />
                </div>

                <div>
                    <p class="text-sm font-semibold mb-3">Avalie os documentos</p>

                    <v-progress-linear v-if="loadingFiles" indeterminate color="primary" class="mb-4" />

                    <DocumentEvaluationList
                        v-else-if="groups.length > 0"
                        :groups="groups"
                        :project-id="project.id"
                        @status-updated="onStatusUpdated"
                    />

                    <p v-else class="text-sm text-gray-400">Nenhum documento encontrado para avaliação.</p>
                </div>

                <div class="flex justify-center pt-2">
                    <v-btn
                        color="secondary"
                        class="text-black font-bold px-16 rounded-lg"
                        :disabled="!allFilesEvaluated"
                        :loading="tramitando"
                        @click="tramitar"
                    >
                        tramitar
                    </v-btn>
                </div>
            </div>
        </v-sheet>
    </v-tabs-window-item>
</template>
