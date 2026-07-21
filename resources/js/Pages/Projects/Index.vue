<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';

import AppContainer from '@/Components/AppContainer.vue';
import AppSubHeader from '@/Components/AppSubHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import ProjectList from '@/Pages/Projects/Partials/ProjectList.vue';
import PhaseFilter from '@/Pages/Projects/Partials/PhaseFilter.vue';
import ProjectNoticeEdit from '@/Pages/Projects/Partials/ProjectNoticeEdit.vue';

import OpeningActions from '@/Pages/Projects/Partials/Actions/Opening/OpeningActions.vue';
import FormalizationActions from './Partials/Actions/Formalization/FormalizationActions.vue';
import BudgetActions from './Partials/Actions/Budget/BudgetActions.vue';
import PaymentActions from './Partials/Actions/Payment/PaymentActions.vue';
import MonitoringActions from './Partials/Actions/Monitoring/MonitoringActions.vue';

import { useSnackbar } from '@/Composables/useSnackbar';
import { useInstallmentStatus } from '@/Composables/useInstallments';
import NoStageSelected from './Partials/Actions/noStageSelected.vue';

const props = defineProps({
    notice: { type: Object, default: null },
    projects: { type: Array, default: () => [] },
    filters: { type: Object, default: null },
    phases: { type: Array, default: () => [] },
    instrumentTypes: { type: Array, default: () => [] },
    supervisorsAvailable: { type: Array, default: () => [] },
    monitoringReportsCount: { type: Number, default: 0 },
});

const { showSnackbar } = useSnackbar();

const { countPaidInstallments } = useInstallmentStatus();

const search = ref(props.filters?.search ?? '');
const selectedPhase = ref(props.filters?.phase ?? null);
const selectedProjects = ref([]);

