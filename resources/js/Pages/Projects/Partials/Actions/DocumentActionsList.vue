<script setup>
import { computed, ref } from 'vue';

import DocumentDownloadMenu from '@/Components/DocumentDownloadMenu.vue';
import { downloadDocumentsZip } from '@/Services/documentService';
import { documentConfigs } from '@/Schemas/Config/documentConfig.js';
import HandleDocumentsDialog from './HandleDocumentsDialog.vue';
import DocumentListDialog from './DocumentListDialog.vue';

const props = defineProps({
    documentTypes: { type: Array, default: () => [] },
    selectedProjects: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    permissionMessage: {
        type: String,
        default: 'Você não tem permissão para criar ou editar este documento, contate o administrador do sistema.',
    },
    // data-cy por tipo de documento: { [type]: { create, edit, list } }
    testIds: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['saved']);

const documentDialog = ref(false);
const docListDialog = ref(false);
const currentType = ref(null);

const downloadingZip = ref(false);
const errorMessage = ref('');
const showError = ref(false);

const availableDocuments = computed(() =>
    props.documentTypes
        .filter((type) => documentConfigs[type])
        .map((type) => ({
            type,
            title: documentConfigs[type].name,
            createLabel: documentConfigs[type].titleCreate,
            editLabel: documentConfigs[type].titleEdit,
            testIds: props.testIds[type] ?? {},
        }))
);

const selectedProjectsList = computed(() => props.projects.filter((p) => props.selectedProjects.includes(p.id)));

const hasSelection = computed(() => props.selectedProjects.length > 0);

const sameType = (document, type) => String(document.type).toLowerCase() === String(type).toLowerCase();

const firstDocumentOfType = (type) => {
    for (const project of selectedProjectsList.value) {
        const document = project.documents?.find((d) => sameType(d, type));

        if (document) return document;
    }

    return null;
};

const projectHasDocument = (type) => firstDocumentOfType(type) !== null;

const imagesOfSection = (document, section) =>
    (document.images ?? []).filter((i) => i.section === section || i.section?.value === section);

const selectedDocument = computed(() => {
    const document = firstDocumentOfType(currentType.value);

    if (!document) return null;

    return {
        content: document.body,
        headerImages: imagesOfSection(document, 'header'),
        footerImages: imagesOfSection(document, 'footer'),
    };
});

const selectedDocuments = computed(() =>
    selectedProjectsList.value.flatMap((p) =>
        (p.documents ?? []).filter((d) => sameType(d, currentType.value)).map((d) => ({ ...d, project: p }))
    )
);

function openDocumentDialog(type) {
    currentType.value = type;
    documentDialog.value = true;
}

function openDocListDialog(type) {
    currentType.value = type;
    docListDialog.value = true;
}

async function downloadZip(type, format) {
    if (!hasSelection.value) {
        errorMessage.value = 'Selecione pelo menos 1 projeto para baixar os documentos.';
        showError.value = true;
        return;
    }

    downloadingZip.value = true;

    try {
        await downloadDocumentsZip(props.selectedProjects, type, format);
    } catch {
        errorMessage.value = 'Erro ao baixar os documentos. Tente novamente.';
        showError.value = true;
    } finally {
        downloadingZip.value = false;
    }
}
</script>

<template>
    <HandleDocumentsDialog
        v-model="documentDialog"
        :type="currentType"
        :project-ids="selectedProjects"
        :edit-data="selectedDocument"
        @saved="emit('saved')"
    />

    <DocumentListDialog v-model="docListDialog" :documents="selectedDocuments" />

    <div class="w-full pt-2 flex flex-col gap-6">
        <div v-for="doc in availableDocuments" :key="doc.type" class="flex flex-col gap-2">
            <p>{{ doc.title }}</p>

            <div v-permission="{ condition: canManage, message: permissionMessage }" class="flex flex-col gap-1">
                <v-btn
                    v-if="projectHasDocument(doc.type)"
                    class="w-full !shadow-none !font-bold !border-gray-300 !bg-white !text-[#2d353fFF] rounded-lg text-xs"
                    variant="outlined"
                    :disabled="!hasSelection || !canManage"
                    :data-cy="doc.testIds.edit ?? 'edit-document-button'"
                    @click="openDocumentDialog(doc.type)"
                >
                    <span class="w-full text-left">
                        {{ doc.editLabel }}
                    </span>

                    <template #append>
                        <v-icon size="18"> mdi-pencil </v-icon>
                    </template>
                </v-btn>

                <v-btn
                    v-else
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="!hasSelection || !canManage"
                    :data-cy="doc.testIds.create ?? 'create-document-button'"
                    @click="openDocumentDialog(doc.type)"
                >
                    {{ doc.createLabel }}
                </v-btn>
            </div>

            <div class="grid w-full grid-cols-[repeat(auto-fit,minmax(min(100%,9rem),1fr))] gap-2">
                <DocumentDownloadMenu
                    label="Baixar todos"
                    button-class="flex-1 !shadow-none !border-primary !text-primary rounded-lg text-xs"
                    :loading="downloadingZip"
                    :disabled="!hasSelection"
                    @download="downloadZip(doc.type, $event)"
                />

                <v-btn
                    class="flex-1 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
                    :disabled="!hasSelection"
                    :data-cy="doc.testIds.list"
                    @click="openDocListDialog(doc.type)"
                >
                    Conferir documentos
                </v-btn>
            </div>
        </div>
    </div>

    <v-snackbar v-model="showError" color="error" timeout="4000" location="top">
        {{ errorMessage }}
    </v-snackbar>
</template>
