<script setup>
import { useAlert } from '@/Composables/useAlert';

const props = defineProps({
    action: {
        type: Function,
        required: true,
    },

    loading: {
        type: Boolean,
        default: false,
    },

    disabled: {
        type: Boolean,
        default: false,
    },

    phase: {
        type: String,
        default: '',
    },
});

const { showAlert } = useAlert();

const confirmTramit = () => {
    showAlert({
        alertTitle: 'Deseja realizar a tramitação?',
        alertMessage: 'As informações serão validadas e o processo será passado adiante, notificando os responsáveis.',
        confirmText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        action: props.action,
    });
};
</script>

<template>
    <v-btn
        class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
        height="40"
        :loading="props.loading"
        :disabled="props.disabled || props.loading"
        data-cy="tramit-button"
        @click="confirmTramit"
    >
        <slot>tramitar</slot>
    </v-btn>
</template>
