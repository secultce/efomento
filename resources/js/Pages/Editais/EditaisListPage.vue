<script setup>
import { ref, computed } from 'vue'

// ─── Props & Emits ────────────────────────────────────────────────────────────

const props = defineProps({
    editais: {
        type: Array,
        default: () => [],
    },
    totalEditais: {
        type: Number,
        default: 0,
    },
})

const emit = defineEmits([
    'buscar',
    'filtrar-status',
    'filtrar-instrumento',
    'acessar',
    'paginar',
    'alterar-por-pagina',
])

// ─── Mock data ────────────────────────────────────────────────────────────────

const editaisMock = [
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
]

// ─── Opções dos filtros ───────────────────────────────────────────────────────

const opcoesStatus = [
    'Em abertura de processo',
    'Em análise jurídica',
    'Em formalização',
    'Em orçamento',
    'Em pagamento',
    'Em monitoramento',
]

const opcoesInstrumento = [
    'Termo de execução cultural',
    'Convênio',
    'Contrato',
    'Acordo de cooperação',
]

const opcoesItensPorPagina = [10, 25, 50]

// ─── Headers da tabela ────────────────────────────────────────────────────────

const headers = [
    { title: 'Título',              key: 'titulo',           align: 'start',  sortable: false },
    { title: 'Status',              key: 'status',           align: 'center', sortable: false },
    { title: 'Tipo de instrumento', key: 'type_ins',  align: 'center', sortable: false },
    { title: 'Nº do processo mãe',  key: 'mae',align: 'center', sortable: false },
    { title: 'Acessar',             key: 'acessar',          align: 'center', sortable: false },
]

// ─── Estado ───────────────────────────────────────────────────────────────────

const busca               = ref('')
const statusSelecionado   = ref(null)
const instrSelecionado    = ref(null)
const pagina              = ref(1)
const itensPorPagina      = ref(10)

// ─── Computeds ────────────────────────────────────────────────────────────────

const itens = computed(() => (props.editais.length ? props.editais : editaisMock))

const total = computed(() => props.totalEditais || itens.value.length)

const totalPaginas = computed(() => Math.ceil(total.value / itensPorPagina.value) || 1)

const paginasVisiveis = computed(() => {
    const n = totalPaginas.value
    const c = pagina.value

    if (n <= 7) return Array.from({ length: n }, (_, i) => i + 1)

    if (c <= 4) return [1, 2, 3, 4, '...', n]

    if (c >= n - 3) return [1, '...', n - 3, n - 2, n - 1, n]

    return [1, '...', c - 1, c, c + 1, '...', n]
})

// ─── Handlers ────────────────────────────────────────────────────────────────

function onBuscar(valor) {
    busca.value = valor
    emit('buscar', valor)
}

function onFiltrarStatus(valor) {
    emit('filtrar-status', valor)
}

function onFiltrarInstrumento(valor) {
    emit('filtrar-instrumento', valor)
}

function onAcessar(item) {
    emit('acessar', item)
}

function irParaPagina(p) {
    if (typeof p !== 'number' || p < 1 || p > totalPaginas.value) return
    pagina.value = p
    emit('paginar', p)
}

function onAlterarPorPagina(qtd) {
    pagina.value = 1
    emit('alterar-por-pagina', qtd)
}
</script>

