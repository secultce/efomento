<script setup>
import AppContainer from '@/Components/AppContainer.vue';
import AppSubHeader from '@/Components/AppSubHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProjectList from '@/Pages/Projects/Partials/ProjectList.vue';
import PhaseFilter from '@/Pages/Projects/Partials/PhaseFilter.vue';
import ProjectNoticeEdit from '@/Pages/Projects/Partials/ProjectNoticeEdit.vue';
import OpeningActions from '@/Pages/Projects/Partials/Actions/Opening/OpeningActions.vue';
import { useSnackbar } from '@/Composables/useSnackbar';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import FormalizationActions from './Partials/Actions/Formalization/FormalizationActions.vue';
import PaymentActions from './Partials/Actions/Payment/PaymentActions.vue';
import MonitoringActions from './Partials/Actions/Monitoring/MonitoringActions.vue';

const props = defineProps({
    notice: { type: Object, default: null },
    projects: { type: Array, default: () => [] },
    filters: { type: Object, default: null },
    phases: { type: Array, default: () => [] },
    instrumentTypes: { type: Array, default: () => [] },
    supervisorsAvailable: { type: Array, default: () => [] },
});

const search = ref(props.filters?.search ?? '');
const selectedPhase = ref(props.filters?.phase ?? null);

const { showSnackbar } = useSnackbar();

function selectPhase(phase) {
    selectedPhase.value = phase.value;

    router.get(
        route('notices.projects', props.notice.id),
        {
            phase: selectedPhase.value,
            search: search.value,
        },
        {
            preserveState: true,
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
            replace: true,
        }
    );
}

function clearPhaseFilter() {
    selectedPhase.value = null;

    router.get(
        route('notices.projects', props.notice.id),
        {},
        {
            preserveState: true,
            replace: true,
        }
    );
}

const reference = (item) => ({
    label: 'Nome do Proponente',
    value: item.agent?.name ?? '-',
});

function getChipColor(status) {
    const map = {
        draft: 'gray',
        pending_signature: 'yellow',
        signed: 'green',
    };

    return map[status] ?? 'default';
}

const chips = (item) => {
    if (!Array.isArray(item.documents)) return [];

    const uniqueDocs = Object.values(
        item.documents.reduce((acc, doc) => {
            acc[doc.type] = doc;
            return acc;
        }, {})
    );

    return uniqueDocs.map((doc) => ({
        label: doc.type_label,
        color: getChipColor(doc.status),
    }));
};

const data = [
    {
        label: 'Número do processo',
        value: (item) => item.opening_nup ?? '-',
    },
    {
        label: 'Fase',
        value: (item) => item.phase ?? '-',
    },
];

const actions = [{ name: 'open', label: 'Abrir' }];

const tableConfig = {
    reference,
    chips,
    data,
    actions,
    isSelectable: (item) => !!item.phase,
};

const selectedProjects = ref([]);

function handleSaved() {
    selectedProjects.value = [];
}

function handleAction({ action, item }) {
    if (!item?.opening?.id) {
        showSnackbar('Projeto ainda não entrou em fase de abertura.', 'error');
        return;
    }

    if (action === 'open') {
        router.get(
            route('notices.projects.show', {
                notice: props.notice.id,
                project: item.id,
            }),
            selectedPhase.value ? { tab: selectedPhase.value } : {}
        );
    }
}
</script>

<template>
    <Head :title="`Projetos`" />
    <AuthenticatedLayout>
        <AppSubHeader back-route="/editais">
            <ProjectNoticeEdit :notice="notice" :instrument-types="instrumentTypes" />
        </AppSubHeader>
        <AppContainer>
            <div class="grid grid-cols-4 grid-rows-1 gap-10">
                <div class="col-span-4 col-start-1 text-[#1a1a1aFF]">
                    <PhaseFilter :phases="phases" :selected-phase="selectedPhase" @select="selectPhase" />
                </div>
                <div class="col-span-3 row-span-2 col-start-1 row-start-2 flex flex-col w-full h-full">
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
                    <PaymentActions
                        v-else-if="selectedPhase === 'pagamento'"
                        :selected-projects="selectedProjects"
                        :projects="projects"
                        :notice="notice"
                    />
                    <MonitoringActions
                        v-else-if="selectedPhase === 'monitoramento'"
                        :selected-projects="selectedProjects"
                        :projects="projects"
                        :notice="notice"
                    />
                </div>
            </div>
        </AppContainer>
    </AuthenticatedLayout>
</template>
