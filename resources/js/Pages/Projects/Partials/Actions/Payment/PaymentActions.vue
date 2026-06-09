<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import HandleDocumentsDialog from '../HandleDocumentsDialog.vue';
import DocumentListDialog from '../DocumentListDialog.vue';

const props = defineProps({
    selectedProjects: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

const viewHistory = ref(false);
const poDialog = ref(false);
const docListDialog = ref(false);

function openNoticeHistory() {
    viewHistory.value = true;
}

const errorMessage = ref('');
const showError = ref(false);

const selectedProjectsList = computed(() => {
    return props.projects.filter((p) => props.selectedProjects.includes(p.id));
});

const selectedDocuments = computed(() =>
    selectedProjectsList.value.flatMap((p) =>
        (p.documents ?? []).filter((d) => d.type?.toLowerCase() === 'po').map((d) => ({ ...d, project: p }))
    )
);

const anyProjectsHasPO = computed(() => {
    return selectedProjectsList.value.some((p) => p.documents?.some((d) => d.type === 'po'));
});

const selectedPO = computed(() => {
    const projectWithPO = selectedProjectsList.value.find((p) => p.documents?.some((d) => d.type === 'po'));

    if (!projectWithPO) return null;

    const poDocument = projectWithPO.documents.find((d) => d.type === 'po');

    return {
        content: poDocument.body,
        headerImages: (poDocument.images ?? []).filter((i) => i.section === 'header' || i.section?.value === 'header'),
        footerImages: (poDocument.images ?? []).filter((i) => i.section === 'footer' || i.section?.value === 'footer'),
    };
});

function openPODialog() {
    poDialog.value = true;
}

const uploadInput = ref(null);

function openUpload() {
    uploadInput.value?.click();
}

async function handleFileUpload(event, installmentNumber) {
    const file = event.target.files?.[0];

    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('installment', installmentNumber);

    router.post(route('installments.import', props.notice.id), formData, {
        forceFormData: true,
    });

    event.target.value = '';
}
</script>

<template>
    <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice?.id" />
    <HandleDocumentsDialog
        v-model="poDialog"
        type="po"
        :project-ids="selectedProjects"
        :edit-data="selectedPO"
        @saved="$emit('saved')"
    />
    <DocumentListDialog v-model="docListDialog" :documents="selectedDocuments" />
    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg"> Ações disponíveis para você </v-card-title>

        <v-card-text class="flex flex-col gap-4">
            <div class="w-full pt-2">
                <p>Fazer upload de planilha de pagamentos</p>
                <div class="w-full pt-2">
                    <div class="flex flex-wrap gap-2">
                        <template v-for="n in props.notice?.installments || 0" :key="n">
                            <input
                                :ref="(el) => (uploadInput = el)"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                class="hidden"
                                @change="(e) => handleFileUpload(e, n)"
                            />

                            <v-btn
                                :class="[
                                    '!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-2 py-2 text-[11px]',
                                    props.notice?.installments == 1 ? 'w-full' : 'flex-1',
                                ]"
                                @click="openUpload"
                            >
                                {{
                                    props.notice?.installments == 1
                                        ? 'Fazer upload do pagamento'
                                        : `Upload da ${n}º parcela`
                                }}
                            </v-btn>
                        </template>
                    </div>
                </div>
                <div class="w-full pt-2 flex flex-col gap-1">
                    <template v-if="anyProjectsHasPO">
                        <p>Editar Despacho (PO)</p>
                        <v-btn
                            class="w-full !shadow-none !font-bold !border-gray-300 !bg-white !text-[#2d353fFF] rounded-lg text-xs gap-6"
                            :disabled="!(props.selectedProjects?.length > 0)"
                            variant="outlined"
                            @click="openPODialog"
                        >
                            <span class="w-full text-left"> Editar Despacho (PO) </span>

                            <template #append>
                                <v-icon size="18"> mdi-pencil </v-icon>
                            </template>
                        </v-btn>
                    </template>

                    <template v-else>
                        <p>Criar Despacho</p>
                        <v-btn
                            class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                            :disabled="!(props.selectedProjects?.length > 0)"
                            @click="openPODialog"
                        >
                            Criar
                        </v-btn>
                    </template>
                </div>
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

    <v-snackbar v-model="showError" color="error" timeout="4000">
        {{ errorMessage }}
    </v-snackbar>
</template>