<template>
    <v-card flat class="pa-6 bg-white">
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
                    :model-value="busca"
                    @update:model-value="onBuscar"
                    placeholder="Busque editais específicos"
                    append-inner-icon="mdi-magnify"
                    variant="outlined"
                    density="compact"
                    hide-details
                    rounded="xl"
                    class="border border-green-700 rounded-xl"
                />
            </v-col>

            <v-col cols="12" md="4">
                <v-select
                    v-model="statusSelecionado"
                    @update:model-value="onFiltrarStatus"
                    :items="opcoesStatus"
                    placeholder="Filtre por status do processo"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    hide-details
                    clearable
                />
            </v-col>

            <v-col cols="12" md="4">
                <v-select
                    v-model="instrSelecionado"
                    @update:model-value="onFiltrarInstrumento"
                    :items="opcoesInstrumento"
                    placeholder="Filtre pelo tipo de instrumento"
                    variant="outlined"
                    density="compact"
                    rounded="lg"
                    hide-details
                    clearable
                />
            </v-col>
        </v-row>

        <!-- ── Tabela ─────────────────────────────────────────────────────── -->
        <v-data-table
            v-model:search="busca"
            v-model:page="pagina"
            v-model:items-per-page="itensPorPagina"
            :headers="headers"
            :items="itens"
            :filter-keys="['titulo', 'numeroProcessoMae']"
            class="editais-table"
        >
            <!-- Título truncado -->
            <template #item.titulo="{ item }">
                <span class="titulo-truncado">{{ item.titulo }}</span>
            </template>

            <!-- Badge de status -->
            <template #item.status="{ item }">
                <span class="status-badge">
                    <span class="status-dot">•</span>
                    {{ item.status }}
                </span>
            </template>

            <!-- Tipo de instrumento -->
            <template #item.type_ins="{ item }">
                <span class="titulo-truncado">{{ item.type_ins }}</span>
            </template>

            <!-- Ícone de acesso -->
            <template #item.acessar="{ item }">
                <v-btn
                    icon
                    variant="text"
                    size="small"
                    @click="onAcessar(item)"
                >
                    <v-icon color="#008344" size="22">mdi-eye-circle</v-icon>
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
                            <v-btn
                                icon
                                variant="text"
                                size="small"
                                :disabled="pagina <= 1"
                                @click="irParaPagina(pagina - 1)"
                            >
                                <v-icon>mdi-chevron-left</v-icon>
                            </v-btn>

                            <template v-for="p in paginasVisiveis" :key="`p-${p}`">
                            <span
                                v-if="p === '...'"
                                class="px-1 text-grey-darken-1 text-body-2"
                            >
                                ...
                            </span>
                                <v-btn
                                    v-else
                                    variant="flat"
                                    size="small"
                                    rounded
                                    :style="pagina === p
                                    ? 'background-color:#FFC107; color:#fff; min-width:32px'
                                    : 'min-width:32px'"
                                    @click="irParaPagina(p)"
                                >
                                    {{ p }}
                                </v-btn>
                            </template>

                            <v-btn
                                icon
                                variant="text"
                                size="small"
                                :disabled="pagina >= totalPaginas"
                                @click="irParaPagina(pagina + 1)"
                            >
                                <v-icon>mdi-chevron-right</v-icon>
                            </v-btn>
                        </v-col>

                        <!-- Coluna direita — itens por página -->
                        <v-col cols="4" class="d-flex justify-end align-center gap-2">
                        <span class="text-body-2 text-grey-darken-1 text-no-wrap">
                            Alterar exibição da lista: Exibindo {{ itensPorPagina }} itens
                        </span>
                            <v-select
                                v-model="itensPorPagina"
                                @update:model-value="onAlterarPorPagina"
                                :items="opcoesItensPorPagina"
                                variant="outlined"
                                density="compact"
                                hide-details
                                style="width: 75px"
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
.editais-table {
    border: 1px solid #E0E0E0;
    border-radius: 8px;
    overflow: hidden;
}

/* Cabeçalho da tabela */
:deep(.v-data-table__th),
:deep(thead tr th) {
    background-color: #f5f5f5 !important;
    font-weight: 700 !important;
    font-size: 0.8125rem !important;
    //color: #ffffff !important;
    border-bottom: 1px solid #F0F0F0 !important;
}

/* Divisória sutil entre linhas */
:deep(.v-data-table__tr:not(:last-child) td) {
    border-bottom: 1px solid #F0F0F0 !important;
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
    background-color: #BBDEFB;
    color: #1565C0;
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
}

.status-dot {
    font-size: 1rem;
    line-height: 1;
    color: #1565C0;
}
</style>
