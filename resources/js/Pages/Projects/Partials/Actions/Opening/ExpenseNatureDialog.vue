<script setup>
import { computed, ref } from 'vue';
import { useSnackbar } from '@/Composables/useSnackbar';
import { assignExpenseNature } from '@/Services/projectService';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    projectIds: { type: Array, default: () => [] },
    expenseNatures: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const { showSnackbar } = useSnackbar();

const expenseNature = ref(null);
const processing = ref(false);

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const closeDialog = () => {
    expenseNature.value = null;
    isOpen.value = false;
};

const save = () => {
    processing.value = true;

    assignExpenseNature(
        {
            selected_projects: props.projectIds,
            expense_nature: expenseNature.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showSnackbar(
                    props.projectIds.length === 1
                        ? 'Natureza da despesa atribuída ao projeto'
                        : 'Natureza da despesa atribuída aos projetos',
                    'success'
                );
                emit('saved');
                closeDialog();
            },
            onError: (errors) => {
                const message =
                    Object.values(errors).flat().join(', ') || 'Ocorreu um erro ao atribuir a natureza da despesa';
                showSnackbar(message, 'error');
            },
            onFinish: () => {
                processing.value = false;
            },
        }
    );
};
</script>

<template>
    <v-dialog v-model="isOpen" max-width="600" persistent>
        <v-card class="rounded-lg">
            <v-card-title>Atribua a natureza da despesa aos selecionados</v-card-title>
            <v-card-text class="space-y-4">
                <div>
                    <label for="expense-nature" class="block text-sm font-medium mb-2">Natureza da despesa</label>
                    <v-select
                        id="expense-nature"
                        v-model="expenseNature"
                        data-cy="expense-nature-dialog-select"
                        :items="expenseNatures"
                        item-title="label"
                        item-value="value"
                        placeholder="Selecione a natureza da despesa"
                        variant="outlined"
                    />
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn variant="outlined" color="#004c27" class="rounded-lg" :disabled="processing" @click="closeDialog"
                    >Cancelar</v-btn
                >
                <v-btn
                    data-cy="btnSalvarNaturezaDespesa"
                    class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg"
                    :disabled="!expenseNature"
                    :loading="processing"
                    @click="save"
                    >Salvar</v-btn
                >
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
