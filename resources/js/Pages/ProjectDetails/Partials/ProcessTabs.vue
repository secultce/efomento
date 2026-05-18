<script setup>
import { ref } from 'vue';
import OpeningTab from './Tabs/OpeningTab.vue';
import LegalAnalysisTab from './Tabs/LegalAnalysisTab.vue';
import ReturnProcessModal from '@/Components/ReturnProcessModal.vue';

defineProps({
    project: { type: Object, default: null },
    supervisorsAvailable: { type: Array, default: () => [] },
    agentStatus: { type: Array, default: () => [] },
    reportStatus: { type: Array, default: () => [] },
    accountType: { type: Array, default: () => [] },
    currentStage: { type: Object, default: null },
    canReturn: { type: Boolean, default: false },
});

const tab = ref('1');
const showReturnModal = ref(false);

const tabs = [
    { value: 'opening', label: 'Abertura' },
    { value: 'legal-analysis', label: 'Análise jurídica' },
    { value: '3', label: 'Formalização de processos' },
    { value: '4', label: 'Orçamento e parcela' },
    { value: '5', label: 'Pagamentos' },
    { value: '6', label: 'Monitoramento' },
];
</script>

<template>
    <v-card class="w-full !shadow-none border border-gray-800 rounded-lg">
        <p>{{ canReturn }}</p>
        <div v-if="canReturn" class="flex justify-end px-4 pt-3">
            <v-btn color="error" variant="outlined" class="rounded-lg font-bold" @click="showReturnModal = true">
                DEVOLVER PROCESSO
            </v-btn>
        </div>

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

                <LegalAnalysisTab :project="project" />

                <v-tabs-window-item value="3">
                    <v-sheet class="pa-5 h-[35em]" color="brown" />
                </v-tabs-window-item>

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

        <ReturnProcessModal
            v-if="canReturn && currentStage"
            v-model="showReturnModal"
            :project-id="project.id"
            :stage-id="currentStage.id"
        />
    </v-card>
</template>
