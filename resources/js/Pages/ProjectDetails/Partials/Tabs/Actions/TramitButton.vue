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

const { open } = useAlert();

const handleClick = () => {
    open({
        alertTitle: 'Tarefa marcada como tramitada',
        alertMessage: 'As informações foram validadas e as pessoas envolvidas nesse processo foram notificadas.',
        confirmText: 'entendi',
        action: () => {
            console.log('confirmed');
        },
    });
};
</script>
<template>
    <div class="w-full justify-center flex">
        <v-btn
            class="w-1/2 mt-4 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
            :loading="loading"
            :disabled="disabled || loading"
            @click="handleClick"
        >
            <slot> tramitar </slot>
        </v-btn>
    </div>
</template>
