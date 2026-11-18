<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    edital: {
        type: Object,
        required: true,
    },
    projeto: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <template #subheader>
            <p class="text-white text-h6 mb-1">{{ edital.nome }}</p>

            <div class="d-flex gap-6 mt-2">
                <div>
                    <span class="text-caption" style="color: rgba(255,255,255,0.7);">Ano</span>
                    <p class="text-white text-body-2 ma-0">{{ edital.ano }}</p>
                </div>
                <div v-if="projeto">
                    <span class="text-caption" style="color: rgba(255,255,255,0.7);">Processo</span>
                    <p class="text-white text-body-2 ma-0">{{ projeto.numero_processo }}</p>
                </div>
                <div v-if="projeto">
                    <span class="text-caption" style="color: rgba(255,255,255,0.7);">Agente</span>
                    <p class="text-white text-body-2 ma-0">{{ projeto.agent_name }}</p>
                </div>
            </div>
        </template>

        <v-container class="py-6">
            <slot />
        </v-container>
    </AuthenticatedLayout>
</template>
