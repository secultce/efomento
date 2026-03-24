<script setup>
import extenso from 'extenso'
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import EditableField from '@/Components/EditableField.vue'
import { useSnackbar } from '@/Composables/useSnackbar'

const props = defineProps({
    notice: Object,
    instrumentTypes: Array
})

const form = useForm({
    ...props.notice
})

const { showSnackbar } = useSnackbar()

const showAll = ref(false)

const saveAll = () => {
    form.patch(`/editais/${props.notice.id}`, {
        preserveScroll: true,
        preserveState: true,

        onSuccess: () => {
            showSnackbar('Dados atualizados com sucesso', 'success')
            form.defaults(form.data())
        },

        onError: (errors) => {
            const message =
                Object.values(errors).flat().join(', ') ||
                'Ocorreu um erro ao salvar'

            showSnackbar(message, 'error')
        }
    })
}

</script>

<template>
    <div class="grid grid-cols-[1fr_1fr_1fr_auto] items-start lg:mx-[8em] lg:w-10/12 gap-x-4 gap-y-2">
        <div class="col-span-3 col-start-1">
            <h1>
                {{ notice.name }}
            </h1>
        </div>
        <div class="row-span-2 col-start-4 flex items-start justify-end">
            <v-btn class="!text-[#008344] !font-bold !border-[#008344] !h-[3em] mb-2 !px-2 !py-1"
                density="compact" 
                rounded="lg"
                @click="saveAll"
                :disabled="!form.isDirty"
                :loading="form.processing"
                >
                Atualizar Dados
            </v-btn>
        </div>
        <div class="col-start-1 row-start-2 flex flex-col justify-start">
            <p><span class="font-bold">Nº do processo mãe: </span>{{ notice.nup }}</p>
            <div v-show="showAll" class="mt-2 space-y-1 transition-all duration-200 ease-in-out">
                <editable-field label="Tipo de Instrumento:" type="select" :items="instrumentTypes" v-model="form.instrument_type" :error="form.errors.instrument_type"/>
                <editable-field label="Gestor do processo do sistema:" v-model="form.process_manager" />
                <editable-field label="Data da Solicitação da Dotação Orçamentária:" v-model="form.budget_allocation_request_date" type="date" />
            </div>
        </div>
        <div class="col-start-2 row-start-2 ">
            <EditableField
                v-model="form.total_notice_amount"
                label="Valor total do edital:"
                type="number"
                :format="(value) => new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(value)"
                :error="form.errors.total_notice_amount"
            />
          
            <div v-show="showAll" class="mt-2 space-y-1 transition-all duration-200 ease-in-out">
                <p><span class="font-bold">Valor por extenso: </span>{{ extenso(Number(form.total_notice_amount), { mode: 'currency' }) }}</p>
                <editable-field label="Email do gestor:" type="email" v-model="form.process_manager_email" />
                <editable-field label="N° Processo Cadastro do Credor:" v-model="form.creditor_registration_nup" />
            </div>
        </div>
        <div class="col-start-3 row-start-2">
            <p class="text-[#ffcc05FF] cursor-pointer" @click="showAll = !showAll">Mostrar todas as informações <v-icon size="18" :class="{ 'rotate-180': showAll }">mdi-chevron-down</v-icon></p>
            <div v-show="showAll" class="mt-2 space-y-1 transition-all duration-200 ease-in-out">
                <editable-field label="N° Parcelas:" v-model="form.installments" type="number" />
                <editable-field label="N° Processo Dotação Orçamentária:" v-model="form.budget_allocation_nup" mask="#####.######/####-##" />
                <editable-field label="Data da Solicitação do Cadastro do Credor:" v-model="form.creditor_registration_request_date" type="date"/>
            </div>
        </div>
    </div>
</template>