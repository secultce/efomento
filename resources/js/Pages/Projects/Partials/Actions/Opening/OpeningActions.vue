<script setup>
import { computed, ref } from 'vue';
import SupervisorDialog from '@/Pages/Projects/Partials/Actions/SupervisorDialog.vue';
import { useAuth } from '@/Composables/useAuth';
import { usePermissions } from '@/Composables/usePermissions';
import NoticeHistoryDialog from '@/Pages/Projects/Partials/Actions/NoticeHistoryDialog.vue';
import { DOCUMENT_TYPES } from '@/Schemas/Config/documentConfig';
import DocumentActionsList from '../DocumentActionsList.vue';

const { can } = useAuth();
const { canAssignSupervisor: hasSupervisorRole, canManageNotices } = usePermissions();

const props = defineProps({
    selectedProjects: { type: Array, default: () => [] },
    supervisorsAvailable: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    notice: { type: Object, default: null },
});

defineEmits(['saved']);

const documentTypes = [DOCUMENT_TYPES.CI, DOCUMENT_TYPES.TC];

const documentTestIds = {
    [DOCUMENT_TYPES.CI]: {
        create: 'create-ci-button',
        edit: 'edit-ci-button',
        list: 'btnConferirDocumentos',
    },
    [DOCUMENT_TYPES.TC]: {
        create: 'create-tc-button',
        edit: 'edit-tc-button',
        list: 'btnConferirDocumentosTC',
    },
};

const supervisorDialog = ref(false);

function openSupervisorDialog() {
    supervisorDialog.value = true;
}

const hasProjectsWithSupervisor = computed(() => {
    return props.projects
        .filter((p) => props.selectedProjects.includes(p.id))
        .some((p) => p.opening?.supervisors?.some((s) => s.is_active));
});

const canAssignSupervisor = computed(() => {
    return hasSupervisorRole.value || can('opening.assign_supervisor');
});

const viewHistory = ref(false);

function openNoticeHistory() {
    viewHistory.value = true;
}
</script>
<template>
    <SupervisorDialog
        v-model="supervisorDialog"
        :project-ids="selectedProjects"
        :supervisors="supervisorsAvailable"
        @saved="$emit('saved')"
    />
    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg">Ações disponíveis para você </v-card-title>
        <v-card-text class="flex flex-col gap-4">
            <DocumentActionsList
                :document-types="documentTypes"
                :selected-projects="selectedProjects"
                :projects="projects"
                :can-manage="canManageNotices"
                :test-ids="documentTestIds"
                permission-message="Você não tem permissão para criar ou editar documentos, contate o administrador do sistema."
                @saved="$emit('saved')"
            />

            <div
                v-permission="{
                    condition: canAssignSupervisor,
                    message:
                        'Você não tem permissão para atribuir fiscais ao projeto, contate o administrador do sistema.',
                }"
                class="w-full pt-2 flex flex-col gap-1"
            >
                <p>Atribuir fiscal aos selecionados</p>
                <v-btn
                    data-cy="btnAtribuirFiscal"
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="!(props.selectedProjects?.length > 0) || !canAssignSupervisor"
                    @click="openSupervisorDialog"
                >
                    Atribuir Fiscal
                </v-btn>
                <p v-if="hasProjectsWithSupervisor" class="text-orange-600 max-w-[22em] whitespace-normal text-xs">
                    Um ou mais projetos selecionados já possuem um fiscal. Ao atribuir um novo fiscal, o anterior será
                    desativado.
                </p>
            </div>
            <div class="w-full flex flex-col gap-1">
                <v-divider class="my-4"></v-divider>

                <p>Conferir histórico de alterações nos processos</p>

                <v-btn variant="outlined" color="outlineSecondary" class="rounded-lg w-full" @click="openNoticeHistory">
                    Conferir Histórico
                </v-btn>

                <NoticeHistoryDialog v-model="viewHistory" :notice-id="notice.id" />
            </div>
        </v-card-text>
    </v-card>
</template>
