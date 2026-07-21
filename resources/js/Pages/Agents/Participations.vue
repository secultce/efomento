<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';

import ListDataTable from '@/Components/ListDataTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useEnums } from '@/Composables/useEnums';
import { useMask } from '@/Composables/useMask';

const props = defineProps({
    agent: { type: Object, required: true },
    participations: { type: Array, default: () => [] },
    accountTypes: { type: Array, default: () => [] },
    backUrl: { type: String, required: true },
});

const { getLabel } = useEnums();
const { maskProcessNumber } = useMask();

const noticeFilter = ref(null);
const valueFilter = ref(null);
const categoryFilter = ref(null);

const snapshot = computed(() => props.agent.latest_snapshot ?? {});
const latestOpening = computed(
    () => props.participations.find((participation) => participation.opening)?.opening ?? {}
);

const address = computed(() => {
    const data = snapshot.value;
    const streetAndNumber = [data.street, data.number].filter(Boolean).join(', ');

    return (
        [streetAndNumber, data.complement, data.neighborhood, data.city, data.state].filter(Boolean).join(' - ') || '-'
    );
});

const categoryOptions = computed(() =>
    uniqueOptions(props.participations.map((participation) => participation.category).filter(Boolean), 'name')
);

const valueOptions = [
    { title: 'Até R$ 10.000', value: 'up-to-10000' },
    { title: 'De R$ 10.000 a R$ 50.000', value: '10000-50000' },
    { title: 'De R$ 50.000 a R$ 100.000', value: '50000-100000' },
    { title: 'Acima de R$ 100.000', value: 'over-100000' },
];

function uniqueOptions(items, labelKey) {
    return Array.from(new Map(items.map((item) => [item.id, { title: item[labelKey], value: item.id }])).values());
}

function matchesValue(amount) {
    const value = Number(amount ?? 0);

    if (valueFilter.value === 'up-to-10000') return value <= 10000;
    if (valueFilter.value === '10000-50000') return value > 10000 && value <= 50000;
    if (valueFilter.value === '50000-100000') return value > 50000 && value <= 100000;
    if (valueFilter.value === 'over-100000') return value > 100000;

    return true;
}

function normalizeSearch(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

const filteredParticipations = computed(() =>
    props.participations.filter((participation) => {
        const noticeName = normalizeSearch(participation.notice?.name);
        const noticeSearch = normalizeSearch(noticeFilter.value);

        return (
            (!noticeSearch || noticeName.includes(noticeSearch)) &&
            (!categoryFilter.value || participation.category?.id === categoryFilter.value) &&
            matchesValue(participation.received_amount)
        );
    })
);

const formatCurrency = (value) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value ?? 0));

const reference = (item) => ({
    label: 'Nome do edital',
    value: item.notice?.name ?? '-',
});

const data = [
    { label: 'Valor recebido no edital', value: (item) => formatCurrency(item.received_amount) },
    { label: 'Status do projeto', value: (item) => item.phase ?? '-' },
    { label: 'Categoria', value: (item) => item.category?.name ?? '-' },
    { label: 'Número do processo', value: (item) => maskProcessNumber(item.opening_nup) || '-' },
];

function goBack() {
    router.visit(props.backUrl);
}
</script>

<template>
    <Head title="Histórico de participações" />

    <AuthenticatedLayout>
        <div class="participations-page">
            <v-btn
                type="button"
                color="#3b3b3c"
                variant="outlined"
                size="small"
                class="back-button"
                data-cy="btnVoltar"
                @click="goBack"
            >
                <v-icon size="16">ms:arrow_left_alt</v-icon>
                <span>Voltar</span>
            </v-btn>

            <main class="participations-content">
                <v-card color="subheader" class="profile-card rounded-lg !text-white !shadow-none">
                    <h1 class="profile-name">{{ agent.name }}</h1>

                    <div class="profile-summary">
                        <p><strong>CPF/CNPJ:</strong> {{ snapshot.cpf_cnpj ?? '-' }}</p>
                        <p><strong>NUP:</strong> {{ participations[0]?.notice?.creditor_registration_nup ?? '-' }}</p>
                        <p><strong>Endereço:</strong> {{ address }}</p>
                    </div>

                    <h2 class="bank-title">Dados bancários</h2>
                    <div class="bank-grid">
                        <p>
                            <span class="block">Banco</span><strong>{{ latestOpening.bank ?? '-' }}</strong>
                        </p>
                        <p>
                            <span class="block">Tipo de conta</span>
                            <strong>{{ getLabel(accountTypes, latestOpening.account_type) || '-' }}</strong>
                        </p>
                        <p>
                            <span class="block">Agência</span><strong>{{ latestOpening.branch ?? '-' }}</strong>
                        </p>
                        <p>
                            <span class="block">Conta</span><strong>{{ latestOpening.account ?? '-' }}</strong>
                        </p>
                    </div>
                </v-card>

                <section class="history-section">
                    <h2 class="mb-3 text-lg font-bold">Histórico de participações em editais</h2>

                    <div class="filters">
                        <v-text-field
                            v-model="noticeFilter"
                            label="Filtre por edital"
                            clearable
                            hide-details
                            density="compact"
                            variant="solo-filled"
                            flat
                        />
                        <v-select
                            v-model="valueFilter"
                            :items="valueOptions"
                            label="Filtre por faixa de valor"
                            clearable
                            hide-details
                            density="compact"
                            variant="solo-filled"
                            flat
                        />
                        <v-select
                            v-model="categoryFilter"
                            :items="categoryOptions"
                            label="Filtre por categoria"
                            clearable
                            hide-details
                            density="compact"
                            variant="solo-filled"
                            flat
                        />
                    </div>

                    <ListDataTable :items="filteredParticipations" :reference="reference" :data="data" />
                </section>
            </main>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.participations-page {
    display: grid;
    grid-template-columns: 70px minmax(0, 1fr);
    gap: 16px;
    width: 72%;
    margin-left: 10.5%;
    padding: 24px 0;
}

.back-button {
    align-self: start;
    width: 70px;
    min-width: 70px;
    height: 30px;
    padding: 0 8px;
    border-color: #bdbdbd;
    border-radius: 6px;
    box-shadow: none;
    font-size: 0.6875rem;
    letter-spacing: 0;
}

.back-button :deep(.v-btn__content) {
    gap: 6px;
}

.participations-content {
    min-width: 0;
}

.profile-card {
    padding: 20px;
}

.profile-name {
    margin-bottom: 4px;
    font-size: 1.125rem;
    font-weight: 700;
}

.profile-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 28px;
    font-size: 0.875rem;
}

.bank-title {
    margin: 18px 0 4px;
    font-weight: 700;
}

.bank-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(70px, max-content));
    gap: 4px 24px;
    font-size: 0.875rem;
}

.history-section {
    margin-top: 24px;
}

.filters {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

@media (max-width: 900px) {
    .participations-page {
        grid-template-columns: 1fr;
        width: auto;
        margin: 0 16px;
    }

    .back-button {
        justify-self: start;
    }
}

@media (max-width: 640px) {
    .bank-grid,
    .filters {
        grid-template-columns: 1fr;
    }
}
</style>
