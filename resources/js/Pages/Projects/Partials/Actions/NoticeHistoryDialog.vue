<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/pt-br';

import { formatAudits } from '@/Composables/useAuditFormatter';

dayjs.extend(relativeTime);
dayjs.locale('pt-br');

const props = defineProps({
    modelValue: Boolean,
    noticeId: {
        type: [Number, String],
        required: true,
    },
});

const emit = defineEmits(['update:modelValue']);

const audits = ref([]);
const loading = ref(false);

function close() {
    emit('update:modelValue', false);
}

function formatRelativeDate(date) {
    return dayjs(date).fromNow();
}

async function loadHistory() {
    if (!props.noticeId) return;

    loading.value = true;

    try {
        const { data } = await axios.get(`/editais/${props.noticeId}/audits`);

        audits.value = formatAudits(data.data);
    } catch (e) {
        console.error(e);
        audits.value = [];
    } finally {
        loading.value = false;
    }
}

watch(
    () => props.modelValue,
    async (opened) => {
        if (opened) {
            await loadHistory();
        }
    }
);
</script>

<template>
    <v-dialog
        :model-value="modelValue"
        max-width="900"
        scrollable
        @update:model-value="emit('update:modelValue', $event)"
    >
        <v-card class="rounded-lg overflow-hidden">
            <v-card-title class="px-6 py-4">
                <div class="flex items-center w-full">
                    <span class="text-lg font-bold"> Histórico de alterações </span>

                    <v-spacer />

                    <v-btn icon variant="text" @click="close">
                        <v-icon> mdi-close </v-icon>
                    </v-btn>
                </div>
            </v-card-title>

            <v-divider />

            <v-card-text class="p-0 max-h-[80vh] overflow-y-auto">
                <div v-if="loading" class="flex justify-center py-10">
                    <v-progress-circular indeterminate />
                </div>

                <div v-else-if="audits.length === 0" class="p-6 text-gray-500">Nenhuma alteração encontrada.</div>

                <div v-else class="flex flex-col">
                    <div v-for="audit in audits" :key="audit.id" class="py-4 px-6 border-b border-gray-200">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center shrink-0">
                                <v-icon size="16"> mdi-account </v-icon>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm mb-1">
                                    <strong>
                                        {{ audit.user }}
                                    </strong>

                                    {{ audit.action }}

                                    os valores dos seguintes campos no edital:
                                </div>

                                <ul v-if="audit.changes.length" class="text-xs text-gray-700 space-y-1 ml-6">
                                    <li v-for="(change, i) in audit.changes" :key="i">
                                        <strong> {{ change.label }}: </strong>

                                        <span class="ml-1">
                                            {{ change.from }}
                                        </span>

                                        <span class="mx-1"> → </span>

                                        <span class="font-medium">
                                            {{ change.to }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            <div class="text-xs text-gray-500 whitespace-nowrap">
                                {{ formatRelativeDate(audit.date) }}
                            </div>
                        </div>
                    </div>
                </div>
            </v-card-text>

            <v-divider />

            <v-card-actions class="justify-end px-6 py-4">
                <v-btn class="!shadow-none !bg-[#ffcc05FF] !font-bold !text-xs rounded-lg" @click="close">
                    Fechar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
