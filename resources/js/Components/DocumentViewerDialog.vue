<script setup>
import { computed } from 'vue';
import SanitizedHtml from '@/Components/SanitizedHtml.vue';
import { getBudgetAllocation } from '@/Schemas/getBudgetAllocation';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    document: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const title = computed(() => {
    return (
        props.document?.project?.title_project ??
        props.document?.project?.agent?.name ??
        props.document?.notice?.name ??
        'documento'
    );
});

const contextLabel = computed(() => (props.document?.project ? 'Documento do projeto' : 'Documento do edital'));

const resolvedBody = computed(() => {
    if (props.document?.resolved_body != null) {
        return props.document.resolved_body;
    }

    const body = props.document?.body;
    const project = props.document?.project;
    if (!body || !project) return body ?? '';
    const budgetAllocation = getBudgetAllocation(project);

    const replacements = {
        '[notice_name]': project.notice?.name ?? '',
        '[nup_mother]': project.notice?.nup ?? '',
        '[agent_name]': project.agent?.name ?? '',
        '[finality]': project.notice?.instrument_type ?? '',
        '[fiscal_matricula]': project.opening?.active_supervisor?.user?.registration_number ?? 'sem matrícula',
        '[fiscal_name]': project.opening?.active_supervisor?.user?.name ?? 'sem fiscal',
        '[project_name]': project.title_project ?? 'sem projeto',
        '[allocation_code]': budgetAllocation?.allocation_code ?? 'sem dotação orçamentária',
        '[allocation_number]': budgetAllocation?.allocation_number ?? 'sem dotação orçamentária',
    };

    return Object.entries(replacements).reduce(
        (text, [placeholder, value]) => text.replaceAll(placeholder, value),
        body
    );
});

function download(format) {
    window.open(`/projetos/documentos/${props.document.id}/download?format=${format}`, '_blank');
}

function close() {
    isOpen.value = false;
}
</script>

<template>
    <v-dialog v-model="isOpen" max-width="720" persistent scrollable>
        <v-card class="rounded-lg d-flex flex-column" max-height="85vh">
            <v-card-title class="d-flex justify-space-between items-center pa-4 pb-2 flex-shrink-0">
                <span class="font-weight-bold text-sm">
                    {{ contextLabel }}: {{ title.length > 50 ? title.substr(0, 49) + '...' : title }}
                </span>
                <v-btn icon size="small" variant="text" @click="close">
                    <v-icon size="18">mdi-close</v-icon>
                </v-btn>
            </v-card-title>
            <v-divider />

            <v-card-text class="flex-grow-1 overflow-y-auto pa-6">
                <div v-if="resolvedBody" class="document-preview text-sm text-[#3b3b3c] leading-relaxed">
                    <SanitizedHtml :content="resolvedBody" />
                </div>
                <div v-else class="text-center text-gray-400 py-6">Sem conteúdo disponível.</div>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-4 flex-shrink-0">
                <v-spacer />
                <v-menu location="top end">
                    <template #activator="{ props: menuProps }">
                        <v-btn v-bind="menuProps" variant="outlined" color="primary" class="rounded-lg text-xs">
                            Baixar
                            <v-icon end size="16">mdi-chevron-down</v-icon>
                        </v-btn>
                    </template>

                    <v-list density="compact">
                        <v-list-item title="Baixar PDF" prepend-icon="mdi-file-pdf-box" @click="download('pdf')" />
                        <v-list-item title="Baixar DOCX" prepend-icon="mdi-file-word-box" @click="download('docx')" />
                        <v-list-item
                            title="Baixar DOCX Casa Civil"
                            prepend-icon="mdi-file-word-box"
                            @click="download('docx_casa_civil')"
                        />
                    </v-list>
                </v-menu>
                <v-btn class="rounded-lg bg-secondary text-black mr-2" @click="close"> Fechar </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.document-preview {
    max-width: 100%;
    overflow-wrap: anywhere;
}

.document-preview :deep(p) {
    margin: 0 0 0.75rem;
}

.document-preview :deep(ul),
.document-preview :deep(ol) {
    margin: 0 0 0.75rem;
    padding-left: 1.5rem;
}

.document-preview :deep(table) {
    width: 100% !important;
    max-width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.document-preview :deep(th),
.document-preview :deep(td) {
    min-width: 0;
    padding: 0.375rem 0.5rem;
    border: 1px solid #d1d5db;
    vertical-align: top;
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.document-preview :deep(th) {
    background-color: #e6f1e3;
    font-weight: 700;
}

.document-preview :deep(img) {
    max-width: 100%;
    height: auto;
}

.document-preview :deep(pre) {
    max-width: 100%;
    overflow-x: auto;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}
</style>
