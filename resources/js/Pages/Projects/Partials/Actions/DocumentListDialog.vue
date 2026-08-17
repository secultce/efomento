<script setup>
import { computed, ref } from 'vue';
import DocumentViewerDialog from '@/Components/DocumentViewerDialog.vue';

const props = defineProps({
    modelValue: Boolean,

    documents: {
        type: Array,
        default: () => [],
    },

    title: {
        type: String,
        default: 'Lista de documentos',
    },

    emptyText: {
        type: String,
        default: 'Nenhum documento encontrado para os projetos selecionados.',
    },

    showDownload: {
        type: Boolean,
        default: true,
    },

    showPreview: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const viewerOpen = ref(false);
const viewerDocument = ref(null);

function typeName(doc) {
    return doc.type_name ?? doc.type_label ?? doc.type;
}

function contextLabel(doc) {
    return doc.project ? 'do projeto' : 'do edital';
}

function contextName(doc) {
    return doc.project?.title_project ?? doc.project?.agent?.name ?? doc.notice?.name ?? 'Edital';
}

function download(doc, format) {
    window.open(`/projetos/documentos/${doc.id}/download?format=${format}`, '_blank');
}

function view(doc) {
    viewerDocument.value = doc;
    viewerOpen.value = true;
}

function close() {
    isOpen.value = false;
}
</script>

<template>
    <DocumentViewerDialog v-model="viewerOpen" :document="viewerDocument" />

    <v-dialog v-model="isOpen" max-width="560" persistent>
        <v-card class="rounded-lg">
            <v-card-title class="font-weight-bold pa-4 pb-2">
                {{ title }}
            </v-card-title>

            <v-card-text class="pa-4 pt-2">
                <div v-if="!documents.length" class="text-center text-gray-400 py-6">
                    {{ emptyText }}
                </div>

                <div
                    v-for="doc in documents"
                    :key="doc.id"
                    class="d-flex items-center justify-between border rounded-lg px-4 py-3 mb-2"
                >
                    <span class="text-sm text-[#3b3b3c] font-medium">
                        {{ typeName(doc) }} {{ contextLabel(doc) }}:
                        {{ contextName(doc) }}
                    </span>

                    <div class="d-flex gap-1 flex-shrink-0 ml-3">
                        <v-menu v-if="showDownload" location="bottom end">
                            <template #activator="{ props: menuProps }">
                                <v-btn
                                    v-bind="menuProps"
                                    icon
                                    size="small"
                                    variant="text"
                                    color="#008344"
                                    aria-label="Baixar documento"
                                >
                                    <v-icon size="20"> mdi-download </v-icon>
                                </v-btn>
                            </template>

                            <v-list density="compact">
                                <v-list-item
                                    title="Baixar PDF"
                                    prepend-icon="mdi-file-pdf-box"
                                    @click="download(doc, 'pdf')"
                                />
                                <v-list-item
                                    title="Baixar DOCX"
                                    prepend-icon="mdi-file-word-box"
                                    @click="download(doc, 'docx')"
                                />
                                <v-list-item
                                    title="Baixar DOCX Casa Civil"
                                    prepend-icon="mdi-file-word-box"
                                    @click="download(doc, 'docx_casa_civil')"
                                />
                            </v-list>
                        </v-menu>

                        <v-btn v-if="showPreview" icon size="small" variant="text" color="#008344" @click="view(doc)">
                            <v-icon size="20"> mdi-eye-outline </v-icon>
                        </v-btn>
                    </div>
                </div>
            </v-card-text>

            <v-card-actions class="pa-4 pt-0">
                <v-spacer />

                <v-btn class="rounded-lg bg-secondary text-black" @click="close"> Fechar </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
