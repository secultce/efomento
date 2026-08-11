<script setup>
import axios from 'axios';
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import BudgetAllocationImportDialog from '@/Pages/Projects/Partials/Actions/BudgetAllocationImportDialog.vue';
import { computed, ref, watch } from 'vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useSnackbar } from '@/Composables/useSnackbar';

const props = defineProps({
    notice: {
        type: Object,
        default: null,
    },
    hasBudgetAllocations: Boolean,
});

const viewHistory = ref(false);
const budgetAllocationDialog = ref(false);
const budgetAllocationInput = ref(null);
const importingBudgetAllocations = ref(false);
const budgetAllocationColumns = ref([]);
const budgetAllocationRows = ref([]);
const selectedBudgetAllocationFile = ref(null);
const hasExistingBudgetAllocations = ref(props.hasBudgetAllocations);

const noticeId = computed(() => props.notice?.id ?? null);
const { canManageBudget } = usePermissions();
const { showSnackbar } = useSnackbar();

watch(
    () => props.hasBudgetAllocations,
    (hasAllocations) => {
        hasExistingBudgetAllocations.value = hasAllocations;
    }
);

function openBudgetAllocationUpload() {
    if (!noticeId.value || !canManageBudget.value) {
        return;
    }

    budgetAllocationInput.value?.click();
}

async function handleBudgetAllocationUpload(event) {
    const file = event.target.files?.[0];

    if (!file || !noticeId.value || !canManageBudget.value) {
        event.target.value = '';

        return;
    }

    const formData = new FormData();
    formData.append('file', file);
    importingBudgetAllocations.value = true;

    try {
        const { data } = await axios.post(route('budget-allocations.preview', { notice: noticeId.value }), formData, {
            headers: {
                Accept: 'application/json',
            },
        });

        budgetAllocationColumns.value = data.columns ?? [];
        budgetAllocationRows.value = data.rows ?? [];
        selectedBudgetAllocationFile.value = file;
        hasExistingBudgetAllocations.value = Boolean(data.has_existing_allocations);
        budgetAllocationDialog.value = true;
    } catch (error) {
        selectedBudgetAllocationFile.value = null;
        const errors = error.response?.data?.errors;
        const message = errors
            ? Object.values(errors).flat().join(', ')
            : (error.response?.data?.message ?? 'Ocorreu um erro ao importar o arquivo CSV.');

        showSnackbar(message, 'error');
    } finally {
        importingBudgetAllocations.value = false;
        event.target.value = '';
    }
}

async function confirmBudgetAllocationImport() {
    if (!selectedBudgetAllocationFile.value || !noticeId.value || !canManageBudget.value) {
        return;
    }

    const formData = new FormData();
    formData.append('file', selectedBudgetAllocationFile.value);
    importingBudgetAllocations.value = true;

    try {
        const { data } = await axios.post(route('budget-allocations.import', { notice: noticeId.value }), formData, {
            headers: {
                Accept: 'application/json',
            },
        });

        const imported = Number(data.summary?.created ?? 0) + Number(data.summary?.updated ?? 0);
        const allocationMessage = imported === 1 ? 'vinculação processada' : 'vinculações processadas';

        showSnackbar(`Importação concluída. ${imported} ${allocationMessage}.`, 'success');
        budgetAllocationDialog.value = false;
        selectedBudgetAllocationFile.value = null;
        hasExistingBudgetAllocations.value = true;
    } catch (error) {
        const errors = error.response?.data?.errors;
        const message = errors
            ? Object.values(errors).flat().join(', ')
            : (error.response?.data?.message ?? 'Ocorreu um erro ao importar o arquivo CSV.');

        showSnackbar(message, 'error');
    } finally {
        importingBudgetAllocations.value = false;
    }
}

function openNoticeHistory() {
    if (!noticeId.value) {
        return;
    }

    viewHistory.value = true;
}
</script>

<template>
    <input
        ref="budgetAllocationInput"
        type="file"
        accept=".csv,text/csv"
        class="hidden"
        @change="handleBudgetAllocationUpload"
    />

    <v-card class="w-full rounded-lg border border-gray-800 pb-4 pt-4 !shadow-none">
        <v-card-title class="font-weight-bold !text-lg"> Ações disponíveis para você </v-card-title>

        <v-card-text class="flex flex-col gap-4">
            <div class="flex w-full flex-col gap-6 pt-2">
                <div
                    v-permission="{
                        condition: canManageBudget,
                        message:
                            'Você não tem permissão para importar informações orçamentárias, contate o administrador do sistema.',
                    }"
                    class="flex flex-col gap-2"
                >
                    <p>Informe os dados da vinculação orçamentária</p>

                    <template v-if="hasExistingBudgetAllocations">
                        <v-btn
                            variant="outlined"
                            class="w-full !shadow-none !font-bold !border-gray-300 !bg-white !text-[#2d353fFF] rounded-lg text-xs gap-6"
                            :disabled="!noticeId || !canManageBudget"
                            :loading="importingBudgetAllocations"
                            @click="openBudgetAllocationUpload"
                        >
                            <span class="w-full text-left">Realizar novo upload</span>

                            <template #append>
                                <v-icon size="18"> mdi-pencil </v-icon>
                            </template>
                        </v-btn>
                    </template>

                    <template v-else>
                        <v-btn
                            class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                            :disabled="!noticeId || !canManageBudget"
                            :loading="importingBudgetAllocations"
                            @click="openBudgetAllocationUpload"
                        >
                            Upload das informações orçamentária
                        </v-btn>
                    </template>
                </div>

                <div class="flex flex-col gap-2">
                    <p>Parecer orçamentário inicial</p>

                    <v-btn
                        class="w-full rounded-lg px-4 py-2 text-xs !bg-[#ffcc05FF] !font-bold !text-[#2d353fFF] !shadow-none"
                    >
                        Criar parecer orçamentário inicial
                    </v-btn>
                </div>

                <div class="flex flex-col gap-2">
                    <p>Parecer orçamentário do resultado</p>

                    <v-btn
                        class="w-full rounded-lg px-4 py-2 text-xs !bg-[#ffcc05FF] !font-bold !text-[#2d353fFF] !shadow-none"
                    >
                        Criar parecer orçamentário resultado
                    </v-btn>
                </div>
            </div>

            <div class="flex w-full flex-col gap-1">
                <v-divider class="my-4" />

                <p>Conferir histórico de alterações nos processos</p>

                <v-btn
                    variant="outlined"
                    color="outlineSecondary"
                    class="w-full rounded-lg"
                    :disabled="!noticeId"
                    @click="openNoticeHistory"
                >
                    Conferir histórico
                </v-btn>
            </div>
        </v-card-text>

        <NoticeHistoryDialog v-if="noticeId" v-model="viewHistory" :notice-id="noticeId" />
    </v-card>

    <BudgetAllocationImportDialog
        v-model="budgetAllocationDialog"
        :columns="budgetAllocationColumns"
        :rows="budgetAllocationRows"
        :loading="importingBudgetAllocations"
        :can-confirm="Boolean(selectedBudgetAllocationFile)"
        :has-existing-allocations="hasExistingBudgetAllocations"
        @confirm="confirmBudgetAllocationImport"
    />
</template>
