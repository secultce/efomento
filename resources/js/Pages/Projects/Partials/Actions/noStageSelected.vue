<script setup>
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    notice: {
        type: Object,
        default: null,
    },
});

const viewHistory = ref(false);

const noticeId = computed(() => props.notice?.id ?? null);

function openNoticeHistory() {
    if (!noticeId.value) {
        return;
    }

    viewHistory.value = true;
}
</script>

<template>
    <v-card class="w-full rounded-lg border border-gray-800 py-4 !shadow-none">
        <v-card-title class="!text-lg font-weight-bold"> Ações disponíveis para você </v-card-title>

        <v-card-text class="flex flex-col gap-4">
            <div class="flex w-full flex-col gap-1 pt-2">
                <p class="text-body-2">
                    Escolha um itens no filtro acima para mostrar as ações disponíveis para cada fase.
                </p>
            </div>

            <div class="flex w-full flex-col gap-2">
                <v-divider class="my-4" />

                <p>Conferir histórico de alterações nos processos</p>

                <v-btn
                    variant="outlined"
                    color="outlineSecondary"
                    class="w-full rounded-lg"
                    :disabled="!noticeId"
                    @click="openNoticeHistory"
                >
                    Conferir histórico
                </v-btn>
            </div>
        </v-card-text>

        <NoticeHistoryDialog v-if="noticeId" v-model="viewHistory" :notice-id="noticeId" />
    </v-card>
</template>
