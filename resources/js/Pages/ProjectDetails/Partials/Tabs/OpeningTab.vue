<script setup>
import { computed, ref } from 'vue';
import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import SectionForm from '@/Components/SectionForm.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import { viewSections, formSections } from '@/Schemas/Opening';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useSnackbar } from '@/Composables/useSnackbar';
import TextField from '@/Components/TextField.vue';
import UserAutocompleteField from '@/Components/UserAutocompleteField.vue';
import FormField from '@/Components/FormField.vue';
import SelectField from '@/Components/SelectField.vue';
import { useDate } from '@/Composables/useDate';
import TramitButton from '@/Pages/ProjectDetails/Partials/Tabs/Actions/TramitButton.vue';
import { useAlert } from '@/Composables/useAlert';
import { useAuth } from '@/Composables/useAuth';

const { showSnackbar } = useSnackbar();
const { normalizeDate } = useDate();
const { showAlert } = useAlert();
const { hasRole } = useAuth();

const props = defineProps({
    project: {
        type: Object,
        default: () => ({}),
    },
    availableSupervisors: {
        type: Array,
        default: () => [],
    },
    agentStatus: {
        type: Array,
        default: () => [],
    },
    reportStatus: {
        type: Array,
        default: () => [],
    },
    accountType: {
        type: Array,
        default: () => [],
    },
});

const stage = props.project.stages.find((s) => s.slug === 'abertura');

const canUserHandleOpening = computed(() => {
    return hasRole('super_admin') || hasRole('fomentation') || hasRole('coord_fomentation');
});

defineEmits(['update:field']);

const form = useForm({
    opening: {
        opening_nup: null,
        opening_date: null,
        agent_status: null,
        opened_by: null,
        bank: null,
        account_type: null,
        branch: null,
        account: null,
        supervisors: [
            { id: null, registration_number: '' },
            { id: null, registration_number: '' },
        ],
    },

    formalization: {
        report_status: null,
        eparcerias_certificate_date: null,
    },

    agent: {
        secondary_email: null,
        secondary_phone: null,
    },
});

onMounted(() => {
    const opening = props.project.opening || {};
    const formalization = props.project.formalization || {};
    const agent = props.project.agent || {};

    form.opening = {
        opening_nup: opening.opening_nup ?? null,
        opening_date: normalizeDate(opening.opening_date) ?? null,
        agent_status: opening.agent_status ?? null,
        opened_by: opening.opened_by ?? null,
        bank: opening.bank ?? null,
        account_type: opening.account_type ?? null,
        branch: opening.branch ?? null,
        account: opening.account ?? null,
        supervisors: [
            {
                id: opening?.supervisors[0]?.user_id ?? null,
                registration_number: opening.supervisors?.[0]?.user.registration_number ?? '',
            },
            {
                id: opening?.supervisors[1]?.user_id ?? null,
                registration_number: opening.supervisors?.[1]?.user.registration_number ?? '',
            },
        ],
    };

    form.formalization = {
        report_status: formalization.report_status ?? null,
        eparcerias_certificate_date: normalizeDate(formalization.eparcerias_certificate_date) ?? null,
    };

    form.agent = {
        secondary_email: agent.latest_snapshot?.secondary_email ?? null,
        secondary_phone: agent.latest_snapshot?.secondary_phone ?? null,
    };
});

const syncSupervisor = (index) => {
    const selectedId = form.opening.supervisors[index].id;

    const user = props.availableSupervisors.find((u) => u.id === selectedId);

    form.opening.supervisors[index].registration_number = user?.registration_number ?? '';
};

const submit = () => {
    form.patch(
        route('projects.openings.update', {
            project: props.project.id,
            opening: props.project.opening.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                showSnackbar('Abertura atualizada com sucesso!', 'success');
            },
            onError: (errors) => {
                const message = Object.values(errors).flat().join(', ') || 'Ocorreu um erro ao atualizar a abertura';
                showSnackbar('Ocorreu um erro ao atualizar a abertura. ' + message, 'error');
            },
        }
    );
};

const tramitLoading = ref(false);

const tramit = () => {
    tramitLoading.value = true;

    router.patch(
        route('projects.stages.advance', {
            project: props.project.id,
            stage: stage.id,
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
                const message = Object.values(errors).flat().join(', ') || 'Erro ao tramitar projeto';

                showSnackbar(message, 'error');
            },
            onFinish: () => {
                tramitLoading.value = false;
            },
        }
    );
};

const permissionMessage = computed(() => {
    if (!canUserHandleOpening.value) {
        return 'Usuário não tem permissão para fazer alterações na Abertura';
    }

    if (stage.status === 'bloqueado') {
        return 'Este projeto está bloqueado e não pode receber alterações no momento.';
    }

    return 'Projeto já foi tramitado e não está mais na fase de Abertura. Aguarde a resposta da Análise Jurídica ou entre em contato com o setor responsável para solicitar a devolução.';
});

