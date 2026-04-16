<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { useSnackbar } from '@/Composables/useSnackbar'
import AppTextEditor from '@/Components/AppTextEditor.vue'


const { showSnackbar } = useSnackbar()

const props = defineProps({
    modelValue: Boolean,
    projectIds: Array,
})

const form = useForm({
    description: '',
})
const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)

watch(() => props.modelValue, (val) => {
    isOpen.value = val
})

const closeDialog = () => {
    form.reset()
    isOpen.value = false
    emit('update:modelValue', false)
}
</script>

<template>
    <v-dialog v-model="isOpen" max-width="805" :retain-focus="false" persistent>
          <v-card class="rounded-lg d-flex flex-column" height="654">
            <v-card-title class="font-weight-bold flex-shrink-0">Crie um documento de comunição interna (CI)</v-card-title>
            <v-container class="flex-grow-1 d-flex flex-column pa-4 min-h-0">
                <app-text-editor
                    v-model="form.description"
                    label=""
                    :error="form.errors.description"
                    class="flex-grow-1"
                />
                <v-card-actions class="flex-shrink-0">
                    <v-spacer></v-spacer>
                    <v-btn variant="outlined"  color="#004c27" class="rounbed-lg" @click="closeDialog">Cancelar</v-btn>
                    <v-btn class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg" :disabled="true" @click="closeDialog">Salvar</v-btn>
                </v-card-actions>
            </v-container>
        </v-card>
    </v-dialog>
</template>
