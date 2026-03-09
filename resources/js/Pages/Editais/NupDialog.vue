<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import extenso from 'extenso'

const props = defineProps({
    modelValue: Boolean,
    item: Object
})
console.log(props)
const emit = defineEmits(['update:modelValue'])

const form = useForm({
    nup: '',
    instrument_type: '',
    name: '',
    total_opportunity_amount: '',
    installments: '',
    process_manager: '',
    process_manager_email: '',
    budget_allocation_nup: '',
    budget_allocation_request_date: '',
    creditor_registration_nup: '',
    creditor_registration_request_date: ''
    
})

watch(() => props.item, (opportunity) => {
    console.log(opportunity.titulo)
    if (!opportunity) return

    form.nup = opportunity.mae || ''
    form.name = opportunity.titulo || ''
})

function close() {
    emit('update:modelValue', false)
}

function submit() {
    form.patch(route('opportunities.update', props.item.id), {
        onSuccess: () => {
            close()
        }
    })
}

/* valor por extenso (exibição simples) */
const valorExtenso = computed(() => {
    if (!form.total_opportunity_amount) return ''
    return extenso(form.total_opportunity_amount, { mode: 'currency' })
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
                        <v-text-field v-model="form.nup" label="Número do processo mãe"
                            placeholder="Insira o número do processo aqui" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-select v-model="form.instrument_type" label="Tipo de instrumento" :items="[
                            'TERMO DE EXECUÇÃO CULTURAL',
                            'TERMO DE FOMENTO',
                            'TERMO DE FOMENTO SIMPLIFICADO',
                            'TERMO DE COLABORAÇÃO',
                            'CONVÊNIO',
                            'PREMIAÇÃO',
                            'AQUISIÇÃO/CONTRATO',
                            'PATROCÍNIO/CONTRATO'
                        ]" placeholder="Selecione um tipo" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.total_opportunity_amount" label="Valor total do edital"
                            placeholder="Insira dado da entrega aqui" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field :model-value="valorExtenso" label="Valor por extenso" readonly
                            variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.process_manager" label="Gestor do acompanhamento do edital"
                            placeholder="Insira o dado do elemento de despesas aqui" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.process_manager_email" label="Email do gestor" type="email"
                            placeholder="Data de tramitação" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.installments" label="Número de parcelas" type="number"
                            placeholder="Insira o número aqui" variant="outlined" />
                    </v-col>
                </v-row>

                <!-- ───────── DEMAIS PROCESSOS ───────── -->

                <p class="text-subtitle-2 font-weight-bold mt-6 mb-3">
                    Demais números de processo
                </p>

                <v-row dense>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.budget_allocation_nup" label="Nº Processo Dotação Orçamentária"
                            placeholder="Insira o número da dotação" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.budget_allocation_request_date" label="Data da Solicitação da Dotação" type="date"
                            variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.creditor_registration_nup" label="Nº Processo Cadastro do Credor"
                            placeholder="Insira o número do processo do cadastro do credor" variant="outlined" />
                    </v-col>

                    <v-col cols="12" md="6">
                        <v-text-field v-model="form.creditor_registration_request_date" label="Data da Solicitação do Cadastro do Credor"
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