<script setup>
import { computed, ref } from 'vue';
import SupervisorDialog from '@/Pages/Projects/Partials/SupervisorDialog.vue';
import { useAuth } from '@/Composables/useAuth';
import HandleCIDialog from './HandleCIDialog.vue';
import Modal from '@/Components/Modal.vue';
import DocumentListDialog from './DocumentListDialog.vue';
import axios from 'axios';
import NoticeHistory from './NoticeHistory.vue';
import { formatAudits } from '@/Composables/useAuditFormatter';
import { downloadDocumentsZip } from '@/Services/documentService';

const { canPerform, hasRole } = useAuth();

const props = defineProps({
    selectedProjects: Array,
    supervisors_available: Array,
    projects: Array,
    notice: Object,
});

const supervisorDialog = ref(false);
const ciDialog = ref(false);
const docListDialog = ref(false);

const selectedDocuments = computed(() =>
    selectedProjectsList.value.flatMap((p) => (p.documents ?? []).map((d) => ({ ...d, project: p })))
);

function openSupervisorDialog() {
    supervisorDialog.value = true;
}

const hasProjectsWithSupervisor = computed(() => {
    return props.projects
        .filter((p) => props.selectedProjects.includes(p.id))
        .some((p) => p.opening?.supervisors?.some((s) => s.is_active));
});

const canAssignSupervisor = computed(() => {
    return hasRole('super_admin') || canPerform('opening.assign_supervisor');
});

const canCreateCI = computed(() => {
    return hasRole('super_admin') || canPerform('ci.create');
});

const selectedProjectsList = computed(() => {
    return props.projects.filter((p) => props.selectedProjects.includes(p.id));
});

const anyProjectsHasCI = computed(() => {
    return selectedProjectsList.value.some((p) => p.documents?.some((d) => d.type === 'ci'));
});

const selectedCI = computed(() => {
    const projectWithCI = selectedProjectsList.value.find((p) => p.documents?.some((d) => d.type === 'ci'));

    if (!projectWithCI) return null;

    const ciDocument = projectWithCI.documents.find((d) => d.type === 'ci');

    return {
        content: ciDocument.body,
    };
});

function openCIDialog() {
    ciDialog.value = true;
}

const errorMessage = ref('');
const showError = ref(false);
const downloadingZip = ref(false);

async function downloadZip() {
    if (!props.selectedProjects?.length) {
        errorMessage.value = 'Selecione pelo menos 1 projeto para baixar os documentos.';
        showError.value = true;
        return;
    }

    downloadingZip.value = true;
    try {
        await downloadDocumentsZip(props.selectedProjects);
    } catch {
        errorMessage.value = 'Erro ao baixar os documentos. Tente novamente.';
        showError.value = true;
    } finally {
        downloadingZip.value = false;
    }
}

const audits = ref([]);
const loadingAudits = ref(false);
const viewHistory = ref(false);

const openNoticeHistory = async () => {
    loadingAudits.value = true;

    try {
        const { data } = await axios.get(`/editais/${props.notice.id}/audits`);
        audits.value = formatAudits(data.data);
        viewHistory.value = true;
    } catch (e) {
        console.error(e);
        errorMessage.value = 'Erro ao carregar o histórico. Tente novamente.';
        showError.value = true;
    } finally {
        loadingAudits.value = false;
    }
};

