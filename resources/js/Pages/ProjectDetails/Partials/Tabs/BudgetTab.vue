<script setup>
import { computed, ref, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import SectionForm from '@/Components/SectionForm.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import DocumentViewerDialog from '@/Components/DocumentViewerDialog.vue';
import ReturnProcessAction from '@/Components/ReturnProcessAction.vue';
import FormField from '@/Components/FormField.vue';
import TextField from '@/Components/TextField.vue';
import TramitButton from '@/Pages/ProjectDetails/Partials/Tabs/Actions/TramitButton.vue';
import SaveButton from '@/Pages/ProjectDetails/Partials/Tabs/Actions/SaveButton.vue';

import { viewSections, formSections } from '@/Schemas/Budget';

import { useDate } from '@/Composables/useDate';
import { useSnackbar } from '@/Composables/useSnackbar';
import { useAlert } from '@/Composables/useAlert';
import { useStageAdvance } from '@/Composables/useStageAdvance';

const props = defineProps({
    project: { type: Object, required: true },
    canReturn: { type: Boolean, default: false },
    currentStage: { type: Object, default: null },
    canAdvance: { type: Boolean, default: false },
});

const { normalizeDate } = useDate();
const { showSnackbar } = useSnackbar();
const { showAlert } = useAlert();

const { canUserHandle: canUserHandleBudget } = useStageAdvance(props, 'orcamento');

const stage = computed(() => props.project.stages?.find((s) => s.slug === 'orcamento'));

const activeViewIndex = ref('all');
const activeEditIndex = ref('all');
const viewerOpen = ref(false);
const viewerDocument = ref(null);

const form = useForm({
    processing_date_for_codip: null,
    processing_date_for_coafi: null,
    installment_amount: null,
    installment_request_date: null,
    installment_justification: null,
    installment_observations: null,
    _method: null,
});

onMounted(() => {
    const budget = props.project.budgets || {};

    const currentInstallment = budget.installments?.find(
        (i) => i.installment_number === props.project.current_installment_cycle
    );

    form.processing_date_for_codip = normalizeDate(budget.processing_date_for_codip) ?? null;
    form.processing_date_for_coafi = normalizeDate(budget.processing_date_for_coafi) ?? null;
    form.installment_amount = currentInstallment?.amount ?? null;
    form.installment_request_date = normalizeDate(currentInstallment?.request_date) ?? null;
    form.installment_justification = currentInstallment?.justification ?? null;
    form.installment_observations = currentInstallment?.observations ?? null;
});

const budgetLocked = computed(() => {
    return props.project.current_installment_cycle > 1;
});

const budgetOpinionDocument = computed(
    () => props.project.documents?.find((document) => document.phase === 'budget' && document.type === 'po') ?? null
);

function downloadDocument(document) {
    window.open(`/projetos/documentos/${document.id}/download`, '_blank');
}

function viewDocument(document) {
    document.project = props.project;
    viewerDocument.value = document;
    viewerOpen.value = true;
}

const saveBudget = ({ showSuccess = true } = {}) => {
    return new Promise((resolve) => {
        const budget = props.project.budgets;

        const options = {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                form._method = null;

                if (showSuccess) {
                    showSnackbar('Orçamento salvo com sucesso!', 'success');
                }

                resolve(true);
            },
            onError: (errors) => {
                const details = Object.values(errors).flat().filter(Boolean).join(', ');

                const message = details
                    ? `Ocorreu um erro ao salvar o orçamento. ${details}`
                    : 'Ocorreu um erro ao salvar o orçamento.';

                showSnackbar(message, 'error');

                resolve(false);
            },
        };

        if (budget?.id) {
            form._method = 'patch';

            form.post(
                route('projects.budgets.update', {
                    project: props.project.id,
                    budget: budget.id,
                }),
                options
            );

            return;
        }

        form._method = null;

        form.post(
            route('projects.budgets.store', {
                project: props.project.id,
            }),
            options
        );
    });
};

const submit = () => {
    saveBudget();
};

const hasRequiredFields = computed(() => {
    return Boolean(
        form.processing_date_for_codip &&
        form.processing_date_for_coafi &&
        form.installment_amount &&
        form.installment_request_date &&
        form.installment_justification
    );
});

