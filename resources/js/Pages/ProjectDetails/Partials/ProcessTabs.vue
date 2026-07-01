<script setup>
import { ref } from 'vue';
import OpeningTab from './Tabs/OpeningTab.vue';
import LegalAnalysisTab from './Tabs/LegalAnalysisTab.vue';
import FormalizationTab from './Tabs/FormalizationTab.vue';
import BudgetTab from './Tabs/BudgetTab.vue';
import MonitoringTab from './Tabs/MonitoringTab.vue';
import PaymentTab from './Tabs/PaymentTab.vue';

const props = defineProps({
    project: { type: Object, default: null },
    supervisorsAvailable: { type: Array, default: () => [] },
    agentStatus: { type: Array, default: () => [] },
    reportStatus: { type: Array, default: () => [] },
    deliberation: { type: Array, default: () => [] },
    accountType: { type: Array, default: () => [] },
    currentStage: { type: Object, default: null },
    canReturn: { type: Boolean, default: false },
    initialTab: { type: String, default: 'opening' },
});

const tab = ref(props.initialTab);

const tabs = [
    { value: 'opening', label: 'Abertura' },
    { value: 'legal-analysis', label: 'Análise jurídica' },
    { value: 'formalization', label: 'Formalização de processos' },
    { value: 'budget', label: 'Orçamento' },
    { value: 'payment', label: 'Pagamentos' },
    { value: 'monitoramento', label: 'Monitoramento' },
];
</script>

<template>
    <v-card class="w-full !shadow-none border border-gray-800 rounded-lg">
        <v-sheet elevation="2">
            <v-tabs v-model="tab" grow>
                <v-tab
                    v-for="item in tabs"
                    :key="item.value"
                    :value="item.value"
                    color="primary"
                    :class="{ '!bg-white': tab === item.value, '!bg-[#f5f5f5]': tab !== item.value }"
                    :data-cy="`${item.value}-tab`"
                >
                    {{ item.label }}
                </v-tab>
            </v-tabs>

            <v-tabs-window v-model="tab">
                <OpeningTab
                    :project="project"
                    :available-supervisors="supervisorsAvailable"
                    :agent-status="agentStatus"
                    :report-status="reportStatus"
                    :account-type="accountType"
                />

                <LegalAnalysisTab :project="project" :can-return="canReturn" :current-stage="currentStage" />

                <FormalizationTab
                    :project="project"
                    :can-return="canReturn"
                    :current-stage="currentStage"
                    :report-status="reportStatus"
                    :deliberation="deliberation"
                />

                <BudgetTab :project="project" :can-return="canReturn" :current-stage="currentStage" />

                <PaymentTab :project="project" :can-return="canReturn" :current-stage="currentStage" />

                <MonitoringTab :project="project" />
            </v-tabs-window>
        </v-sheet>
    </v-card>
</template>
