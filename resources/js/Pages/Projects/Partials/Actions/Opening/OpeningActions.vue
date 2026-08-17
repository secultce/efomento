<script setup>
import { computed, ref } from 'vue';
import SupervisorDialog from '@/Pages/Projects/Partials/Actions/SupervisorDialog.vue';
import { useAuth } from '@/Composables/useAuth';
import { usePermissions } from '@/Composables/usePermissions';
import { downloadDocumentsZip } from '@/Services/documentService';
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import HandleDocumentsDialog from '../HandleDocumentsDialog.vue';
import DocumentListDialog from '../DocumentListDialog.vue';

const { canPerform } = useAuth();
const { isSuperAdmin, canManageNotices } = usePermissions();

const props = defineProps({
    selectedProjects: { type: Array, default: () => [] },
    supervisorsAvailable: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

defineEmits(['saved']);

const supervisorDialog = ref(false);
const ciDialog = ref(false);
const docListDialog = ref(false);

const selectedDocuments = computed(() =>
    selectedProjectsList.value.flatMap((p) =>
        (p.documents ?? []).filter((d) => d.type?.toLowerCase() === 'ci').map((d) => ({ ...d, project: p }))
    )
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
    return isSuperAdmin.value || canPerform('opening.assign_supervisor');
});

const canCreateCI = computed(() => {
    return canManageNotices.value;
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
        headerImages: (ciDocument.images ?? []).filter((i) => i.section === 'header' || i.section?.value === 'header'),
        footerImages: (ciDocument.images ?? []).filter((i) => i.section === 'footer' || i.section?.value === 'footer'),
    };
});

function openCIDialog() {
    ciDialog.value = true;
}

const errorMessage = ref('');
const showError = ref(false);
const downloadingZip = ref(false);

async function downloadZip(format) {
    if (!props.selectedProjects?.length) {
        errorMessage.value = 'Selecione pelo menos 1 projeto para baixar os documentos.';
        showError.value = true;
        return;
    }

    downloadingZip.value = true;
    try {
        await downloadDocumentsZip(props.selectedProjects, 'ci', format);
    } catch {
        errorMessage.value = 'Erro ao baixar os documentos. Tente novamente.';
        showError.value = true;
    } finally {
        downloadingZip.value = false;
    }
}

const viewHistory = ref(false);

function openNoticeHistory() {
    viewHistory.value = true;
}
</script>
<template>
    <v-snackbar v-model="showError" color="error" timeout="4000" location="top">
        {{ errorMessage }}
    </v-snackbar>

    <HandleDocumentsDialog
        v-model="ciDialog"
        type="ci"
        :project-ids="selectedProjects"
        :edit-data="selectedCI"
        @saved="$emit('saved')"
    />
    <SupervisorDialog
        v-model="supervisorDialog"
        :project-ids="selectedProjects"
        :supervisors="supervisorsAvailable"
        @saved="$emit('saved')"
    />
    <DocumentListDialog v-model="docListDialog" :documents="selectedDocuments" />
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
                        data-cy="edit-ci-button"
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
                        data-cy="create-ci-button"
                        class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                        :disabled="!(props.selectedProjects?.length > 0) || !canCreateCI"
                        @click="openCIDialog"
                    >
                        Criar CI
                    </v-btn>
                </template>
            </div>
            <div class="w-full flex flex-col sm:flex-row gap-2">
                <v-menu location="bottom">
                    <template #activator="{ props: menuProps }">
                        <v-btn
                            v-bind="menuProps"
                            variant="outlined"
                            class="flex-1 !shadow-none !border-primary !text-primary rounded-lg text-xs"
                            :loading="downloadingZip"
                            :disabled="!selectedProjects?.length"
                            color="primary"
                        >
                            Baixar todos
                            <v-icon end size="16">mdi-chevron-down</v-icon>
                        </v-btn>
                    </template>

                    <v-list density="compact">
                        <v-list-item title="PDF" prepend-icon="mdi-file-pdf-box" @click="downloadZip('pdf')" />
                        <v-list-item title="DOCX" prepend-icon="mdi-file-word-box" @click="downloadZip('docx')" />
                        <v-list-item
                            title="DOCX Casa Civil"
                            prepend-icon="mdi-file-word-box"
                            @click="downloadZip('docx_casa_civil')"
                        />
                    </v-list>
                </v-menu>
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

                <p>Conferir histórico de alterações nos processos</p>

                <v-btn variant="outlined" color="outlineSecondary" class="rounded-lg w-full" @click="openNoticeHistory">
                    Conferir Histórico
                </v-btn>

                <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice.id" />
            </div>
        </v-card-text>
    </v-card>
</template>