const hasBudgetOpinionDocument = computed(() => !!budgetOpinionDocument.value);

const canTramitBudget = computed(() => {
    return canUserHandleBudget.value && hasRequiredFields.value && hasBudgetOpinionDocument.value;
});

function showTramitBlockedMessage() {
    if (canTramitBudget.value) {
        return;
    }

    if (!hasRequiredFields.value) {
        showSnackbar('Preencha todos os campos obrigatórios antes de tramitar.', 'warning');

        return;
    }

    if (!hasBudgetOpinionDocument.value) {
        showSnackbar('O parecer orçamentário precisa ser gerado antes da tramitação.', 'warning');

        return;
    }

    if (!canUserHandleBudget.value) {
        showSnackbar('Usuário não tem permissão para tramitar orçamento.', 'warning');

        return;
    }

    showSnackbar('Este projeto não pode ser tramitado no momento.', 'warning');
}

const tramitLoading = ref(false);

const tramit = async () => {
    if (!stage.value?.id) {
        showSnackbar('Etapa de orçamento não encontrada.', 'error');
        return;
    }

    if (!canTramitBudget.value) {
        showTramitBlockedMessage();
        return;
    }

    tramitLoading.value = true;

    const saved = await saveBudget({ showSuccess: false });

    if (!saved) {
        tramitLoading.value = false;
        return;
    }

    router.patch(
        route('projects.stages.advance', {
            project: props.project.id,
            stage: stage.value.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showAlert({
                    alertTitle: 'Tarefa marcada como tramitada',
                    alertMessage:
                        'As informações foram validadas e as pessoas envolvidas nesse processo foram notificadas.',
                    confirmText: 'Entendi',
                    action: () => {
                        router.visit(window.location.pathname, {
                            preserveState: false,
                            preserveScroll: true,
                        });
                    },
                });
            },
            onError: (errors) => {
                const message = Object.values(errors).flat().join(', ') || 'Erro ao tramitar projeto';
                showSnackbar(message, 'error');
            },
            onFinish: () => {
                tramitLoading.value = false;
            },
        }
    );
};

const permissionMessage = computed(() => {
    if (!canUserHandleBudget.value) {
        return 'Usuário não tem permissão para fazer alterações no Orçamento.';
    }

    if (stage.value?.status === 'bloqueado') {
        return 'Este projeto está bloqueado e não pode receber alterações no momento.';
    }

    return 'Projeto já foi tramitado e não está mais na fase de Orçamento.';
});
</script>

