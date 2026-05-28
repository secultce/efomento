<script setup>
import { computed, ref } from 'vue';
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import HandleTCDialog from '@/Pages/Projects/Partials/Actions/Formalization/HandleTCDialog.vue';
import DocumentListDialog from '@/Pages/Projects/Partials/Actions/Formalization/DocumentListDialog.vue';
import { downloadDocumentsZip } from '@/Services/documentService';

const props = defineProps({
    selectedProjects: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

const viewHistory = ref(false);
const TCDialog = ref(false);
const docListDialog = ref(false);
const type = ref('');

function openTCDialog() {
    TCDialog.value = true;
}

function openNoticeHistory() {
    viewHistory.value = true;
}

function openDocListDialog(docType) {
    type.value = docType;
    docListDialog.value = true;
}

const downloadingZip = ref(false);
const errorMessage = ref('');
const showError = ref(false);

async function downloadZip(documentType) {
    if (!props.selectedProjects?.length) {
        errorMessage.value = 'Selecione pelo menos 1 projeto para baixar os documentos.';
        showError.value = true;
        return;
    }

    downloadingZip.value = true;
    try {
        await downloadDocumentsZip(props.selectedProjects, documentType);
    } catch {
        errorMessage.value = 'Erro ao baixar os documentos. Tente novamente.';
        showError.value = true;
    } finally {
        downloadingZip.value = false;
    }
}

const selectedProjectsList = computed(() => {
    return props.projects.filter((p) => props.selectedProjects.includes(p.id));
});

const selectedDocuments = computed(() => {
    const currentType = type.value?.toLowerCase();
    return selectedProjectsList.value.flatMap((p) =>
        (p.documents ?? []).filter((d) => d.type?.toLowerCase() === currentType).map((d) => ({ ...d, project: p }))
    );
});

const anyProjectsHasTC = computed(() => {
    return selectedProjectsList.value.some((p) => p.documents?.some((d) => d.type === 'tc'));
});

const selectedTC = computed(() => {
    const projectWithTC = selectedProjectsList.value.find((p) => p.documents?.some((d) => d.type === 'tc'));

    if (!projectWithTC) return null;

    const tcDocument = projectWithTC.documents.find((d) => d.type === 'tc');

    return {
        content: tcDocument.body,
        headerImages: (tcDocument.images ?? []).filter((i) => i.section === 'header' || i.section?.value === 'header'),
        footerImages: (tcDocument.images ?? []).filter((i) => i.section === 'footer' || i.section?.value === 'footer'),
    };
});
</script>

<template>
    <HandleTCDialog v-model="TCDialog" :project-ids="selectedProjects" :edit-data="selectedTC" />
    <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice?.id" />
    <DocumentListDialog v-model="docListDialog" :documents="selectedDocuments" />
    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg">Ações disponíveis para você </v-card-title>
        <v-card-text class="flex flex-col gap-4">
            <div class="w-full pt-2 flex flex-col gap-1">
                <template v-if="anyProjectsHasTC">
                    <p>Termo de execução cultural</p>
                    <v-btn
                        class="w-full !shadow-none !font-bold !border-gray-300 !bg-white !text-[#2d353fFF] rounded-lg text-xs gap-6"
                        :disabled="!(props.selectedProjects?.length > 0)"
                        variant="outlined"
                        @click="openTCDialog"
                    >
                        <span class="w-full text-left"> Editar Termo de Execução Cultural </span>

                        <template #append>
                            <v-icon size="18"> mdi-pencil </v-icon>
                        </template>
                    </v-btn>
                </template>

                <template v-else>
                    <v-btn
                        class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                        :disabled="!(props.selectedProjects?.length > 0)"
                        @click="openTCDialog"
                    >
                        Criar termo para assinatura
                    </v-btn>
                </template>

                <div class="w-full flex flex-col sm:flex-row gap-2 mt-2">
                    <v-btn
                        variant="outlined"
                        class="flex-1 !shadow-none !border-primary !text-primary rounded-lg text-xs"
                        :loading="downloadingZip"
                        color="primary"
                        :disabled="!(selectedProjects?.length > 0)"
                        @click="downloadZip('tc')"
                    >
                        Baixar todos
                    </v-btn>
                    <v-btn
                        class="flex-1 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
                        :disabled="!(selectedProjects?.length > 0)"
                        @click="openDocListDialog('tc')"
                    >
                        Conferir documentos
                    </v-btn>
                </div>

                <p class="mt-4">Extrato do termo</p>
                <v-btn
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="!(props.selectedProjects?.length > 0)"
                >
                    Criar extrato do termo
                </v-btn>

                <p class="mt-4">Criar parecer jurídico geral</p>
                <v-btn
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="!(props.selectedProjects?.length > 0)"
                >
                    Criar parecer jurídico
                </v-btn>

                <div class="w-full flex flex-col gap-1">
                    <v-divider class="my-4"></v-divider>
                    <p>Conferir histórico de alterações nos processos</p>
                    <v-btn
                        variant="outlined"
                        color="outlineSecondary"
                        class="rounded-lg w-full"
                        @click="openNoticeHistory"
                    >
                        Conferir Histórico
                    </v-btn>
                </div>
            </div>
        </v-card-text>
    </v-card>

    <v-snackbar v-model="showError" color="error" timeout="4000">
        {{ errorMessage }}
    </v-snackbar>
</template>
