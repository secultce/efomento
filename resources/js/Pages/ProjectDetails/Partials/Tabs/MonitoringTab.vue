<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import SectionForm from '@/Components/SectionForm.vue';
import FormField from '@/Components/FormField.vue';
import TextField from '@/Components/TextField.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import DiligenceChat from '@/Components/DiligenceChat.vue';
import TramitButton from '@/Pages/ProjectDetails/Partials/Tabs/Actions/TramitButton.vue';
import { viewSections, formSections } from '@/Schemas/Monitoring';
import { useSnackbar } from '@/Composables/useSnackbar';
import { useAlert } from '@/Composables/useAlert';

const props = defineProps({
    project: {
        type: Object,
        default: () => ({}),
    },
});

const { showSnackbar } = useSnackbar();
const { showAlert } = useAlert();

const activeViewIndex = ref('all');

const hasMonitoringSnapshot = computed(() => props.project.has_monitoring_snapshot === true);
const monitoringDialogOpen = ref(false);

const monitoringStage = computed(() => props.project.stages?.find((s) => s.slug === 'monitoramento') ?? null);

const canRequestNextInstallment = computed(() => {
    const installments = props.project.notice?.installments ?? 1;
    const currentCycle = props.project.current_installment_cycle ?? 1;
    return installments > 1 && currentCycle < installments && monitoringStage.value?.status === 'em_andamento';
});

const form = useForm({
    technical_opinions: props.project.monitoring?.technical_opinions?.length
        ? props.project.monitoring.technical_opinions
        : [{ suite_number: '', processing_date: '' }],
    observations: props.project.monitoring?.observations ?? '',
});

function addOpinion() {
    form.technical_opinions.push({ suite_number: '', processing_date: '' });
}

function removeOpinion(index) {
    form.technical_opinions.splice(index, 1);
}

const tramitLoading = ref(false);

const tramit = () => {
    if (!monitoringStage.value?.id) {
        showSnackbar('Etapa de monitoramento não encontrada.', 'error');
        return;
    }

    tramitLoading.value = true;

    router.patch(
        route('projects.stages.advance', {
            project: props.project.id,
            stage: monitoringStage.value.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showAlert({
                    alertTitle: 'Tarefa marcada como tramitada',
                    alertMessage:
                        'As informações foram validadas e as pessoas envolvidas nesse processo foram notificadas.',
                    confirmText: 'Entendi',
                    action: () => {
                        router.visit(window.location.pathname, {
                            preserveState: false,
                            preserveScroll: true,
                        });
                    },
                });
            },
            onError: (errors) => {
                const message = Object.values(errors).flat().join(', ') || 'Erro ao tramitar monitoramento';
                showSnackbar(message, 'error');
            },
            onFinish: () => {
                tramitLoading.value = false;
            },
        }
    );
};

const requestingNextInstallment = ref(false);

function requestNextInstallment() {
    showAlert({
        alertTitle: 'Solicitar próxima parcela',
        alertMessage:
            'Ao confirmar, o ciclo de Orçamento, Pagamento e Monitoramento será reiniciado para a próxima parcela.',
        confirmText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        action: () => {
            requestingNextInstallment.value = true;
            router.patch(
                route('projects.stages.request-next-installment', { project: props.project.id }),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () =>
                        router.visit(window.location.pathname, { preserveState: false, preserveScroll: true }),
                    onError: (errors) => {
                        const msg = Object.values(errors).flat().join(', ') || 'Erro ao solicitar próxima parcela';
                        showSnackbar(msg, 'error');
                    },
                    onFinish: () => {
                        requestingNextInstallment.value = false;
                    },
                }
            );
        },
    });
}

function submit() {
    const monitoring = props.project.monitoring;

    const options = {
        preserveScroll: true,
        onSuccess: () => showSnackbar('Monitoramento salvo com sucesso!', 'success'),
        onError: () => showSnackbar('Ocorreu um erro ao salvar o monitoramento.', 'error'),
    };

    if (monitoring?.id) {
        form.patch(
            route('projects.monitorings.update', {
                project: props.project.id,
                monitoring: monitoring.id,
            }),
            options
        );

        return;
    }

    form.post(route('projects.monitorings.store', { project: props.project.id }), options);
}
</script>

