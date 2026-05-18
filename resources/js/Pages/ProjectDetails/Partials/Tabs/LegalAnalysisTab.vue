<script setup>
import { ref, toRef, onMounted } from 'vue';
import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import DocumentEvaluationList from '@/Components/DocumentEvaluationList.vue';
import { viewSections } from '@/Schemas/Opening';
import { useLegalAnalysis } from '@/Composables/useLegalAnalysis';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
});

const activeViewIndex = ref('all');

const { groups, statusOptions, loadingFiles, in_progress, allFilesEvaluated, fetchFiles, onStatusUpdated, process } =
    useLegalAnalysis(toRef(props, 'project'));

onMounted(fetchFiles);
</script>

<template>
    <SplitScreenTab value="legal-analysis">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <p class="font-bold text-lg">Dados disponíveis para consulta</p>
                    <p class="text-sm text-gray-600">Utilize os filtros abaixo para navegar entre os dados</p>
                </div>

                <SectionChips v-model="activeViewIndex" :sections="viewSections" />

                <div class="space-y-8">
                    <template v-for="(section, index) in viewSections" :key="'view-' + section.title">
                        <SectionContent
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
                <div class="flex items-center justify-between">
                    <p class="font-bold text-lg">Campos para você avaliar</p>
                </div>

                <div>
                    <p class="font-bold text-sm mb-2">Links auxiliares</p>
                    <AuxLinks />
                </div>

                <div>
                    <p class="font-bold text-sm mb-3">Avalie os documentos</p>

                    <!--                    <p>{{ groups }}</p>-->
                    <v-progress-linear v-if="loadingFiles" indeterminate color="primary" class="mb-4" />

                    <DocumentEvaluationList
                        v-else-if="groups.length > 0"
                        :groups="groups"
                        :project="project"
                        :status-options="statusOptions"
                        @status-updated="onStatusUpdated"
                    />

                    <p v-else class="text-sm text-gray-400">Nenhum documento encontrado para avaliação.</p>
                </div>

                <div class="flex justify-center pt-4">
                    <v-btn
                        class="w-1/2 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
                        :disabled="!allFilesEvaluated"
                        :loading="in_progress"
                        @click="process"
                    >
                        tramitar
                    </v-btn>
                </div>
            </div>
        </template>
    </SplitScreenTab>
</template>
