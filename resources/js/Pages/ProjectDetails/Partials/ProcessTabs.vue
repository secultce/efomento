<script setup>
import { ref } from 'vue';
import OpeningTab from './Tabs/OpeningTab.vue';
import LegalAnalysisTab from './Tabs/LegalAnalysisTab.vue';
import FormalizationTab from './Tabs/FormalizationTab.vue';

defineProps({
    project: { type: Object, default: null },
    supervisorsAvailable: { type: Array, default: () => [] },
    agentStatus: { type: Array, default: () => [] },
    reportStatus: { type: Array, default: () => [] },
    termStatus: { type: Array, default: () => [] },
    deliberation: { type: Array, default: () => [] },
    accountType: { type: Array, default: () => [] },
    currentStage: { type: Object, default: null },
    canReturn: { type: Boolean, default: false },
});

const tab = ref('opening');

const tabs = [
    { value: 'opening', label: 'Abertura' },
    { value: 'legal-analysis', label: 'Análise jurídica' },
    { value: 'formalization', label: 'Formalização de processos' },
    { value: '4', label: 'Orçamento e parcela' },
    { value: '5', label: 'Pagamentos' },
    { value: '6', label: 'Monitoramento' },
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
                    :term-status="termStatus"
                    :deliberation="deliberation"
                />

                <v-tabs-window-item value="4">
                    <v-sheet class="pa-5 h-[35em]" color="green" />
                </v-tabs-window-item>

                <v-tabs-window-item value="5">
                    <v-sheet class="pa-5 h-[35em]" color="blue" />
                </v-tabs-window-item>

                <v-tabs-window-item value="6">
                    <v-sheet class="pa-5 h-[35em]" color="red" />
                </v-tabs-window-item>
            </v-tabs-window>
        </v-sheet>
    </v-card>
</template>
