<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import HandleDocumentsDialog from '../HandleDocumentsDialog.vue';
import DocumentListDialog from '../DocumentListDialog.vue';
import { useSnackbar } from '@/Composables/useSnackbar.js';
import { useAuth } from '@/Composables/useAuth.js';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    selectedProjects: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

defineEmits(['saved']);

const { canPerform } = useAuth();
const { canManagePayment } = usePermissions();

const viewHistory = ref(false);
const dispatchDialog = ref(false);
const docListDialog = ref(false);

const uploadInput = ref(null);
const uploadingInstallment = ref(null);

const errorMessage = ref('');
const showError = ref(false);

const { showSnackbar } = useSnackbar();

const hasSelectedProjects = computed(() => {
    return props.selectedProjects?.length > 0;
});

const selectedProjectsList = computed(() => {
    return props.projects.filter((p) => props.selectedProjects.includes(p.id));
});

const selectedDocuments = computed(() =>
    selectedProjectsList.value.flatMap((p) =>
        (p.documents ?? []).filter((d) => d.type?.toLowerCase() === 'd').map((d) => ({ ...d, project: p }))
    )
);

const anyProjectsHasDispatch = computed(() => {
    return selectedProjectsList.value.some((p) => p.documents?.some((d) => d.type?.toLowerCase() === 'd'));
});

const selectedDispatch = computed(() => {
    const projectWithDispatch = selectedProjectsList.value.find((p) =>
        p.documents?.some((d) => d.type?.toLowerCase() === 'd')
    );

    if (!projectWithDispatch) return null;

    const dispatchDocument = projectWithDispatch.documents.find((d) => d.type?.toLowerCase() === 'd');

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

const canImportPayments = canManagePayment;

const canCreateDispatch = computed(() => {
    return canManagePayment.value || canPerform('dispatch.create');
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

function openUpload() {
    if (!hasSelectedProjects.value || uploadingInstallment.value !== null || !canImportPayments.value) {
        return;
    }

    uploadInput.value?.click();
}

async function handleFileUpload(event) {
    const file = event.target.files?.[0];

    if (!file || !canImportPayments.value) {
        event.target.value = '';
        return;
    }

    const formData = new FormData();

    formData.append('file', file);

    props.selectedProjects.forEach((projectId) => {
        formData.append('selectedProjects[]', projectId);
    });

    router.post(route('installments.import', props.notice.id), formData, {
        forceFormData: true,
        preserveScroll: true,

        onStart: () => {
            showSnackbar('Importando planilha, aguarde...', 'warning', -1);
        },

        onSuccess: (page) => {
            if (page.props.flash?.error) {
                showSnackbar(page.props.flash.error, 'error');
                return;
            }

            if (page.props.flash?.success) {
                showSnackbar(page.props.flash.success, 'success');
            }
        },

        onError: (errors) => {
            const message = Object.values(errors).flat().join(', ') || 'Ocorreu um erro ao importar a planilha';

            showSnackbar(message, 'error');
        },

        onFinish: () => {
            uploadingInstallment.value = null;
            event.target.value = '';
        },
    });
}
</script>

<template>
    <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice?.id" />

    <HandleDocumentsDialog
        v-model="dispatchDialog"
        type="d"
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
                    condition: canImportPayments,
                    message: 'Você não tem permissão para importar pagamentos, contate o administrador do sistema.',
                }"
                class="w-full pt-2"
            >
                <p>Fazer upload de planilha de pagamentos</p>

                <input
                    ref="uploadInput"
                    type="file"
                    accept=".xlsx,.xls,.csv"
                    class="hidden"
                    @change="handleFileUpload"
                />

                <div class="w-full pt-2">
                    <v-btn
                        class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-2 py-2 text-[11px] w-full"
                        :loading="uploadingInstallment === 1"
                        :disabled="!hasSelectedProjects || uploadingInstallment !== null || !canImportPayments"
                        @click="openUpload()"
                    >
                        Fazer upload do pagamento
                    </v-btn>
                </div>
            </div>

            <div
                v-permission="{
                    condition: canCreateDispatch,
                    message:
                        'Você não tem permissão para criar ou editar despacho, contate o administrador do sistema.',
                }"
                class="w-full pt-2 flex flex-col gap-1"
            >
                <template v-if="anyProjectsHasDispatch">
                    <p>Editar Despacho (D)</p>

                    <v-btn
                        class="w-full !shadow-none !font-bold !border-gray-300 !bg-white !text-[#2d353fFF] rounded-lg text-xs gap-6"
                        :disabled="!hasSelectedProjects || !canCreateDispatch"
                        variant="outlined"
                        @click="openDispatchDialog"
                    >
                        <span class="w-full text-left">Editar Despacho (D)</span>

                        <template #append>
                            <v-icon size="18">mdi-pencil</v-icon>
                        </template>
                    </v-btn>
                </template>

                <template v-else>
                    <p>Criar Despacho</p>

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
