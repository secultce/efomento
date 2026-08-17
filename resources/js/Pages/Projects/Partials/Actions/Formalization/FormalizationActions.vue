<script setup>
import { computed, ref } from 'vue';

import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import HandleDocumentsDialog from '../HandleDocumentsDialog.vue';
import DocumentListDialog from '../DocumentListDialog.vue';

import { downloadDocumentsZip } from '@/Services/documentService';
import { DOCUMENT_TYPES, documentConfigs } from '@/Schemas/Config/documentConfig.js';
import { usePermissions } from '@/Composables/usePermissions';

const { canManageFormalization } = usePermissions();

const props = defineProps({
    selectedProjects: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

const viewHistory = ref(false);
const documentDialog = ref(false);
const docListDialog = ref(false);

const type = ref(null);

const availableDocuments = computed(() =>
    [DOCUMENT_TYPES.TC, DOCUMENT_TYPES.ET, DOCUMENT_TYPES.PJ]
        .filter((type) => documentConfigs[type])
        .map((type) => ({
            type,
            title: documentConfigs[type].name,
            createLabel: documentConfigs[type].titleCreate,
            editLabel: documentConfigs[type].titleEdit,
        }))
);

function openDocumentDialog(docType) {
    if (!docType) {
        console.error('Document type missing');
        return;
    }

    type.value = docType;
    documentDialog.value = true;
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

async function downloadZip(documentType, format) {
    if (!props.selectedProjects?.length) {
        errorMessage.value = 'Selecione pelo menos 1 projeto para baixar os documentos.';
        showError.value = true;
        return;
    }

    downloadingZip.value = true;

    try {
        await downloadDocumentsZip(props.selectedProjects, documentType, format);
    } catch {
        errorMessage.value = 'Erro ao baixar os documentos. Tente novamente.';
        showError.value = true;
    } finally {
        downloadingZip.value = false;
    }
}

const selectedProjectsList = computed(() => props.projects.filter((p) => props.selectedProjects.includes(p.id)));

function projectHasDocument(docType) {
    return selectedProjectsList.value.some((p) =>
        p.documents?.some((d) => String(d.type).toLowerCase() === String(docType).toLowerCase())
    );
}

const selectedDocuments = computed(() => {
    const currentType = String(type.value).toLowerCase();

    return selectedProjectsList.value.flatMap((p) =>
        (p.documents ?? [])
            .filter((d) => d.type?.toLowerCase() === currentType)
            .map((d) => ({
                ...d,
                project: p,
            }))
    );
});

const selectedDocument = computed(() => {
    const currentType = type.value?.toLowerCase();

    const project = selectedProjectsList.value.find((p) =>
        p.documents?.some((d) => d.type?.toLowerCase() === currentType)
    );

    if (!project) return null;

    const document = project.documents.find((d) => d.type?.toLowerCase() === currentType);

    if (!document) return null;

    return {
        content: document.body,
        headerImages: (document.images ?? []).filter((i) => i.section === 'header' || i.section?.value === 'header'),
        footerImages: (document.images ?? []).filter((i) => i.section === 'footer' || i.section?.value === 'footer'),
    };
});
</script>

<template>
    <HandleDocumentsDialog
        v-model="documentDialog"
        :type="type"
        :project-ids="selectedProjects"
        :edit-data="selectedDocument"
    />

    <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice?.id" />

    <DocumentListDialog v-model="docListDialog" :documents="selectedDocuments" />

    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg"> Ações disponíveis para você </v-card-title>

        <v-card-text class="flex flex-col gap-4">
            <div class="w-full pt-2 flex flex-col gap-6">
                <div v-for="doc in availableDocuments" :key="doc.type" class="flex flex-col gap-2">
                    <p>{{ doc.title }}</p>

                    <div
                        v-permission="{
                            condition: canManageFormalization,
                            message:
                                'Você não tem permissão para criar ou editar este documento, contate o administrador do sistema.',
                        }"
                        class="flex flex-col gap-1"
                    >
                        <template v-if="projectHasDocument(doc.type)">
                            <v-btn
                                class="w-full !shadow-none !font-bold !border-gray-300 !bg-white !text-[#2d353fFF] rounded-lg text-xs"
                                variant="outlined"
                                :disabled="!selectedProjects?.length || !canManageFormalization"
                                data-cy="edit-document-button"
                                @click="openDocumentDialog(doc.type)"
                            >
                                <span class="w-full text-left">
                                    {{ doc.editLabel }}
                                </span>

                                <template #append>
                                    <v-icon size="18"> mdi-pencil </v-icon>
                                </template>
                            </v-btn>
                        </template>

                        <template v-else>
                            <v-btn
                                class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                                :disabled="!selectedProjects?.length || !canManageFormalization"
                                data-cy="create-document-button"
                                @click="openDocumentDialog(doc.type)"
                            >
                                {{ doc.createLabel }}
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
                                    color="primary"
                                    :disabled="!selectedProjects?.length"
                                >
                                    Baixar todos
                                    <v-icon end size="16">mdi-chevron-down</v-icon>
                                </v-btn>
                            </template>

                            <v-list density="compact">
                                <v-list-item
                                    title="PDF"
                                    prepend-icon="mdi-file-pdf-box"
                                    @click="downloadZip(doc.type, 'pdf')"
                                />
                                <v-list-item
                                    title="DOCX"
                                    prepend-icon="mdi-file-word-box"
                                    @click="downloadZip(doc.type, 'docx')"
                                />
                                <v-list-item
                                    title="DOCX Casa Civil"
                                    prepend-icon="mdi-file-word-box"
                                    @click="downloadZip(doc.type, 'docx_casa_civil')"
                                />
                            </v-list>
                        </v-menu>

                        <v-btn
                            class="flex-1 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
                            :disabled="!selectedProjects?.length"
                            @click="openDocListDialog(doc.type)"
                        >
                            Conferir documentos
                        </v-btn>
                    </div>
                </div>

                <div class="w-full flex flex-col gap-1">
                    <v-divider class="my-4" />

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