function selectPhase(phase) {
    selectedProjects.value = [];
    selectedPhase.value = phase.value;

    router.get(
        route('notices.projects', props.notice.id),
        {
            phase: selectedPhase.value,
            search: search.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

function onSearch(value) {
    search.value = value;

    router.get(
        route('notices.projects', props.notice.id),
        {
            phase: selectedPhase.value,
            search: value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

function clearPhaseFilter() {
    selectedProjects.value = [];
    selectedPhase.value = null;

    router.get(
        route('notices.projects', props.notice.id),
        {
            search: search.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

const reference = (item) => ({
    label: 'Nome do Proponente',
    value: item.agent?.name ?? '-',
});

function getChipColor(status) {
    const colors = {
        draft: 'gray',
        pending_signature: 'yellow',
        signed: 'green',
    };

    return colors[status] ?? 'default';
}

const chips = (item) => {
    if (!Array.isArray(item.documents)) return [];

    const uniqueDocuments = Object.values(
        item.documents.reduce((accumulator, document) => {
            accumulator[document.type] = document;

            return accumulator;
        }, {})
    );

    return uniqueDocuments.map((document) => ({
        label: document.type_label,
        color: getChipColor(document.status),
    }));
};

function resolveCollection(value) {
    if (Array.isArray(value)) {
        return value;
    }

    if (Array.isArray(value?.data)) {
        return value.data;
    }

    return [];
}

function getProjectInstallments(item) {
    const directInstallments = resolveCollection(item?.installments);

    if (directInstallments.length > 0) {
        return directInstallments;
    }

    const singularBudgetInstallments = resolveCollection(item?.budget?.installments);

    if (singularBudgetInstallments.length > 0) {
        return singularBudgetInstallments;
    }

    const budgetObjectInstallments = resolveCollection(item?.budgets?.installments);

    if (budgetObjectInstallments.length > 0) {
        return budgetObjectInstallments;
    }

    const budgets = resolveCollection(item?.budgets);

    if (budgets.length > 0) {
        return budgets.flatMap((budget) => {
            return resolveCollection(budget?.installments);
        });
    }

    const resourceBudgetInstallments = resolveCollection(item?.data?.budget?.installments);

    if (resourceBudgetInstallments.length > 0) {
        return resourceBudgetInstallments;
    }

    const resourceBudgetsObjectInstallments = resolveCollection(item?.data?.budgets?.installments);

    if (resourceBudgetsObjectInstallments.length > 0) {
        return resourceBudgetsObjectInstallments;
    }

    const resourceBudgets = resolveCollection(item?.data?.budgets);

    if (resourceBudgets.length > 0) {
        return resourceBudgets.flatMap((budget) => {
            return resolveCollection(budget?.installments);
        });
    }

    return [];
}

function getPaidInstallmentsCount(item) {
    if (item?.paid_installments_count !== null && item?.paid_installments_count !== undefined) {
        const count = Number(item.paid_installments_count);

        return Number.isFinite(count) ? count : 0;
    }

    const installments = getProjectInstallments(item);

    return countPaidInstallments(installments);
}

function getTotalInstallments(item) {
    const total = Number(
        item?.notice?.installments ??
            item?.opening?.notice?.installments ??
            item?.notice_installments ??
            props.notice?.installments ??
            item?.total_installments ??
            item?.installments_count ??
            getProjectInstallments(item).length ??
            0
    );

    return Number.isFinite(total) && total > 0 ? total : 0;
}

function getInstallmentReference(item) {
    const paidInstallments = getPaidInstallmentsCount(item);

    const totalInstallments = getTotalInstallments(item);

    return `${paidInstallments} de ${totalInstallments}`;
}

function getOrdinalInstallment(number) {
    const ordinals = {
        1: 'Primeira',
        2: 'Segunda',
        3: 'Terceira',
        4: 'Quarta',
        5: 'Quinta',
        6: 'Sexta',
        7: 'Sétima',
        8: 'Oitava',
        9: 'Nona',
        10: 'Décima',
    };

    return ordinals[number] ?? `${number}ª`;
}

function getPaymentStatus(item) {
    const paidInstallments = getPaidInstallmentsCount(item);

    const totalInstallments = getTotalInstallments(item);

    if (paidInstallments === 0) {
        return 'Não pago';
    }

    if (totalInstallments > 0 && paidInstallments >= totalInstallments) {
        return 'Pagamento concluído';
    }

    if (paidInstallments === 1) {
        return 'Primeira parcela paga';
    }

    return `${getOrdinalInstallment(paidInstallments)} parcela paga`;
}

const data = computed(() => {
    const columns = [
        {
            label: 'Número do processo',
            value: (item) => item.opening_nup ?? '-',
        },
    ];

    if (selectedPhase.value === 'pagamento') {
        columns.push(
            {
                label: 'Referência de parcelas',
                value: (item) => {
                    return getInstallmentReference(item);
                },
            },
            {
                label: 'Status do pagamento',
                value: (item) => {
                    return getPaymentStatus(item);
                },
            }
        );
    }

    columns.push({
        label: 'Fase',
        value: (item) => item.phase ?? '-',
    });

    return columns;
});

const actions = [
    {
        name: 'open',
        label: 'Abrir',
    },
];

const tableConfig = computed(() => ({
    reference,
    chips,
    data: data.value,
    actions,
    isSelectable: (item) => !!item.phase,
}));

function handleSaved() {
    selectedProjects.value = [];
}

const phaseToTab = {
    abertura: 'opening',
    analise_juridica: 'legal-analysis',
    formalizacao: 'formalization',
    orcamento: 'budget',
    pagamento: 'payment',
    monitoramento: 'monitoring',
    prestacao_de_contas: 'monitoring',
};

function handleAction({ action, item }) {
    if (!item?.opening?.id) {
        showSnackbar('Projeto ainda não entrou em fase de abertura.', 'error');

        return;
    }

    if (action !== 'open') {
        return;
    }

    const tab = phaseToTab[item.phase_slug] ?? 'opening';

    router.get(
        route('notices.projects.show', {
            notice: props.notice.id,
            project: item.id,
        }),
        { tab }
    );
}
</script>

<template>
    <Head title="Projetos" />

    <AuthenticatedLayout>
        <AppSubHeader back-route="/editais">
            <ProjectNoticeEdit :notice="notice" :instrument-types="instrumentTypes" />
        </AppSubHeader>

        <AppContainer>
            <div class="grid grid-cols-4 grid-rows-1 gap-10">
                <div class="col-span-4 col-start-1 text-[#1a1a1aFF]">
                    <PhaseFilter :phases="phases" :selected-phase="selectedPhase" @select="selectPhase" />
                </div>

                <div class="col-span-3 row-span-2 col-start-1 row-start-2 flex h-full w-full flex-col">
                    <ProjectList
                        v-model="selectedProjects"
                        :projects="projects"
                        :table-config="tableConfig"
                        :search="search"
                        @update:search="onSearch"
                        @clear-phase-filter="clearPhaseFilter"
                        @action="handleAction"
                    />
                </div>

                <div class="row-span-2 col-start-4 row-start-2">
                    <NoStageSelected v-if="!selectedPhase" :notice="notice" />
                    <OpeningActions
                        v-if="selectedPhase === 'abertura'"
                        :selected-projects="selectedProjects"
                        :projects="projects"
                        :supervisors-available="supervisorsAvailable"
                        :notice="notice"
                        @saved="handleSaved"
                    />

                    <FormalizationActions
                        v-else-if="selectedPhase === 'formalizacao'"
                        :selected-projects="selectedProjects"
                        :projects="projects"
                        :notice="notice"
                    />

                    <BudgetActions
                        v-else-if="selectedPhase === 'orcamento'"
                        :selected-projects="selectedProjects"
                        :projects="projects"
                        :notice="notice"
                    />

                    <PaymentActions
                        v-else-if="selectedPhase === 'pagamento'"
                        :selected-projects="selectedProjects"
                        :projects="projects"
                        :notice="notice"
                    />

                    <MonitoringActions
                        v-else-if="selectedPhase === 'monitoramento' || selectedPhase === 'prestacao_de_contas'"
                        :selected-projects="selectedProjects"
                        :projects="projects"
                        :notice="notice"
                        :monitoring-reports-count="monitoringReportsCount"
                    />
                </div>
            </div>
        </AppContainer>
    </AuthenticatedLayout>
</template>