<template>
    <split-screen-tab value="monitoramento">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <p class="font-bold text-lg">Dados disponíveis para consulta</p>
                    <p class="text-sm text-gray-600">Utilize os filtros abaixo para navegar entre os dados</p>
                </div>
                <section-chips v-model="activeViewIndex" :sections="viewSections" show-all-option />
                <div class="mt-4 space-y-8">
                    <template v-for="(section, index) in viewSections" :key="'view-' + section.title">
                        <section-content
                            v-if="activeViewIndex === 'all' || activeViewIndex === index"
                            :section="section"
                            :project="project"
                        />
                    </template>
                </div>
            </div>
        </template>

        <template #right-content>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-bold text-lg">Campos para você inserir ou editar dados</p>
                        <p class="font-bold text-md mt-2 text-black">Links auxiliares</p>
                    </div>
                    <div class="flex gap-2">
                        <v-btn
                            variant="outlined"
                            color="outlineSecondary"
                            class="rounded-lg"
                            :loading="form.processing"
                            @click="submit"
                        >
                            Salvar Alterações
                        </v-btn>
                    </div>
                </div>
                <aux-links />
                <v-btn
                    color="primary"
                    class="rounded-lg w-full"
                    :disabled="!hasMonitoringSnapshot"
                    @click="monitoringDialogOpen = true"
                >
                    Visualizar ficha da fase do Monitoramento
                </v-btn>
                <diligence-chat
                    :project="project"
                    stage="monitoramento"
                    description="Envie mensagem ao agente cultural sobre o relatório de monitoramento (não vale para notificações, comunicados, solicitações etc.)"
                />
                <section-form :active-edit-index="'all'" :sections="formSections">
                    <template #header-action="{ section }">
                        <v-btn
                            v-if="section.key === 'opinion'"
                            variant="text"
                            color="primary"
                            class="pl-0 font-bold text-xs"
                            prepend-icon="mdi-plus"
                            @click="addOpinion"
                        >
                            Registre novo parecer técnico
                        </v-btn>
                    </template>
                    <template #default="{ section }">
                        <template v-if="section.key === 'opinion'">
                            <div v-for="(opinion, i) in form.technical_opinions" :key="i" class="mb-2">
                                <div class="grid grid-cols-2 gap-4">
                                    <form-field label="Número do parecer no SUITE" required>
                                        <text-field v-model="opinion.suite_number" />
                                    </form-field>
                                    <form-field label="Data da tramitação do parecer via Suite">
                                        <text-field v-model="opinion.processing_date" type="date" />
                                    </form-field>
                                </div>
                                <v-btn
                                    variant="text"
                                    color="error"
                                    class="pl-0 font-bold text-xs"
                                    prepend-icon="mdi-plus"
                                    @click="removeOpinion(i)"
                                >
                                    Excluir campos
                                </v-btn>
                            </div>
                        </template>
                        <template v-if="section.key === 'observations'">
                            <text-field v-model="form.observations" :rows="4" type="textarea" />
                        </template>
                    </template>
                </section-form>
                <!-- BOTÃO DE AÇÃO -->
                <div v-if="monitoringStage?.status === 'em_andamento'" class="flex gap-2">
                    <v-btn
                        v-if="canRequestNextInstallment"
                        variant="outlined"
                        color="primary"
                        class="rounded-lg mt-4"
                        :loading="requestingNextInstallment"
                        @click="requestNextInstallment"
                    >
                        Solicitar próxima parcela
                    </v-btn>
                    <tramit-button :action="tramit" :loading="tramitLoading" />
                </div>
            </div>
        </template>
    </split-screen-tab>

    <v-dialog v-model="monitoringDialogOpen" max-width="800" scrollable>
        <v-card class="rounded-lg d-flex flex-column" max-height="85vh">
            <v-card-title class="pa-4">Ficha da fase do Monitoramento</v-card-title>
            <v-divider />
            <v-card-text class="pa-4">
                <template v-if="project.data_registration">
                    <div v-for="(value, key) in project.data_registration" :key="key" class="mb-3">
                        <p class="text-xs text-gray-500 font-semibold uppercase">{{ key }}</p>
                        <p class="text-sm">{{ value ?? '—' }}</p>
                    </div>
                </template>
                <p v-else class="text-sm text-gray-500">Nenhum dado de inscrição disponível.</p>
            </v-card-text>
            <v-divider />
            <v-card-actions class="pa-4 justify-end">
                <v-btn variant="outlined" @click="monitoringDialogOpen = false">Fechar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
