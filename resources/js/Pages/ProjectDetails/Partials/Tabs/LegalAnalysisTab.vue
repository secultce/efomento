<script setup>
import { ref, computed, onMounted } from 'vue';
import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import DocumentEvaluationList from '@/Components/DocumentEvaluationList.vue';
import { viewSections } from '@/Schemas/Opening';
import { useSnackbar } from '@/Composables/useSnackbar';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
});

const { showSnackbar } = useSnackbar();

const activeViewIndex = ref('all');
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
    <SplitScreenTab value="2">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <p class="font-bold text-lg">Dados disponíveis para consulta</p>
                    <p class="text-sm text-gray-600">Utilize os filtros abaixo para navegar entre os dados</p>
                </div>

                <SectionChips v-model="activeViewIndex" :sections="viewSections" />

                <div class="space-y-8">
                    <template v-for="(section, index) in viewSections" :key="'view-' + section.title">
                        <SectionContent
                            v-if="activeViewIndex === 'all' || activeViewIndex === index"
                            :section="section"
                            :project="project"
                        />
                    </template>
                </div>
            </div>
        </template>

        <template #right-content>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <p class="font-bold text-lg">Campos para você avaliar</p>
                    <v-btn
                        variant="outlined"
                        color="outlineSecondary"
                        class="rounded-lg"
                        @click="showSnackbar('Alterações salvas com sucesso!', 'success')"
                    >
                        Salvar alterações
                    </v-btn>
                </div>

                <div>
                    <p class="font-bold text-sm mb-2">Links auxiliares</p>
                    <AuxLinks />
                </div>

                <div>
                    <p class="font-bold text-sm mb-3">Avalie os documentos</p>

                    <v-progress-linear v-if="loadingFiles" indeterminate color="primary" class="mb-4" />

                    <DocumentEvaluationList
                        v-else-if="groups.length > 0"
                        :groups="groups"
                        :project-id="project.id"
                        @status-updated="onStatusUpdated"
                    />

                    <p v-else class="text-sm text-gray-400">Nenhum documento encontrado para avaliação.</p>
                </div>

                <div class="flex justify-center pt-4">
                    <v-btn
                        class="w-1/2 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
                        :disabled="!allFilesEvaluated"
                        :loading="tramitando"
                        @click="tramitar"
                    >
                        tramitar
                    </v-btn>
                </div>
            </div>
        </template>
    </SplitScreenTab>
</template>
