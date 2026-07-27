<script setup>
import { computed, ref } from 'vue';
import { useAlert } from '@/Composables/useAlert';
import ReturnProcessModal from '@/Components/ReturnProcessModal.vue';

const props = defineProps({
    project: { type: Object, required: true },
    currentStage: { type: Object, default: null },
    canReturn: { type: Boolean, default: false },
    stageSlug: { type: String, required: true },
    canUserHandle: { type: Boolean, default: false },
});

const { showAlert } = useAlert();

const showReturnModal = ref(false);

const stage = computed(() => {
    return props.project.stages?.find((projectStage) => projectStage.slug === props.stageSlug);
});

const isCurrentStage = computed(() => {
    return Boolean(props.currentStage && stage.value && props.currentStage.id === stage.value.id);
});

const cannotReturn = computed(() => {
    return !isCurrentStage.value || !props.canReturn || !props.canUserHandle || stage.value?.status === 'aprovado';
});

const returnPermissionMessage = computed(() => {
    if (!props.canUserHandle) {
        return 'Usuário não tem permissão para devolver o processo.';
    }

    if (stage.value?.status === 'aprovado') {
        return 'O processo já foi tramitado.';
    }

    if (!isCurrentStage.value) {
        return 'O processo não está atualmente nesta fase.';
    }

    if (!props.canReturn) {
        return 'Não é possível devolver o processo nesta fase.';
    }

    return '';
});

function showReturnBlockedMessage() {
    if (!cannotReturn.value) {
        return;
    }

    showAlert({
        alertTitle: 'Não é possível devolver o processo',
        alertMessage: returnPermissionMessage.value,
        confirmText: 'Entendi',
    });
}

function openReturnModal() {
    if (cannotReturn.value) {
        showReturnBlockedMessage();
        return;
    }

    showReturnModal.value = true;
}
</script>

<template>
    <div :class="{ 'cursor-not-allowed': cannotReturn }" @click="showReturnBlockedMessage">
        <v-btn
            :disabled="cannotReturn"
            class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
            :class="{ 'pointer-events-none': cannotReturn }"
            @click.stop="openReturnModal"
        >
            DEVOLVER PROCESSO
        </v-btn>
    </div>

    <ReturnProcessModal
        v-if="canReturn && isCurrentStage"
        v-model="showReturnModal"
        :project-id="project.id"
        :stage-id="currentStage.id"
    />
</template>
