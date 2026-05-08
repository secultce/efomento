<script setup>
import { computed, ref } from 'vue'
import DocumentViewerDialog from '@/Components/DocumentViewerDialog.vue'

const props = defineProps({
    modelValue: Boolean,
    documents: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:modelValue'])

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
})

const viewerOpen = ref(false)
const viewerDocument = ref(null)

function typeName(doc) {
    return doc.type_name ?? doc.type_label ?? doc.type
}

function download(doc) {
    window.open(`/projetos/documentos/${doc.id}/download`, '_blank')
}

function view(doc) {
    viewerDocument.value = doc
    viewerOpen.value = true
}

function close() {
    isOpen.value = false
}
</script>

<template>
    <DocumentViewerDialog v-model="viewerOpen" :document="viewerDocument" />

    <v-dialog v-model="isOpen" max-width="560" persistent>
        <v-card class="rounded-lg">
            <v-card-title class="font-weight-bold pa-4 pb-2">
                Lista de documentos
            </v-card-title>

            <v-card-text class="pa-4 pt-2">
                <div v-if="!documents.length" class="text-center text-gray-400 py-6">
                    Nenhum documento encontrado para os projetos selecionados.
                </div>

                <div
                    v-for="doc in documents"
                    :key="doc.id"
                    class="d-flex items-center justify-between border rounded-lg px-4 py-3 mb-2"
                >
                    <span class="text-sm text-[#3b3b3c] font-medium">
                        {{ typeName(doc) }} do projeto: {{ doc.project.title_project == null ? doc.project.agent.name : doc.project.title_project }}
                    </span>

                    <div class="d-flex gap-1 flex-shrink-0 ml-3">
                        <v-btn icon size="small" variant="text" color="#008344" @click="download(doc)">
                            <v-icon size="20">mdi-download</v-icon>
                        </v-btn>
                        <v-btn icon size="small" variant="text" color="#008344" @click="view(doc)">
                            <v-icon size="20">mdi-eye-outline</v-icon>
                        </v-btn>
                    </div>
                </div>
            </v-card-text>

            <v-card-actions class="pa-4 pt-0">
                <v-spacer />
                <v-btn class="rounded-lg bg-secondary text-black"
                       @click="close">Fechar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>