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
    <div class="w-full justify-center flex">
        <v-btn
            class="w-1/2 mt-4 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
            :loading="props.loading"
            :disabled="props.disabled || props.loading"
            @click="confirmTramit"
        >
            <slot>tramitar</slot>
        </v-btn>
    </div>
</template>
