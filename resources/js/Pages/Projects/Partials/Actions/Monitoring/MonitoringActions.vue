<script setup>
import { computed, ref } from 'vue';
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';

const props = defineProps({
    selectedProjects: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

const viewHistory = ref(false);

const selectedProjectsList = computed(() => props.projects.filter((p) => props.selectedProjects.includes(p.id)));

const hasMonitoringReports = computed(() => selectedProjectsList.value.some((p) => p.monitoring != null));

function openNoticeHistory() {
    viewHistory.value = true;
}
</script>

<template>
    <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice?.id" />

    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg">Ações disponíveis para você</v-card-title>

        <v-card-text class="flex flex-col gap-4">
            <div class="w-full pt-2 flex flex-col gap-2">
                <p class="font-medium">Status</p>

                <v-card v-if="hasMonitoringReports" class="!shadow-none rounded-lg" color="success" variant="tonal">
                    <v-card-text class="flex items-start gap-2 text-sm">
                        <v-icon color="success" size="18" class="mt-0.5">mdi-check-circle</v-icon>
                        <span>
                            Relatórios de monitoramento disponíveis para consulta. Acesse os
                            <strong>agentes</strong> para conferir.
                        </span>
                    </v-card-text>
                </v-card>

                <v-card v-else class="!shadow-none rounded-lg" variant="outlined">
                    <v-card-text class="text-sm text-gray-600">
                        O relatório ainda não foi gerado ou as informações não foram carregadas
                    </v-card-text>
                </v-card>
            </div>

            <div class="w-full flex flex-col gap-1">
                <v-divider class="my-4" />

                <p>Conferir histórico de alterações nos processos</p>

                <v-btn variant="outlined" color="outlineSecondary" class="rounded-lg w-full" @click="openNoticeHistory">
                    Conferir histórico
                </v-btn>
            </div>
        </v-card-text>
    </v-card>
</template>
