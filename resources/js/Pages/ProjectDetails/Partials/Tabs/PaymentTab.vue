<script setup>
import { computed, ref } from 'vue';
import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import { viewSections } from '@/Schemas/Payment';
import SectionContent from '@/Components/SectionContent.vue';
import SectionChips from '@/Components/SectionChips.vue';

const props = defineProps({
    project: {
        type: Object,
        default: () => ({}),
    },
    availableSupervisors: {
        type: Array,
        default: () => [],
    },
    agentStatus: {
        type: Array,
        default: () => [],
    },
    reportStatus: {
        type: Array,
        default: () => [],
    },
    accountType: {
        type: Array,
        default: () => [],
    },
});
const activeViewIndex = ref('all');
const activeEditIndex = ref('all');
</script>
<template>
    <split-screen-tab value="payment">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <p class="font-bold text-lg">Dados disponíveis para consulta</p>
                    <p class="text-sm text-gray-600">Utilize os filtros abaixo para navegar entre os dados</p>
                </div>
                <section-chips v-model="activeViewIndex" :sections="viewSections" show-all-option />
                <div class="mt-4 space-y-8">
                    <template v-for="(section, index) in viewSections" :key="'view-' + section.title">
                        <section-content
                            v-if="activeViewIndex === 'all' || activeViewIndex === index"
                            :section="section"
                            :project="project"
                        />
                    </template>
                </div>
            </div>
        </template>
        <template #right-content>
            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-bold text-lg">Campos para você inserir ou editar dados</p>
                            <p class="font-bold text-md mt-2 text-black">Links auxiliares</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </split-screen-tab>
</template>
