<script setup>
import { computed, ref } from 'vue';
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import HandleDocumentsDialog from '../HandleDocumentsDialog.vue';
import DocumentListDialog from '../DocumentListDialog.vue';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    selectedProjects: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

defineEmits(['saved']);

const { canManagePayment } = usePermissions();

const viewHistory = ref(false);
const dispatchDialog = ref(false);
const docListDialog = ref(false);

const errorMessage = ref('');
const showError = ref(false);

const hasSelectedProjects = computed(() => {
    return props.selectedProjects?.length > 0;
});

const selectedProjectsList = computed(() => {
    return props.projects.filter((p) => props.selectedProjects.includes(p.id));
});

const selectedDocuments = computed(() =>
    selectedProjectsList.value.flatMap((p) =>
        (p.documents ?? []).filter((d) => d.type?.toLowerCase() === 'dp').map((d) => ({ ...d, project: p }))
    )
);

const anyProjectsHasDispatch = computed(() => {
    return selectedProjectsList.value.some((p) => p.documents?.some((d) => d.type?.toLowerCase() === 'dp'));
});

const selectedDispatch = computed(() => {
    const projectWithDispatch = selectedProjectsList.value.find((p) =>
        p.documents?.some((d) => d.type?.toLowerCase() === 'dp')
    );

    if (!projectWithDispatch) return null;

    const dispatchDocument = projectWithDispatch.documents.find((d) => d.type?.toLowerCase() === 'dp');

    return {
        content: dispatchDocument.body,
        headerImages: (dispatchDocument.images ?? []).filter(
            (i) => i.section === 'header' || i.section?.value === 'header'
        ),
        footerImages: (dispatchDocument.images ?? []).filter(
            (i) => i.section === 'footer' || i.section?.value === 'footer'
        ),
    };
});

const canCreateDispatch = computed(() => {
    return canManagePayment.value;
});

function openNoticeHistory() {
    viewHistory.value = true;
}

function openDispatchDialog() {
    if (!hasSelectedProjects.value || !canCreateDispatch.value) {
        return;
    }

    dispatchDialog.value = true;
}
</script>

<template>
    <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice?.id" />

    <HandleDocumentsDialog
        v-model="dispatchDialog"
        type="dp"
        :project-ids="selectedProjects"
        :edit-data="selectedDispatch"
        @saved="$emit('saved')"
    />

    <DocumentListDialog v-model="docListDialog" :documents="selectedDocuments" />

    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg"> Ações disponíveis para você </v-card-title>

        <v-card-text class="flex flex-col gap-4">
            <div
                v-permission="{
                    condition: canCreateDispatch,
                    message:
                        'Você não tem permissão para criar ou editar despacho, contate o administrador do sistema.',
                }"
                class="w-full pt-2 flex flex-col gap-1"
            >
                <template v-if="anyProjectsHasDispatch">
                    <p>Editar Despacho de Pagamento (DP)</p>

                    <v-btn
                        class="w-full !shadow-none !font-bold !border-gray-300 !bg-white !text-[#2d353fFF] rounded-lg text-xs gap-6"
                        :disabled="!hasSelectedProjects || !canCreateDispatch"
                        variant="outlined"
                        @click="openDispatchDialog"
                    >
                        <span class="w-full text-left">Editar Despacho de Pagamento (DP)</span>

                        <template #append>
                            <v-icon size="18">mdi-pencil</v-icon>
                        </template>
                    </v-btn>
                </template>

                <template v-else>
                    <p>Criar Despacho de Pagamento</p>

                    <v-btn
                        class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                        :disabled="!hasSelectedProjects || !canCreateDispatch"
                        @click="openDispatchDialog"
                    >
                        Criar
                    </v-btn>
                </template>
            </div>

            <div class="w-full flex flex-col gap-1">
                <v-divider class="my-4" />

                <p>Conferir histórico de alterações nos processos</p>

                <v-btn variant="outlined" color="outlineSecondary" class="rounded-lg w-full" @click="openNoticeHistory">
                    Conferir Histórico
                </v-btn>
            </div>
        </v-card-text>
    </v-card>

    <v-snackbar v-model="showError" color="error" timeout="4000">
        {{ errorMessage }}
    </v-snackbar>
</template>
