<script setup>
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import extenso from 'extenso'

import FormField from '@/Components/FormField.vue'
import TextField from '@/Components/TextField.vue'
import SelectField from '@/Components/SelectField.vue'
import { useSnackbar } from '@/Composables/useSnackbar'

const props = defineProps({
    modelValue: Boolean,
    item: Object
})

const { showSnackbar } = useSnackbar()

const formRef = ref(null)
const isValid = ref(false)

const emit = defineEmits(['update:modelValue'])

const instrumentTypes = [
    'TERMO DE EXECUÇÃO CULTURAL',
    'TERMO DE FOMENTO',
    'TERMO DE FOMENTO SIMPLIFICADO',
    'TERMO DE COLABORAÇÃO',
    'CONVÊNIO',
    'PREMIAÇÃO',
    'AQUISIÇÃO/CONTRATO',
    'PATROCÍNIO/CONTRATO'
]

const form = useForm({
    nup: '',
    instrument_type: null,
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
    if (!opportunity) return
    console.log(form)
    form.nup = opportunity.mae ?? ''
})

function close() {
    emit('update:modelValue', false)
}

async function submit() {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    form.patch(route('opportunities.update', props.item.id), {
        onSuccess: () => {
            showSnackbar('Número do processo salvo com sucesso', 'success')
            close()
            form.reset()
        },
        onError: (errors) => {
            const message =
                Object.values(errors).flat().join(', ') ||
                'Ocorreu um erro ao salvar o processo'
            showSnackbar(message, 'error')
        }
    })
}

const valorExtenso = computed(() => {
    if (!form.total_opportunity_amount) return ''

    const value = Number(form.total_opportunity_amount)

    if (isNaN(value)) return ''

    return extenso(value, { mode: 'currency' })
})
</script>

<template>
    <v-dialog :model-value="modelValue" max-width="600" persistent>
        <v-card>
            <v-form ref="formRef" v-model="isValid">
                <v-card-title class="text-h6 font-weight-bold">
                    Adicione as informações de identificação e processos
                </v-card-title>

                <v-card-text>

                    <p class="text-subtitle-2 font-weight-bold mb-3">
                        Dados de Identificação
                    </p>

                    <v-row dense>

                        <v-col cols="12" md="6">
                            <FormField label="Número do processo mãe (SUITE)" :error="form.errors.nup" required>
                                <TextField v-model="form.nup" placeholder="Insira o número do processo aqui" clearable
                                    mask="#####.######/####-##" />
                            </FormField>
                        </v-col>

                        <v-col cols="12" md="6">
                            <FormField label="Tipo de instrumento" :error="form.errors.instrument_type" required
                                clearable>
                                <SelectField v-model="form.instrument_type" :items="instrumentTypes"
                                    placeholder="Selecione um tipo" />
                            </FormField>
                        </v-col>

                        <v-col cols="12" md="6">
                            <FormField label="Valor total do edital" :error="form.errors.total_opportunity_amount"
                                required>
                                <TextField v-model="form.total_opportunity_amount"
                                    placeholder="Insira o valor total do edital aqui" money />
                            </FormField>
                        </v-col>

                        <v-col cols="12" md="6">
                            <FormField label="Valor por extenso">
                                <TextField :model-value="valorExtenso" readonly capitalize disabled />
                            </FormField>
                        </v-col>

                        <v-col cols="12" md="6">
                            <FormField label="Gestor do acompanhamento do edital" :error="form.errors.process_manager" required>
                                <TextField v-model="form.process_manager" placeholder="Insira o nome do gestor aqui"
                                    capitalize />
                            </FormField>
                        </v-col>

                        <v-col cols="12" md="6">
                            <FormField label="Email do gestor" :error="form.errors.process_manager_email" required>
                                <TextField v-model="form.process_manager_email"
                                    placeholder="Insira o email do gestor aqui" />
                            </FormField>
                        </v-col>

                        <v-col cols="12" md="6">
                            <FormField label="Número de parcelas" :error="form.errors.installments" required>
                                <TextField v-model="form.installments" placeholder="Insira o número de parcelas"
                                    type="number" />
                            </FormField>
                        </v-col>

                    </v-row>

                    <p class="text-subtitle-2 font-weight-bold mt-6 mb-3">
                        Demais números de processo
                    </p>

                    <v-row dense>

                        <v-col cols="12" md="6">
                            <FormField label="Número do processo de dotação orçamentária"
                                :error="form.errors.budget_allocation_nup">
                                <TextField v-model="form.budget_allocation_nup" placeholder="Insira o número da dotação"
                                    mask="#####.######/####-##" />
                            </FormField>
                        </v-col>

                        <v-col cols="12" md="6">
                            <FormField label="Data da Solicitação da Dotação"
                                :error="form.errors.budget_allocation_request_date">
                                <TextField v-model="form.budget_allocation_request_date" type="date" />
                            </FormField>
                        </v-col>

                        <v-col cols="12" md="6">
                            <FormField label="Nº Processo Cadastro do Credor"
                                :error="form.errors.creditor_registration_nup">
                                <TextField v-model="form.creditor_registration_nup"
                                    placeholder="Número do processo do cadastro do credor"
                                    mask="#####.######/####-##" />
                            </FormField>
                        </v-col>

                        <v-col cols="12" md="6">
                            <FormField label="Data da Solicitação do Cadastro do Credor"
                                :error="form.errors.creditor_registration_request_date">
                                <TextField v-model="form.creditor_registration_request_date" type="date" />
                            </FormField>
                        </v-col>

                    </v-row>

                </v-card-text>

                <v-card-actions>

                    <v-spacer />

                    <v-btn
                        class="!inline-flex !bg-[#485465FF] !items-center !justify-center !rounded-md !px-4 !py-2 !text-xs !font-semibold !tracking-widest !transition !duration-150 !ease-in-out !focus:outline-none !focus:ring-2 !focus:ring-gray-800 !focus:ring-offset-2 !text-white"
                        variant="outlined" @click="close">
                        Cancelar
                    </v-btn>

                    <v-btn
                        class="!inline-flex !bg-[#ffcc05FF] !items-center !justify-center !rounded-md !px-4 !py-2 !text-xs !font-semibold !tracking-widest !transition !duration-150 !ease-in-out !focus:outline-none !focus:ring-2 !focus:ring-gray-800 !focus:ring-offset-2 !text-black"
                        :loading="form.processing" :disabled="form.processing" @click="submit">
                        Adicionar dados
                    </v-btn>

                </v-card-actions>
            </v-form>
        </v-card>

    </v-dialog>
</template>