<script setup>
import extenso from 'extenso';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import EditableField from '@/Components/EditableField.vue';
import { useSnackbar } from '@/Composables/useSnackbar';

const props = defineProps({
    notice: { type: Object, default: null },
    instrumentTypes: { type: Array, default: () => [] },
});

const form = useForm({
    ...props.notice,
});

const { showSnackbar } = useSnackbar();

const showAll = ref(false);

const saveAll = () => {
    form.patch(`/editais/${props.notice.id}`, {
        preserveScroll: true,
        preserveState: true,

        onSuccess: () => {
            showSnackbar('Dados atualizados com sucesso', 'success');
            form.defaults(form.data());
        },

        onError: (errors) => {
            const message = Object.values(errors).flat().join(', ') || 'Ocorreu um erro ao salvar';

            showSnackbar(message, 'error');
        },
    });
};
</script>

<template>
    <div class="grid grid-cols-[1fr_1fr_1fr_auto] items-start lg:mx-[8em] lg:w-10/12 gap-x-4 gap-y-2">
        <div class="col-span-3 col-start-1">
            <h1 data-cy="nomeDoEditalInformacoesDoProcesso">
                {{ notice.name }}
            </h1>
        </div>
        <div class="row-span-2 col-start-4 flex items-start justify-end">
            <v-btn
                class="!text-[#008344] !font-bold !border-[#008344] !h-[3em] mb-2 !px-2 !py-1"
                density="compact"
                rounded="lg"
                :disabled="!form.isDirty"
                :loading="form.processing"
                data-cy="botaoAtualizarDados"
                @click="saveAll"
            >
                Atualizar Dados
            </v-btn>
        </div>
        <div class="col-start-1 row-start-2 flex flex-col justify-start">
            <p data-cy="numeroDeProcessoInformacoesDoProcesso">
                <span class="font-bold">Nº do processo mãe: </span>{{ notice.nup }}
            </p>
            <div v-show="showAll" class="mt-2 space-y-1 transition-all duration-200 ease-in-out">
                <EditableField
                    v-model="form.instrument_type"
                    label="Tipo de Instrumento:"
                    type="select"
                    :items="instrumentTypes"
                    :error="form.errors.instrument_type"
                    data-cy="tipoDeInstrumentoInformacoesDoProcesso"
                />
                <EditableField
                    v-model="form.process_manager"
                    label="Gestor do processo do sistema:"
                    data-cy="nomeDoGestorInformacoesDoProcesso"
                />
                <EditableField
                    v-model="form.budget_allocation_request_date"
                    label="Data da Solicitação da Dotação Orçamentária:"
                    type="date"
                    data-cy="dataSolicitacaoDotOrcamentariaInformacoesDoProcesso"
                />
            </div>
        </div>
        <div class="col-start-2 row-start-2">
            <EditableField
                v-model="form.total_notice_amount"
                label="Valor total do edital:"
                type="number"
                :format="
                    (value) =>
                        new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL',
                        }).format(value)
                "
                :error="form.errors.total_notice_amount"
                data-cy="valorTotalDoProcessoInformacoesDoProcesso"
            />

            <div v-show="showAll" class="mt-2 space-y-1 transition-all duration-200 ease-in-out">
                <p data-cy="valorPorExtensoInformacoesDoProcesso">
                    <span class="font-bold">Valor por extenso: </span
                    >{{ extenso(Number(form.total_notice_amount), { mode: 'currency' }) }}
                </p>
                <EditableField
                    v-model="form.process_manager_email"
                    label="Email do gestor:"
                    type="email"
                    data-cy="emailGestorInformacoesDoProcesso"
                />
                <EditableField
                    v-model="form.creditor_registration_nup"
                    label="N° Processo Cadastro do Credor:"
                    data-cy="numeroProcessoCredorInformacoesDoProcesso"
                />
            </div>
        </div>
        <div class="col-start-3 row-start-2">
            <p class="text-[#ffcc05FF] cursor-pointer" @click="showAll = !showAll">
                Mostrar todas as informações
                <v-icon size="18" :class="{ 'rotate-180': showAll }" data-cy="botaoMostrarTodasAsInformacoes"
                    >mdi-chevron-down</v-icon
                >
            </p>
            <div v-show="showAll" class="mt-2 space-y-1 transition-all duration-200 ease-in-out">
                <EditableField
                    v-model="form.installments"
                    label="N° Parcelas:"
                    type="number"
                    data-cy="numeroParcelasInformacoesDoProcesso"
                />
                <EditableField
                    v-model="form.budget_allocation_nup"
                    label="N° Processo Dotação Orçamentária:"
                    mask="#####.######/####-##"
                    data-cy="numeroProcessoDotacaoInformacoesDoProcesso"
                />
                <EditableField
                    v-model="form.creditor_registration_request_date"
                    label="Data da Solicitação do Cadastro do Credor:"
                    type="date"
                    data-cy="dataSolicitacaoCadastroCredorInformacoesDoProcesso"
                />
            </div>
        </div>
    </div>
</template>
