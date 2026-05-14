<script setup>
import { computed } from 'vue';
import { useSnackbar } from '@/Composables/useSnackbar';
import { assignSupervisor } from '@/Services/projectService';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    projectIds: { type: Array, default: () => [] },
    supervisors: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const { showSnackbar } = useSnackbar();

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const closeDialog = () => {
    form.reset();
    isOpen.value = false;
    emit('update:modelValue', false);
};

const form = useForm({
    titularSupervisor: null,
    suplenteSupervisor: null,
});

const availableTitulars = computed(() => {
    return props.supervisors.filter((s) => s.id !== form.suplenteSupervisor);
});

const availableSuplentes = computed(() => {
    return props.supervisors.filter((s) => s.id !== form.titularSupervisor);
});

const saveSupervisors = () => {
    assignSupervisor(
        {
            selected_projects: props.projectIds,
            selected_supervisors: [form.titularSupervisor, form.suplenteSupervisor].filter(Boolean),
        },
        {
            onSuccess: () => {
                if (props.projectIds.length === 1) {
                    showSnackbar('Sucesso ao atribuir fiscal ao projeto', 'success');
                } else {
                    showSnackbar('Sucesso ao atribuir fiscais aos projetos', 'success');
                }
                emit('saved');
            },
            onError: (errors) => {
                const message = Object.values(errors).flat().join(', ') || 'Ocorreu um erro ao salvar o processo';
                showSnackbar(message, 'error');
            },
        }
    );
    closeDialog();
};

defineExpose({ isOpen });
</script>

<template>
    <v-dialog v-model="isOpen" max-width="600" persistent>
        <v-card class="rounded-lg">
            <v-card-title>Atribua um fiscal aos selecionados</v-card-title>
            <v-card-text class="space-y-4">
                <div>
                    <label for="titular" class="block text-sm font-medium mb-2">Fiscal titular</label>
                    <v-autocomplete
                        v-model="form.titularSupervisor"
                        :items="availableTitulars"
                        placeholder="Selecione um usuário"
                        item-title="name"
                        item-value="id"
                    />
                </div>

                <div>
                    <label for="suplente" class="block text-sm font-medium mb-2">Fiscal Suplente</label>
                    <v-autocomplete
                        v-model="form.suplenteSupervisor"
                        :items="availableSuplentes"
                        item-title="name"
                        item-value="id"
                        placeholder="Selecione um usuário"
                        class="w-full"
                    />
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn variant="outlined" color="#004c27" class="rounbed-lg" @click="closeDialog">Cancelar</v-btn>
                <v-btn
                    class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg"
                    :disabled="!form.titularSupervisor"
                    :loading="form.processing"
                    @click="saveSupervisors"
                    >Salvar</v-btn
                >
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
