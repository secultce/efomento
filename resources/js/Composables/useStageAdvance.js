import { computed } from 'vue';

export function useStageAdvance(props, stageSlug) {
    const isCurrentStage = computed(() => props.currentStage?.slug === stageSlug);

    const canUserHandle = computed(() => props.canAdvance && isCurrentStage.value);

    return {
        isCurrentStage,
        canUserHandle,
    };
}
