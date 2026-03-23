<script setup>
import extenso from 'extenso';
import { ref } from 'vue';

defineProps({
    notice: Object,
})

const showAll = ref(false);
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
                density="compact" rounded="lg" @click="clear">
                Atualizar Dados
            </v-btn>
        </div>
        <div class="col-start-1 row-start-2 flex flex-col justify-start">
            <p><span class="font-bold">Nº do processo mãe: </span>{{ notice.nup }}</p>
            <div v-show="showAll" class="mt-2 space-y-1 transition-all duration-200 ease-in-out">
                <p><span class="font-bold">Tipo de Instrumento: </span>{{ notice.instrument_type }}</p>
                <p><span class="font-bold">Gestor do processo do sistema: </span>{{ notice.process_manager }}</p>
                <p><span class="font-bold">Data da Solicitação da Dotação Orçamentária: </span>{{ notice.budget_allocation_request_date }}</p>
            </div>
        </div>
        <div class="col-start-2 row-start-2 ">
            <p><span class="font-bold">Valor total do edital: </span>
                {{ new Intl.NumberFormat('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    }).format(notice.total_notice_amount)}}
            </p>
            <div v-show="showAll" class="mt-2 space-y-1 transition-all duration-200 ease-in-out">
                <p><span class="font-bold">Valor por extenso: </span>{{ extenso(Number(notice.total_notice_amount), { mode: 'currency' }) }}</p>
                <p><span class="font-bold">Email do gestor do processo no sistema: </span>{{ notice.process_manager_email }}</p>
                <p><span class="font-bold">N° Processo Cadastro do Credor NUP: </span>{{ notice.creditor_registration_nup }}</p>
            </div>
        </div>
        <div class="col-start-3 row-start-2">
            <p class="text-[#ffcc05FF] cursor-pointer" @click="showAll = !showAll">Mostrar todas as informações <v-icon size="18" :class="{ 'rotate-180': showAll }">mdi-chevron-down</v-icon></p>
            <div v-show="showAll" class="mt-2 space-y-1 transition-all duration-200 ease-in-out">
                <p><span class="font-bold">N° Parcelas: </span>{{ notice.installments }}</p>
                <p><span class="font-bold">N° Processo Dotação Orçamentária NUP: </span>{{ notice.budget_allocation_nup }}</p>
                <p><span class="font-bold">Data da Solicitação do Cadastro do Credor: </span>{{ notice.creditor_registration_request_date }}</p>
            </div>
        </div>
    </div>
</template>