const activeViewIndex = ref('all');
const activeEditIndex = ref('all');
</script>
<template>
    <split-screen-tab value="opening">
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
                <div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-bold text-lg">Campos para você inserir ou editar dados</p>
                            <p class="font-bold text-md mt-2 text-black">Links auxiliares</p>
                        </div>
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
                <section-chips v-model="activeEditIndex" :sections="formSections" />
                <div
                    v-permission="{
                        condition:
                            !canUserHandleOpening || (stage.status !== 'aprovado' && stage.status !== 'bloqueado'),
                        message: permissionMessage,
                    }"
                    class="mt-4"
                >
                    <section-form :active-edit-index="activeEditIndex" :sections="formSections">
                        <template #default="{ section }">
                            <template v-if="section.key === 'opening'">
                                <div class="grid grid-cols-2 gap-4">
                                    <form-field label="Número do processo*" required>
                                        <text-field
                                            v-model="form.opening.opening_nup"
                                            mask="####.######/####-##"
                                            data-cy="project-nup-opening-tab"
                                        />
                                    </form-field>

                                    <form-field label="Data de abertura do processo" required>
                                        <text-field v-model="form.opening.opening_date" type="date" />
                                    </form-field>

                                    <form-field label="Status do agente cultural">
                                        <select-field
                                            v-model="form.opening.agent_status"
                                            :items="props.agentStatus"
                                            item-title="label"
                                            item-value="value"
                                            placeholder="Selecione um tipo"
                                        />
                                    </form-field>

                                    <form-field label="Responsável por abrir o processo" required>
                                        <text-field v-model="form.opening.opened_by" />
                                    </form-field>
                                </div>
                            </template>
                            <template v-else-if="section.key === 'certificate'">
                                <div class="grid grid-cols-2 gap-4">
                                    <form-field label="Regularidade e inadimplência">
                                        <select-field
                                            v-model="form.formalization.report_status"
                                            :items="props.reportStatus"
                                            item-title="label"
                                            item-value="value"
                                            placeholder="Selecione um tipo"
                                        />
                                    </form-field>

                                    <form-field label="Data da certidão">
                                        <text-field
                                            v-model="form.formalization.eparcerias_certificate_date"
                                            type="date"
                                        />
                                    </form-field>
                                </div>
                            </template>
                            <template v-else-if="section.key === 'bank'">
                                <div class="grid grid-cols-2 gap-4">
                                    <form-field label="Banco">
                                        <text-field v-model="form.opening.bank" />
                                    </form-field>
                                    <form-field label="Tipo de conta">
                                        <select-field
                                            v-model="form.opening.account_type"
                                            item-title="label"
                                            item-value="value"
                                            :items="props.accountType"
                                            placeholder="Selecione um tipo"
                                        />
                                    </form-field>
                                    <form-field label="Agência">
                                        <text-field v-model="form.opening.branch" />
                                    </form-field>
                                    <form-field label="Conta">
                                        <text-field v-model="form.opening.account" />
                                    </form-field>
                                </div>
                            </template>
                            <template v-else-if="section.key === 'supervisors'">
                                <div class="grid grid-cols-2 gap-4">
                                    <form-field label="Fiscal titular">
                                        <user-autocomplete-field
                                            v-model="form.opening.supervisors[0].id"
                                            variant="outlined"
                                            :items="availableSupervisors"
                                            @update:model-value="() => syncSupervisor(0)"
                                        />
                                    </form-field>

                                    <form-field label="Matrícula titular">
                                        <text-field
                                            v-model="form.opening.supervisors[0].registration_number"
                                            :disabled="!form.opening.supervisors[0].id"
                                        />
                                    </form-field>

                                    <form-field label="Fiscal suplente">
                                        <user-autocomplete-field
                                            v-model="form.opening.supervisors[1].id"
                                            variant="outlined"
                                            :items="availableSupervisors"
                                            @update:model-value="() => syncSupervisor(1)"
                                        />
                                    </form-field>

                                    <form-field label="Matrícula titular">
                                        <text-field
                                            v-model="form.opening.supervisors[1].registration_number"
                                            :disabled="!form.opening.supervisors[1].id"
                                        />
                                    </form-field>
                                </div>
                            </template>
                            <template v-else-if="section.key === 'agent'">
                                <div class="grid grid-cols-2 gap-4">
                                    <form-field label="Email secundário">
                                        <text-field v-model="form.agent.secondary_email" type="email" />
                                    </form-field>

                                    <form-field label="Telefone secundário">
                                        <text-field v-model="form.agent.secondary_phone" />
                                    </form-field>
                                </div>
                            </template>
                        </template>
                    </section-form>
                    <tramit-button :action="tramit" :disabled="!canUserHandleOpening" :loading="tramitLoading" />
                </div>
            </div>
        </template>
    </split-screen-tab>
</template>
