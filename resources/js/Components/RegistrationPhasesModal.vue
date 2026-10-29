<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useRegistrationFields, REGISTRATION_STATUS_LABELS } from '@/Composables/useRegistrationFields';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    phases: { type: Array, default: () => [] },
    totalPhases: { type: Number, default: null },
});

const emit = defineEmits(['update:modelValue']);

const page = usePage();
const { formatField, formatDate } = useRegistrationFields(() => page.props.mapasDomain);

const open = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Fases publicadas em que o projeto ainda não tem inscrição aparecem como "sem inscrição".
const missingPhases = computed(() => Math.max(0, (props.totalPhases ?? 0) - props.phases.length));

const statusLabel = (status) => REGISTRATION_STATUS_LABELS[status] ?? '—';
const sections = (phase) => phase.data?.sections ?? [];
</script>

<template>
    <v-dialog v-model="open" max-width="900" scrollable>
        <v-card class="rounded-lg d-flex flex-column" max-height="90vh">
            <v-card-title class="pa-4">Dados completos do proponente</v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
                <p v-if="!phases.length" class="text-sm text-gray-500">Nenhum dado de inscrição disponível.</p>

                <div v-for="phase in phases" :key="phase.id" class="border rounded-lg p-4 mb-4">
                    <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
                        <p class="font-bold">{{ phase.data?.opportunity?.name ?? `Fase ${phase.phase_order}` }}</p>
                        <div class="text-xs text-gray-700 text-right">
                            <p>
                                Status: <strong>{{ statusLabel(phase.status) }}</strong>
                            </p>
                            <p>
                                Enviada em: <strong>{{ formatDate(phase.sent_at) }}</strong>
                            </p>
                        </div>
                    </div>

                    <div v-for="(section, sIndex) in sections(phase)" :key="sIndex" class="border-t pt-3 mt-3">
                        <p class="font-bold uppercase text-xs tracking-wider mb-2">{{ section.title }}</p>
                        <div v-for="(field, fIndex) in section.fields ?? []" :key="fIndex" class="mb-2">
                            <p class="text-xs text-gray-700">{{ field.title }}</p>
                            <p class="text-sm font-bold break-words">
                                <template v-for="(item, iIndex) in formatField(field)" :key="iIndex">
                                    <span v-if="iIndex > 0">, </span>
                                    <a
                                        v-if="item.href"
                                        :href="item.href"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-blue-600 hover:underline"
                                        >{{ item.text }}</a
                                    >
                                    <span v-else>{{ item.text }}</span>
                                </template>
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-for="n in missingPhases"
                    :key="'missing-' + n"
                    class="border rounded-lg p-4 mb-4 text-sm text-gray-500"
                >
                    Fase {{ phases.length + n }}: sem inscrição
                </div>
            </v-card-text>
            <v-divider />
            <v-card-actions class="pa-4 justify-end">
                <v-btn variant="outlined" @click="open = false">Fechar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
