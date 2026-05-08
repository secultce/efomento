<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: Boolean,
    document: Object,
})

const emit = defineEmits(['update:modelValue'])

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
})

const title = computed(() =>
    props.document?.project?.title_project
    ?? props.document?.project?.agent?.name
    ?? 'documento'
)

const resolvedBody = computed(() => {
    const body = props.document?.body
    const project = props.document?.project
    if (!body || !project) return body ?? ''

    const replacements = {
        '[notice_name]':      project.notice?.name ?? '',
        '[nup_mother]':       project.notice?.nup ?? '',
        '[agent_name]':       project.agent?.name ?? '',
        '[finality]':         project.notice?.instrument_type ?? '',
        '[fiscal_matricula]': project.opening?.active_supervisor?.user?.registration_number ?? 'sem matrícula',
        '[fiscal_name]':      project.opening?.active_supervisor?.user?.name ?? 'sem fiscal',
        '[project_name]':     project.title_project ?? 'sem projeto',
    }

    return Object.entries(replacements).reduce(
        (text, [placeholder, value]) => text.replaceAll(placeholder, value),
        body
    )
})

function download() {
    window.open(`/projetos/documentos/${props.document.id}/download`, '_blank')
}

function close() {
    isOpen.value = false
}
</script>

<template>
    <v-dialog v-model="isOpen" max-width="720" persistent scrollable>
        <v-card class="rounded-lg d-flex flex-column" max-height="85vh">
            <v-card-title class="d-flex justify-space-between items-center pa-4 pb-2 flex-shrink-0">
                <span class="font-weight-bold text-sm">
                    Documento do projeto: {{ title.length > 50 ? title.substr(0, 49)+'...' : title }}
                </span>
                    <v-btn icon size="small" variant="text" @click="close">
                        <v-icon size="18">mdi-close</v-icon>
                    </v-btn>
            </v-card-title>
            <v-divider />

            <v-card-text class="flex-grow-1 overflow-y-auto pa-6">
                <div
                    v-if="resolvedBody"
                    v-html="resolvedBody"
                    class="text-sm text-[#3b3b3c] leading-relaxed"
                />
                <div v-else class="text-center text-gray-400 py-6">
                    Sem conteúdo disponível.
                </div>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-4 flex-shrink-0">
                <v-spacer />
                <v-btn
                    variant="outlined"
                    color="primary"
                    class="rounded-lg text-xs"
                    @click="download">
                    Baixar
                </v-btn>
                <v-btn class="rounded-lg bg-secondary text-black mr-2" @click="close">
                    Fechar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
