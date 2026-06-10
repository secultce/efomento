<script setup>
import { ref } from 'vue';
import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import { viewSections, formSections } from '@/Schemas/Monitoring';

defineProps({
    project: {
        type: Object,
        default: () => ({}),
    },
});

const activeViewIndex = ref('all');
const activeFormIndex = ref(0);
</script>

<template>
    <split-screen-tab value="monitoramento">
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
                    <p class="font-bold text-lg">Campos para você inserir ou editar dados</p>
                    <p class="font-bold text-md mt-2 text-black">Links auxiliares</p>
                </div>
                <aux-links />
                <section-chips v-model="activeFormIndex" :sections="formSections" />
                <div class="mt-4 text-sm text-gray-500 italic">Formulário de edição disponível em breve.</div>
            </div>
        </template>
    </split-screen-tab>
</template>
