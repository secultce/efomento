<script setup>
import { computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useSnackbar } from '@/Composables/useSnackbar';
import AppTextEditor from '@/Components/AppTextEditor.vue';
import { returnProcess } from '@/Services/projectService';

const { showSnackbar } = useSnackbar();
const page = usePage();

const props = defineProps({
    modelValue: Boolean,
    projectId: { type: Number, required: true },
    stageId: { type: Number, required: true },
});
const emit = defineEmits(['update:modelValue', 'returned']);

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const form = useForm({ reason: '' });

const handleReturn = () => {
    returnProcess(
        props.projectId,
        props.stageId,
        { reason: form.reason },
        {
            onSuccess: () => {
                const flash = page.props.flash;
                if (flash?.error) {
                    showSnackbar(flash.error, 'error');
                    return;
                }
                showSnackbar('Processo devolvido com sucesso!', 'success');
                emit('returned');
                close();
            },
            onError: (errors) => {
                const msg = Object.values(errors).flat().join(', ') || 'Ocorreu um erro ao devolver o processo.';
                showSnackbar(msg, 'error');
            },
            onFinish: () => {
                router.visit(window.location.pathname, {
                    preserveState: false,
                    preserveScroll: true,
                });
            },
        }
    );
};

const close = () => {
    form.reset();
    form.clearErrors();
    isOpen.value = false;
};

watch(
    () => isOpen.value,
    (open) => {
        if (!open) form.reset();
    }
);
</script>

<template>
    <v-dialog v-model="isOpen" max-width="800" :retain-focus="false" persistent>
        <v-card class="rounded-lg d-flex flex-column" height="600">
            <v-card-title class="font-weight-bold flex-shrink-0"> Devolva o processo para ajustes </v-card-title>
            <v-container class="flex-grow-1 d-flex flex-column pa-4 min-h-0">
                <p class="text-sm text-gray-600 mb-3">
                    Informe o motivo da devolução <span class="text-red-500">*</span>
                </p>
                <app-text-editor v-model="form.reason" label="" :error="form.errors.reason" class="flex-grow-1" />
                <v-card-actions class="flex-shrink-0 mt-2">
                    <v-spacer />
                    <v-btn variant="outlined" color="#004c27" class="rounded-lg" @click="close"> Cancelar </v-btn>
                    <v-btn
                        class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg"
                        :loading="form.processing"
                        :disabled="!form.reason.trim()"
                        data-cy="return-process-sent-button"
                        @click="handleReturn"
                    >
                        Enviar
                    </v-btn>
                </v-card-actions>
            </v-container>
        </v-card>
    </v-dialog>
</template>
