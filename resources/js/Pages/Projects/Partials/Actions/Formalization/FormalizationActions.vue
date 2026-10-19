<script setup>
import { ref } from 'vue';

import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import DocumentActionsList from '../DocumentActionsList.vue';

import { DOCUMENT_TYPES } from '@/Schemas/Config/documentConfig.js';
import { usePermissions } from '@/Composables/usePermissions';

const { canManageFormalization } = usePermissions();

defineProps({
    selectedProjects: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

const documentTypes = [DOCUMENT_TYPES.TC, DOCUMENT_TYPES.ET, DOCUMENT_TYPES.PJ];

const viewHistory = ref(false);

function openNoticeHistory() {
    viewHistory.value = true;
}
</script>

<template>
    <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice?.id" />

    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg"> Ações disponíveis para você </v-card-title>

        <v-card-text class="flex flex-col gap-4">
            <div class="w-full pt-2 flex flex-col gap-6">
                <DocumentActionsList
                    :document-types="documentTypes"
                    :selected-projects="selectedProjects"
                    :projects="projects"
                    :can-manage="canManageFormalization"
                />

                <div class="w-full flex flex-col gap-1">
                    <v-divider class="my-4" />

                    <p>Conferir histórico de alterações nos processos</p>

                    <v-btn
                        variant="outlined"
                        color="outlineSecondary"
                        class="rounded-lg w-full"
                        @click="openNoticeHistory"
                    >
                        Conferir Histórico
                    </v-btn>
                </div>
            </div>
        </v-card-text>
    </v-card>
</template>
