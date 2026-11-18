<script setup>
import SanitizedHtml from '@/Components/SanitizedHtml.vue';

defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Detalhes',
    },
    sentAt: {
        type: String,
        default: '',
    },
    messageLabel: {
        type: String,
        default: 'Mensagem enviada',
    },
    message: {
        type: String,
        default: '',
    },
    messageHtml: {
        type: Boolean,
        default: false,
    },
    cancelLabel: {
        type: String,
        default: 'Fechar',
    },
    confirmLabel: {
        type: String,
        default: 'Confirmar',
    },
    showConfirm: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const close = () => {
    emit('update:modelValue', false);
};

const confirm = () => {
    emit('confirm');
};
</script>

<template>
    <v-dialog :model-value="modelValue" max-width="900" @update:model-value="emit('update:modelValue', $event)">
        <v-card rounded="lg" class="pa-6">
            <v-card-title class="!text-2xl !font-bold !p-0 mb-4">
                {{ title }}
            </v-card-title>

            <v-card-text class="!p-0">
                <slot name="description" />

                <p v-if="sentAt" class="text-sm mb-8">Enviado {{ sentAt }}</p>

                <p class="text-base mb-2">
                    {{ messageLabel }}
                </p>

                <div class="border border-primary rounded-lg min-h-[260px] p-4 text-sm overflow-auto">
                    <SanitizedHtml
                        v-if="message && messageHtml"
                        :content="message"
                        class="max-w-none [&_p]:mb-3 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:font-bold [&_em]:italic"
                    />

                    <div v-else-if="message" class="whitespace-pre-line">
                        {{ message }}
                    </div>

                    <div v-else>Nenhuma mensagem informada.</div>
                </div>
            </v-card-text>

            <v-card-actions class="justify-end mt-6 !p-0">
                <v-btn variant="outlined" color="primary" class="!font-bold !rounded-lg" @click="close">
                    {{ cancelLabel }}
                </v-btn>

                <v-btn
                    v-if="showConfirm"
                    class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