const closeModal = () => {
    viewHistory.value = false;
};
</script>
<template>
    <v-snackbar v-model="showError" color="error" timeout="4000" location="top">
        {{ errorMessage }}
    </v-snackbar>
    <handle-c-i-dialog
        v-model="ciDialog"
        :project-ids="selectedProjects"
        :edit-data="selectedCI"
        @saved="$emit('saved')"
    />
    <supervisor-dialog
        v-model="supervisorDialog"
        :project-ids="selectedProjects"
        :supervisors="supervisors_available"
        @saved="$emit('saved')"
    />
    <document-list-dialog v-model="docListDialog" :documents="selectedDocuments" />
    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg">Ações disponíveis para você </v-card-title>
        <v-card-text class="flex flex-col gap-4">
            <div
                v-permission="{
                    condition: canCreateCI,
                    message:
                        'Você não tem permissão para criar um documento de comunicação interna, contate o administrador do sistema.',
                }"
                class="w-full pt-2 flex flex-col gap-1"
            >
                <template v-if="anyProjectsHasCI">
                    <p>Editar comunicação interna (CI)</p>
                    <v-btn
                        data-cy="btnCriarCI"
                        class="w-full !shadow-none !font-bold !border-gray-300 !bg-white !text-[#2d353fFF] rounded-lg text-xs gap-6"
                        :disabled="!(props.selectedProjects?.length > 0) || !canCreateCI"
                        variant="outlined"
                        @click="openCIDialog"
                    >
                        <span class="w-full text-left"> Editar Comunicação Interna </span>

                        <template #append>
                            <v-icon size="18"> mdi-pencil </v-icon>
                        </template>
                    </v-btn>
                </template>

                <template v-else>
                    <p>Criar comunicação interna (CI)</p>
                    <v-btn
                        data-cy="btnCriarCI"
                        class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                        :disabled="!(props.selectedProjects?.length > 0) || !canCreateCI"
                        @click="openCIDialog"
                    >
                        Criar CI
                    </v-btn>
                </template>
            </div>
            <div class="w-full flex flex-col sm:flex-row gap-2">
                <v-btn
                    variant="outlined"
                    class="flex-1 !shadow-none !border-primary !text-primary rounded-lg text-xs"
                    :loading="downloadingZip"
                    color="primary"
                    @click="downloadZip"
                >
                    Baixar todos
                </v-btn>
                <v-btn
                    data-cy="btnConferirDocumentos"
                    class="flex-1 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
                    :disabled="!(selectedProjects?.length > 0)"
                    @click="docListDialog = true"
                >
                    Conferir documentos
                </v-btn>
            </div>

            <div
                v-permission="{
                    condition: canAssignSupervisor,
                    message:
                        'Você não tem permissão para atribuir fiscais ao projeto, contate o administrador do sistema.',
                }"
                class="w-full pt-2 flex flex-col gap-1"
            >
                <p>Atribuir fiscal aos selecionados</p>
                <v-btn
                    data-cy="btnAtribuirFiscal"
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="!(props.selectedProjects?.length > 0) || !canAssignSupervisor"
                    @click="openSupervisorDialog"
                >
                    Atribuir Fiscal
                </v-btn>
                <p v-if="hasProjectsWithSupervisor" class="text-orange-600 max-w-[22em] whitespace-normal text-xs">
                    Um ou mais projetos selecionados já possuem um fiscal. Ao atribuir um novo fiscal, o anterior será
                    desativado.
                </p>
            </div>
            <div class="w-full flex flex-col gap-1">
                <v-divider class="my-4"></v-divider>
                <p class="">Conferir histórico de alterações nos processos</p>
                <v-btn variant="outlined" color="outlineSecondary" class="rounded-lg w-full" @click="openNoticeHistory">
                    Conferir Histórico
                </v-btn>

                <Modal :show="viewHistory" @close="closeModal">
                    <div class="p-6 max-h-[80vh] overflow-y-auto">
                        <h3 class="text-lg font-bold mb-4">Histórico de alterações</h3>

                        <div v-if="loadingAudits" class="flex justify-center">
                            <v-progress-circular indeterminate />
                        </div>

                        <div v-else-if="audits.length === 0">
                            <p>Nenhuma alteração encontrada.</p>
                        </div>

                        <NoticeHistory v-else :audits="audits" />

                        <div class="mt-6 flex justify-end">
                            <v-btn
                                class="!shadow-none !bg-[#ffcc05FF] !font-bold !text-xs rounded-lg"
                                @click="closeModal"
                            >
                                Fechar
                            </v-btn>
                        </div>
                    </div>
                </Modal>
            </div>
        </v-card-text>
    </v-card>
</template>
