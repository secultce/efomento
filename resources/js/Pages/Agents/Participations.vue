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
        <div
            class="grid grid-cols-1 gap-4 py-6 max-[900px]:mx-4 min-[901px]:ml-[10.5%] min-[901px]:w-[72%] min-[901px]:grid-cols-[70px_minmax(0,1fr)]"
        >
            <v-btn
                type="button"
                color="#3b3b3c"
                variant="outlined"
                size="small"
                class="!h-[30px] !w-[70px] !min-w-[70px] self-start !rounded-[6px] !border-[#bdbdbd] !px-2 !text-[0.6875rem] !tracking-normal !shadow-none [&_.v-btn__content]:!gap-1.5 max-[900px]:justify-self-start"
                data-cy="btnVoltar"
                @click="goBack"
            >
                <v-icon size="16">ms:arrow_left_alt</v-icon>
                <span>Voltar</span>
            </v-btn>

            <main class="min-w-0">
                <v-card color="subheader" class="rounded-lg !p-5 !text-white !shadow-none">
                    <h1 class="mb-1 text-lg font-bold">{{ agent.name }}</h1>

                    <div class="flex flex-wrap gap-x-7 gap-y-1 text-sm">
                        <p><strong>CPF/CNPJ:</strong> {{ snapshot.cpf_cnpj ?? '-' }}</p>
                        <p><strong>NUP:</strong> {{ participations[0]?.notice?.creditor_registration_nup ?? '-' }}</p>
                        <p><strong>Endereço:</strong> {{ address }}</p>
                    </div>

                    <h2 class="mb-1 mt-[18px] font-bold">Dados bancários</h2>
                    <div
                        class="grid grid-cols-1 gap-x-6 gap-y-1 text-sm min-[641px]:grid-cols-[repeat(4,minmax(70px,max-content))]"
                    >
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

                <section class="mt-6">
                    <h2 class="mb-3 text-lg font-bold">Histórico de participações em editais</h2>

                    <div class="mb-3 grid grid-cols-1 gap-3 min-[641px]:grid-cols-3">
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