<template>
    <SplitScreenTab value="budget">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <div class="font-bold text-lg d-flex justify-between">
                        <span>Dados disponíveis para consulta</span>

                        <ReturnProcessAction
                            :project="project"
                            :current-stage="currentStage"
                            :can-return="canReturn"
                            stage-slug="orcamento"
                            :can-user-handle="canUserHandleBudget"
                        />
                    </div>

                    <p class="text-sm text-gray-600">Utilize os filtros abaixo para navegar entre os dados</p>
                </div>

                <SectionChips v-model="activeViewIndex" :sections="viewSections" show-all-option />

                <div class="space-y-8">
                    <template v-for="(section, index) in viewSections" :key="'view-' + section.title">
                        <SectionContent
                            v-if="activeViewIndex === 'all' || activeViewIndex === index"
                            :section="section"
                            :project="project"
                        />
                    </template>
                </div>
            </div>
        </template>

        <template #right-content>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-bold text-lg">Campos para você inserir ou editar dados</p>

                        <p class="font-bold text-md mt-2 text-black">Links auxiliares</p>
                    </div>

                    <SaveButton :loading="form.processing" :can-save="canUserHandleBudget" @click="submit" />
                </div>

                <AuxLinks />

                <SectionChips v-model="activeEditIndex" :sections="formSections" />

                <div
                    v-permission="{
                        condition:
                            !canUserHandleBudget || (stage?.status !== 'aprovado' && stage?.status !== 'bloqueado'),
                        message: permissionMessage,
                    }"
                    class="mt-4"
                >
                    <SectionForm :active-edit-index="activeEditIndex" :sections="formSections">
                        <template #default="{ section }">
                            <template v-if="section.key === 'dates'">
                                <div class="grid grid-cols-2 gap-4">
                                    <FormField label="Data de tramitação para a CODIP">
                                        <TextField
                                            v-model="form.processing_date_for_codip"
                                            type="date"
                                            :disabled="budgetLocked"
                                        />
                                    </FormField>

                                    <FormField label="Data de tramitação para a Coafi">
                                        <TextField
                                            v-model="form.processing_date_for_coafi"
                                            type="date"
                                            :disabled="budgetLocked"
                                        />
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'budget_report'">
                                <DocumentViewerDialog v-model="viewerOpen" :document="viewerDocument" />

                                <div v-if="budgetOpinionDocument" class="grid grid-cols-2 gap-4">
                                    <FormField label="Parecer orçamentário">
                                        <div class="d-flex items-center justify-between border rounded-lg px-4 py-2">
                                            <span class="text-sm text-[#3b3b3c] font-medium truncate">
                                                {{
                                                    budgetOpinionDocument.type_name ??
                                                    budgetOpinionDocument.type_label ??
                                                    budgetOpinionDocument.type
                                                }}
                                            </span>

                                            <div class="d-flex gap-1 flex-shrink-0 ml-3">
                                                <v-btn
                                                    icon
                                                    size="small"
                                                    variant="text"
                                                    color="#008344"
                                                    @click="downloadDocument(budgetOpinionDocument)"
                                                >
                                                    <v-icon size="20"> mdi-download </v-icon>
                                                </v-btn>

                                                <v-btn
                                                    icon
                                                    size="small"
                                                    variant="text"
                                                    color="#008344"
                                                    @click="viewDocument(budgetOpinionDocument)"
                                                >
                                                    <v-icon size="20"> mdi-eye-outline </v-icon>
                                                </v-btn>
                                            </div>
                                        </div>
                                    </FormField>

                                    <FormField label="Data do parecer orçamentário">
                                        <TextField
                                            :model-value="normalizeDate(budgetOpinionDocument.created_at)"
                                            type="date"
                                            disabled
                                        />
                                    </FormField>
                                </div>

                                <div v-else class="grid grid-cols-2 gap-4">
                                    <FormField label="Parecer orçamentário">
                                        <div class="border rounded-lg px-4 py-3 mt-2 flex items-center justify-between">
                                            <span class="text-sm font-bold"> O parecer ainda não foi gerado </span>

                                            <v-icon color="warning" size="20"> mdi-alert-circle </v-icon>
                                        </div>
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'installments'">
                                <div class="grid grid-cols-2 gap-4">
                                    <FormField
                                        :label="`Valor da ${project.current_installment_cycle}ª parcela`"
                                        :error="form.errors.installment_amount"
                                    >
                                        <TextField v-model="form.installment_amount" money />
                                    </FormField>

                                    <FormField label="Data de solicitação da parcela">
                                        <TextField v-model="form.installment_request_date" type="date" />
                                    </FormField>

                                    <FormField label="Justificativa da parcela">
                                        <v-textarea
                                            v-model="form.installment_justification"
                                            label="Descreva a justificativa"
                                            rows="2"
                                            no-resize
                                            variant="outlined"
                                            class="mt-2"
                                        />
                                    </FormField>

                                    <FormField label="Observações sobre parcela">
                                        <v-textarea
                                            v-model="form.installment_observations"
                                            label="Adicione observações sobre a parcela"
                                            rows="2"
                                            no-resize
                                            variant="outlined"
                                            class="mt-2"
                                        />
                                    </FormField>
                                </div>
                            </template>
                        </template>
                    </SectionForm>

                    <div :class="{ 'cursor-not-allowed': !canTramitBudget }" @click="showTramitBlockedMessage">
                        <TramitButton
                            :action="tramit"
                            :disabled="!canTramitBudget"
                            :loading="tramitLoading"
                            :class="{ 'pointer-events-none': !canTramitBudget }"
                        />
                    </div>
                </div>
            </div>
        </template>
    </SplitScreenTab>
</template>
