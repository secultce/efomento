<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useSnackbar } from '@/Composables/useSnackbar'
import AppTextEditor from '@/Components/AppTextEditor.vue'
import { saveCI } from '@/Services/documentService'

function insertPlaceholder(value) {
    window.tinymce?.activeEditor?.insertContent(value)
}

const { showSnackbar } = useSnackbar()

const props = defineProps({
    modelValue: Boolean,
    projectIds: Array,
    editData: Object,
    placeholders: {
        type: Array,
        default: () => [
            { label: 'Nome do edital',      value: '[notice_name]'      },
            { label: 'Nup Mãe',             value: '[nup_mother]'       },
            { label: 'Nome do Agente',      value: '[agent_name]'       },
            { label: 'Finalidade',          value: '[finality]'         },
            { label: 'Matrícula do Fiscal', value: '[fiscal_matricula]' },
            { label: 'Nome do Fiscal',      value: '[fiscal_name]'      },
            { label: 'Nome do projeto',     value: '[project_name]'     },
        ],
    },
})

const form = useForm({
    content: '',
})

const emit = defineEmits(['update:modelValue'])

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
})

const handleCI = () => {
    saveCI({
        selected_projects: props.projectIds,
        content: form.content,
    }, {
        onSuccess: () => {
            showSnackbar('Comunicação interna criada com sucesso!', 'success')
            closeDialog()
        },
        onError: (errors) => {
            const message =
                Object.values(errors).flat().join(', ') ||
                'Ocorreu um erro ao criar a comunicação interna'
            showSnackbar(message, 'error')
        }
    })
}

watch(
    () => isOpen.value,
    (open) => {
        if (open) {
            if (props.editData?.content) {
                form.content = props.editData.content
            } else {
                form.reset()
            }
        }
    }
)

const closeDialog = () => {
    form.reset()
    form.clearErrors()
    isOpen.value = false
    emit('update:modelValue', false)
}
</script>

<template>
    <v-dialog v-model="isOpen" max-width="805" :retain-focus="false" persistent>
        <v-card class="rounded-lg d-flex flex-column" height="654">
            <v-card-title class="font-weight-bold flex-shrink-0">Crie um documento de comunicação interna
                (CI)</v-card-title>
            <v-container class="flex-grow-1 d-flex flex-column pa-4 min-h-0">
                <div class="flex flex-wrap gap-2 mb-3">
                <v-chip
                    v-for="p in placeholders"
                    :key="p.value"
                    size="small"
                    color="primary"
                    variant="outlined"
                    class="cursor-pointer"
                    @click="insertPlaceholder(p.value)"
                >
                    {{ p.label }}
                </v-chip>
            </div>

            <app-text-editor v-model="form.content" label="" :error="form.errors.content" class="flex-grow-1" />
                <v-card-actions class="flex-shrink-0">
                    <v-spacer></v-spacer>
                    <v-btn variant="outlined" color="#004c27" class="rounded-lg" @click="closeDialog">Cancelar</v-btn>
                    <v-btn class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg"
                        :loading="form.processing" :disabled="!form?.content.trim()" @click="handleCI">Salvar</v-btn>
                </v-card-actions>
            </v-container>
        </v-card>
    </v-dialog>
</template>
