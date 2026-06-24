<script setup>
import { ref, computed, watch } from 'vue';
import NupDialog from './NupDialog.vue';
import { router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import { useMask } from '@/Composables/useMask';

const props = defineProps({
    notices: {
        type: Array,
        default: () => [],
    },
    totalNotices: {
        type: Number,
        default: 0,
    },
    instrumentTypes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['search', 'filter-status', 'filter-instrument', 'access', 'pagete', 'change-per-page']);

const noticesMock = [
    {
        id: 1,
        titulo: 'EDITAL DE CHAMAMENTO PÚBLICO Nº 005/2025 - PRE...',
        status: 'Em abertura de processo',
        tipoInstrumento: 'Termo de execução cultural',
        numeroProcessoMae: '000054554654/45457',
    },
    {
        id: 2,
        titulo: 'EDITAL DE CHAMAMENTO PÚBLICO Nº 006/2025 - CICLO CEARENSE CARNAVALESCO',
        status: 'Em abertura de processo',
        tipoInstrumento: 'Termo de execução cultural',
        numeroProcessoMae: '000054554654/45457',
    },
    {
        id: 3,
        titulo: 'EDITAL DE CHAMAMENTO PÚBLICO Nº 007/2025 - PNAB MÚSICA',
        status: 'Em abertura de processo',
        tipoInstrumento: 'Termo de execução cultural',
        numeroProcessoMae: '000054554654/45458',
    },
    {
        id: 4,
        titulo: 'EDITAL DE CHAMAMENTO PÚBLICO Nº 008/2025 - CULTURA VIVA',
        status: 'Em abertura de processo',
        tipoInstrumento: 'Termo de execução cultural',
        numeroProcessoMae: '000054554654/45459',
    },
    {
        id: 5,
        titulo: 'EDITAL DE CHAMAMENTO PÚBLICO Nº 009/2025 - FUNDO DE CULTURA',
        status: 'Em abertura de processo',
        tipoInstrumento: 'Termo de execução cultural',
        numeroProcessoMae: '000054554654/45460',
    },
];

const instrumentOptions = computed(() => props.instrumentTypes);

const itemsPerPageOptions = [10, 25, 50];

// ─── Headers da tabela ────────────────────────────────────────────────────────

const headers = [
    { title: 'Título', key: 'titulo', align: 'start', sortable: false },
    { title: 'Status', key: 'status', align: 'center', sortable: false },
    { title: 'Tipo de instrumento', key: 'type_ins', align: 'center', sortable: false },
    { title: 'Nº do processo mãe', key: 'mae', align: 'center', sortable: false },
    { title: 'Acessar', key: 'acessar', align: 'center', sortable: false },
];

// ─── Estado ───────────────────────────────────────────────────────────────────

const search = ref('');
const selectedStatus = ref(null);
const selectedInstrument = ref(null);
const page = ref(1);
const itemsPerPage = ref(10);

// ─── Computeds ────────────────────────────────────────────────────────────────

const itens = computed(() => (props.notices.length ? props.notices : noticesMock));

const itensFiltrados = computed(() => {
    let lista = itens.value;

    if (search.value.trim()) {
        const termo = search.value.toLowerCase();
        lista = lista.filter(
            (item) =>
                item.titulo?.toLowerCase().includes(termo) ||
                item.mae?.toLowerCase().includes(termo) ||
                item.numeroProcessoMae?.toLowerCase().includes(termo)
        );
    }

    if (selectedStatus.value) {
        lista = lista.filter((item) => item.status === selectedStatus.value);
    }

    if (selectedInstrument.value) {
        lista = lista.filter(
            (item) => item.tipoInstrumento === selectedInstrument.value || item.type_ins === selectedInstrument.value
        );
    }

    return lista;
});

// Reseta para página 1 sempre que qualquer filtro mudar
watch([search, selectedStatus, selectedInstrument], () => {
    page.value = 1;
});

const total = computed(() => itensFiltrados.value.length);

const totalPages = computed(() => Math.ceil(total.value / itemsPerPage.value) || 1);

const visiblePages = computed(() => {
    const n = totalPages.value;
    const c = page.value;

    if (n <= 7) return Array.from({ length: n }, (_, i) => i + 1);

    if (c <= 4) return [1, 2, 3, 4, '...', n];

    if (c >= n - 3) return [1, '...', n - 3, n - 2, n - 1, n];

    return [1, '...', c - 1, c, c + 1, '...', n];
});

// ─── Handlers ────────────────────────────────────────────────────────────────

function onSearch(value) {
    search.value = value;
    emit('search', value);
}

function onFilterStatus(value) {
    emit('filter-status', value);
}

function onFilterInstrument(value) {
    emit('filter-instrument', value);
}

function onAccess(item) {
    router.visit(route('notices.projects', item.id));
}

function goToPage(p) {
    if (typeof p !== 'number' || p < 1 || p > totalPages.value) return;
    page.value = p;
    emit('pagete', p);
}

function onChangePerPage(qty) {
    page.value = 1;
    emit('change-per-page', qty);
}

// ─── nup dialog ───────────────────────────────────────────────────────────────
const { canManageNotices } = usePermissions();
const { maskProcessNumber } = useMask();

const dialog = ref(false);
const selectedItem = ref(null);

function openDialog(item) {
    selectedItem.value = item;
    dialog.value = true;
}
</script>

<template>
    <nup-dialog v-model="dialog" :item="selectedItem" :instrument-types="instrumentTypes" />
    <v-card flat class="pa-6 bg-white" data-cy="table-notice-list">
        <!-- ── Cabeçalho ──────────────────────────────────────────────────── -->
        <div class="mb-5">
            <p class="text-subtitle-1 font-weight-bold text-grey-darken-3">
                Editais disponíveis para acompanhamento abaixo
            </p>
            <p class="text-body-2 text-grey-darken-1 mt-1">
                Total de editais encontrados:
                <strong class="text-grey-darken-3">{{ total }}</strong>
            </p>
        </div>

        <!-- ── Filtros ────────────────────────────────────────────────────── -->
        <v-row dense class="mb-4">
            <v-col cols="12" md="4">
                <v-text-field
                    :model-value="search"
                    placeholder="Busque editais específicos"
                    append-inner-icon="mdi-magnify"
                    variant="outlined"
                    density="compact"
                    hide-details
                    rounded="xl"
                    class="border border-green-700 rounded-xl"
                    data-cy="find-specific-notice"
                    @update:model-value="onSearch"
                />
            </v-col>

            <v-col cols="12" md="4">
                <v-select
                    v-model="selectedStatus"
                    :items="statusOptions"
                    placeholder="Filtre por status do processo"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    hide-details
                    clearable
                    data-cy="filter-notice-by-status-process"
                    @update:model-value="onFilterStatus"
                />
            </v-col>

            <v-col cols="12" md="4">
                <v-select
                    v-model="selectedInstrument"
                    :items="instrumentOptions"
                    placeholder="Filtre pelo tipo de instrumento"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    hide-details
                    clearable
                    data-cy="filter-notice-by-instrument-type-notices-list"
                    @update:model-value="onFilterInstrument"
                />
            </v-col>
        </v-row>

        <!-- ── Tabela ─────────────────────────────────────────────────────── -->
        <v-data-table
            v-model:page="page"
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="itensFiltrados"
            class="notices-table"
            data-cy="row-table-notice-list"
        >
            <!-- Título truncado -->
            <template #item.titulo="{ item }">
                <span class="titulo-truncado" data-cy="notice-title-notices-list">{{ item.titulo }}</span>
            </template>

            <!-- Badge de status -->
            <template #item.status="{ item }">
                <span class="status-badge" data-cy="process-status-notices-list">
                    <span class="status-dot">•</span>
                    {{ item.status }}
                </span>
            </template>

            <!-- Tipo de instrumento -->
            <template #item.type_ins="{ item }">
                <span class="titulo-truncado" data-cy="instrument-type-notices-list">{{ item.type_ins }}</span>
            </template>
            <!-- Número do processo mãe -->
            <template #item.mae="{ item }">
                <span v-if="item.numeroProcessoMae || item.mae" data-cy="notice-nup-notices-list">
                    {{ maskProcessNumber(item.numeroProcessoMae || item.mae) }}
                </span>
                <v-btn
                    v-else
                    v-permission="{
                        condition: canManageNotices,
                        message: 'Você não tem permissão para informar os dados de identificação',
                    }"
                    variant="text"
                    density="compact"
                    class="!text-[#008344] !font-bold spacing tracking-tight"
                    data-cy="access-identification-data-form-button"
                    @click="openDialog(item)"
                >
                    Informe os dados de identificação
                </v-btn>
            </template>
            <!-- Ícone de acesso -->
            <template #item.acessar="{ item }">
                <v-btn
                    icon
                    variant="text"
                    size="small"
                    :disabled="!(item.numeroProcessoMae || item.mae)"
                    data-cy="access-notice-information"
                    @click="onAccess(item)"
                >
                    <v-icon :color="!(item.numeroProcessoMae || item.mae) ? 'grey' : '#008344'" size="22">
                        mdi-eye-circle
                    </v-icon>
                </v-btn>
            </template>

            <!-- ── Paginação customizada ───────────────────────────────────── -->
            <template #bottom>
                <div class="bg-gray-100">
                    <v-row align="center" class="px-4 ma-0">
                        <!-- Coluna esquerda — vazia (equilíbrio visual) -->
                        <v-col cols="4" />

                        <!-- Coluna central — botões de paginação -->
                        <v-col cols="4" class="d-flex justify-center align-center gap-1">
                            <v-btn icon variant="text" size="small" :disabled="page <= 1" @click="goToPage(page - 1)">
                                <v-icon>mdi-chevron-left</v-icon>
                            </v-btn>

                            <template v-for="p in visiblePages" :key="`p-${p}`">
                                <span v-if="p === '...'" class="px-1 text-grey-darken-1 text-body-2"> ... </span>
                                <v-btn
                                    v-else
                                    variant="flat"
                                    size="small"
                                    rounded
                                    :style="
                                        page === p
                                            ? 'background-color:#FFC107; color:#fff; min-width:32px'
                                            : 'min-width:32px'
                                    "
                                    data-cy="pagination-number-notice-list"
                                    @click="goToPage(p)"
                                >
                                    {{ p }}
                                </v-btn>
                            </template>

                            <v-btn
                                icon
                                variant="text"
                                size="small"
                                :disabled="page >= totalPages"
                                @click="goToPage(page + 1)"
                            >
                                <v-icon>mdi-chevron-right</v-icon>
                            </v-btn>
                        </v-col>

                        <!-- Coluna direita — itens por página -->
                        <v-col cols="4" class="d-flex justify-end align-center gap-2">
                            <span class="text-body-2 text-grey-darken-1 text-no-wrap">
                                Alterar exibição da lista: Exibindo {{ itemsPerPage }} itens
                            </span>
                            <v-select
                                v-model="itemsPerPage"
                                :items="itemsPerPageOptions"
                                variant="outlined"
                                density="compact"
                                hide-details
                                style="width: 75px"
                                data-cy="quantity-notices-per-page-notice-list"
                                @update:model-value="onChangePerPage"
                            />
                        </v-col>
                    </v-row>
                </div>
            </template>
        </v-data-table>
    </v-card>
</template>

<style scoped>
/* Container da tabela */
.notices-table {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

/* Cabeçalho da tabela */
:deep(.v-data-table__th),
:deep(thead tr th) {
    background-color: #f5f5f5 !important;
    font-weight: 700 !important;
    font-size: 0.8125rem !important;
    border-bottom: 1px solid #f0f0f0 !important;
}

/* Divisória sutil entre linhas */
:deep(.v-data-table__tr:not(:last-child) td) {
    border-bottom: 1px solid #f0f0f0 !important;
}

/* Título com truncamento */
.titulo-truncado {
    display: inline-block;
    max-width: 280px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
}

/* Badge de status */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background-color: #bbdefb;
    color: #1565c0;
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
}

.status-dot {
    font-size: 1rem;
    line-height: 1;
    color: #1565c0;
}
</style>
