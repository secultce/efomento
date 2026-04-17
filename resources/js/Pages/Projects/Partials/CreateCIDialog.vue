<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useSnackbar } from '@/Composables/useSnackbar'
import AppTextEditor from '@/Components/AppTextEditor.vue'
import { createCI } from '@/Services/documentService'

const { showSnackbar } = useSnackbar()

const props = defineProps({
    modelValue: Boolean,
    projectIds: Array,
})

const form = useForm({
    content: '',
})

const emit = defineEmits(['update:modelValue'])

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
})

const saveCI = () => {
    createCI({
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

const closeDialog = () => {
    form.reset()
    isOpen.value = false
    emit('update:modelValue', false)
}
</script>

<template>
    <v-dialog v-model="isOpen" max-width="805" :retain-focus="false" persistent>
        <v-card class="rounded-lg d-flex flex-column" height="654">
            <v-card-title class="font-weight-bold flex-shrink-0">Crie um documento de comunição interna
                (CI)</v-card-title>
            <v-container class="flex-grow-1 d-flex flex-column pa-4 min-h-0">
                <app-text-editor v-model="form.content" label="" :error="form.errors.content" class="flex-grow-1" />
                <v-card-actions class="flex-shrink-0">
                    <v-spacer></v-spacer>
                    <v-btn variant="outlined" color="#004c27" class="rounbed-lg" @click="closeDialog">Cancelar</v-btn>
                    <v-btn class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg"
                        :loading="form.processing" :disabled="!form?.content.trim()" @click="saveCI">Salvar</v-btn>
                </v-card-actions>
            </v-container>
        </v-card>
    </v-dialog>
</template>
