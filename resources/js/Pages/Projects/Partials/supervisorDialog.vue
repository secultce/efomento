<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useSnackbar } from '@/Composables/useSnackbar'

const props = defineProps({
    projectIds: Array,
    supervisors: Array,
})

const emit = defineEmits(['update:modelValue'])

const { showSnackbar } = useSnackbar()

const isOpen = ref(false)

const closeDialog = () => {
    form.value = { titularSupervisor: null, supleteSupervisor: null }
    isOpen.value = false
    emit('update:modelValue', false)
}

const form = ref({
    titularSupervisor: null,
    supleteSupervisor: null,
})

const availableTitulars = computed(() => {
    return props.supervisors.filter(
        s => s.id !== form.value.supleteSupervisor
    )
})

const availableSuplentes = computed(() => {
    return props.supervisors.filter(
        s => s.id !== form.value.titularSupervisor
    )
})

const saveSupervisors = () => {
    router.post('/projetos/atribuir-fiscal', {
        selected_projects: props.projectIds,
        selected_supervisors: [
            form.value.titularSupervisor,
            form.value.supleteSupervisor,
        ].filter(Boolean),
    }, {
        onSuccess: () => {
            if (props.projectIds.length === 1) {
                showSnackbar('Sucesso ao atribuir fiscal ao projeto', 'success')
            } else {
                showSnackbar('Sucesso ao atribuir fiscais aos projetos', 'success')
            }
            emit('saved')
        },
        onError: (errors) => {
            const message =
                Object.values(errors).flat().join(', ') ||
                'Ocorreu um erro ao salvar o processo'
            showSnackbar(message, 'error')
        }
    })
    closeDialog()
}

defineExpose({ isOpen })
</script>

<template>
    <v-dialog v-model="isOpen" max-width="600" persistent>
        <v-card class="rounded-lg">
            <v-card-title>Atribua um fiscal aos selecionados</v-card-title>
            <v-card-text class="space-y-4">
                <div>
                    <label for="titular" class="block text-sm font-medium mb-2">Fiscal titular</label>
                    <v-autocomplete v-model="form.titularSupervisor" :items="availableTitulars"
                        placeholder="Selecione um usuário" item-title="name" item-value="id"  
                    />
                </div>

                <div>
                    <label for="suplente" class="block text-sm font-medium mb-2">Fiscal Suplente</label>
                    <v-autocomplete v-model="form.supleteSupervisor" :items="availableSuplentes" item-title="name"
                        item-value="id" placeholder="Selecione um usuário" class="w-full" />
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn variant="outlined"  color="#004c27" class="rounbed-lg" @click="closeDialog">Cancelar</v-btn>
                <v-btn class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg" :disabled="!form.titularSupervisor" @click="saveSupervisors">Salvar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
