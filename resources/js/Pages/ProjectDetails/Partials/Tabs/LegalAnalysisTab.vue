<script setup>
import { ref, computed, toRef, onMounted } from 'vue';
import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import DocumentEvaluationList from '@/Components/DocumentEvaluationList.vue';
import ReturnProcessAction from '@/Components/ReturnProcessAction.vue';
import TramitButton from '@/Pages/ProjectDetails/Partials/Tabs/Actions/TramitButton.vue';
import { viewSections } from '@/Schemas/Opening';
import { useLegalAnalysis } from '@/Composables/useLegalAnalysis';
import { useStageAdvance } from '@/Composables/useStageAdvance';

const props = defineProps({
    project: { type: Object, required: true },
    canReturn: { type: Boolean, default: false },
    currentStage: { type: Object, default: null },
    canAdvance: { type: Boolean, default: false },
});

const STAGE_SLUG = 'analise_juridica';

const { canUserHandle: canUserHandleLegalAnalysis } = useStageAdvance(props, STAGE_SLUG);

const activeViewIndex = ref('all');

const { groups, statusOptions, loadingFiles, in_progress, allFilesValid, fetchFiles, onStatusUpdated, process } =
    useLegalAnalysis(toRef(props, 'project'));
onMounted(fetchFiles);

const stage = computed(() => props.project.stages?.find((s) => s.slug === STAGE_SLUG));

const canEditLegalAnalysis = computed(() => canUserHandleLegalAnalysis.value && stage.value?.status === 'em_andamento');

const permissionMessage = computed(() => {
    if (stage.value?.status === 'aprovado') {
        return 'Projeto já foi tramitado e não está mais na fase de Análise Jurídica.';
    }

    if (!canUserHandleLegalAnalysis.value && stage.value?.status === 'em_andamento') {
        return 'Usuário não tem permissão para fazer alterações na Análise Jurídica';
    }

    if (stage.value?.status !== 'aprovado' && stage.value?.status !== 'em_andamento') {
        return 'Este projeto não está disponível nessa fase no momento.';
    }

    return '';
});
</script>

<template>
    <SplitScreenTab value="legal-analysis">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <div class="font-bold text-lg d-flex justify-between">
                        <span>Dados disponíveis para consulta</span>

                        <ReturnProcessAction
                            :project="project"
                            :current-stage="currentStage"
                            :can-return="canReturn"
                            :stage-slug="STAGE_SLUG"
                            :can-user-handle="canUserHandleLegalAnalysis"
                        />
                    </div>

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

                <div
                    v-permission="{
                        condition: canEditLegalAnalysis,
                        message: permissionMessage,
                    }"
                    class="mt-4"
                >
                    <div>
                        <p class="font-bold text-sm mb-3">Avalie os documentos</p>
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

                    <TramitButton
                        v-permission="{
                            condition: canEditLegalAnalysis,
                            message: permissionMessage,
                        }"
                        :action="process"
                        :disabled="!canEditLegalAnalysis || !allFilesValid"
                        :loading="in_progress"
                    />
                </div>
            </div>
        </template>
    </SplitScreenTab>
</template>
