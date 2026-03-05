<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    modelValue: Boolean,
    edital: Object
})

const emit = defineEmits(['update:modelValue'])

const form = useForm({
    numero_processo_mae: '',
    tipo_instrumento: '',
    valor_total: '',
    tipo_assinatura: '',
    numero_parcelas: '',
    gestor_acompanhamento: '',
    email_gestor: '',
    processo_dotacao: '',
    data_dotacao: '',
    processo_credor: '',
    data_credor: ''
})

watch(() => props.edital, (edital) => {
    if (!edital) return

    form.numero_processo_mae = edital.numeroProcessoMae || ''
})

function close() {
    emit('update:modelValue', false)
}

function submit() {
    form.post(route('editais.identificacao.store', props.edital.id), {
        onSuccess: () => {
            close()
        }
    })
}

/* valor por extenso (exibição simples) */
const valorExtenso = computed(() => {
    if (!form.valor_total) return ''
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(form.valor_total)
})
</script>

<template>
    <v-dialog :model-value="modelValue" max-width="600" persistent>

        <v-card>

            <v-card-title class="text-h6 font-weight-bold">
                Adicione as informações de identificação e processos
            </v-card-title>

            <v-card-text>

                <!-- ───────── DADOS DE IDENTIFICAÇÃO ───────── -->

                <p class="text-subtitle-2 font-weight-bold mb-3">
                    Dados de Identificação
                </p>

                <v-row dense>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.numero_processo_mae" label="Número do processo mãe"
                            placeholder="Insira o número do processo aqui" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-select v-model="form.tipo_instrumento" label="Tipo de instrumento" :items="[
                            'Termo de execução cultural',
                            'Convênio',
                            'Contrato',
                            'Acordo de cooperação'
                        ]" placeholder="Selecione um tipo" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.valor_total" label="Valor total do edital"
                            placeholder="Insira dado da entrega aqui" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field :model-value="valorExtenso" label="Valor por extenso" readonly
                            variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.tipo_assinatura" label="Tipo de assinatura"
                            placeholder="Insira os dados da subfunção aqui" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.numero_parcelas" label="Número de parcelas" type="number"
                            placeholder="Insira o número aqui" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.gestor_acompanhamento" label="Gestor do acompanhamento do edital"
                            placeholder="Insira o dado do elemento de despesas aqui" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.email_gestor" label="Email do gestor" type="email"
                            placeholder="Data de tramitação" variant="outlined" />
                    </v-col>

                </v-row>

                <!-- ───────── DEMAIS PROCESSOS ───────── -->

                <p class="text-subtitle-2 font-weight-bold mt-6 mb-3">
                    Demais números de processo
                </p>

                <v-row dense>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.processo_dotacao" label="Nº Processo Dotação Orçamentária"
                            placeholder="Insira o número da dotação" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.data_dotacao" label="Data da Solicitação da Dotação" type="date"
                            variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.processo_credor" label="Nº Processo Cadastro do Credor"
                            placeholder="Insira o número do processo do cadastro do credor" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.data_credor" label="Data da Solicitação do Cadastro do Credor"
                            type="date" variant="outlined" />
                    </v-col>

                </v-row>

            </v-card-text>

            <v-card-actions>

                <v-spacer />

                <v-btn variant="outlined" @click="close">
                    Cancelar
                </v-btn>

                <v-btn color="#FFC107" class="text-black font-weight-bold" :loading="form.processing" @click="submit">
                    Adicionar dados
                </v-btn>

            </v-card-actions>

        </v-card>

    </v-dialog>
</template>