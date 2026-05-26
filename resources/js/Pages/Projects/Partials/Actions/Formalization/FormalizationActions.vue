<script setup>
import { ref } from 'vue';
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import HandleTCDialog from './HandleTCDialog.vue';

const props = defineProps({
    selectedProjects: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

const viewHistory = ref(false);

const TCDialog = ref(null);

function openTCDialog() {
    TCDialog.value = true;
}

function openNoticeHistory() {
    viewHistory.value = true;
}
</script>
<template>
    <HandleTCDialog v-model="TCDialog" :project-ids="selectedProjects" />
    <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice.id" />
    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg">Ações disponíveis para você </v-card-title>
        <v-card-text class="flex flex-col gap-4">
            <div class="w-full pt-2 flex flex-col gap-1">
                <p>Termo de execução cultural</p>
                <v-btn
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="!(props.selectedProjects?.length > 0)"
                    @click="openTCDialog"
                >
                    Criar termo para assinatura
                </v-btn>
                <p class="mt-4">Extrato do termo</p>
                <v-btn
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="!(props.selectedProjects?.length > 0)"
                >
                    Criar extrato do termo
                </v-btn>
                <p class="mt-4">Criar parecer jurídico geral</p>
                <v-btn
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="!(props.selectedProjects?.length > 0)"
                >
                    Criar parecer jurídico
                </v-btn>
                <div class="w-full flex flex-col gap-1">
                    <v-divider class="my-4"></v-divider>

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
