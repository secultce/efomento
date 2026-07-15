<script setup>
import { computed, onMounted, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import SectionForm from '@/Components/SectionForm.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import ReturnProcessModal from '@/Components/ReturnProcessModal.vue';
import FormField from '@/Components/FormField.vue';
import TextField from '@/Components/TextField.vue';
import SelectField from '@/Components/SelectField.vue';
import TramitButton from '@/Pages/ProjectDetails/Partials/Tabs/Actions/TramitButton.vue';
import SaveButton from '@/Pages/ProjectDetails/Partials/Tabs/Actions/SaveButton.vue';

import { viewSections } from '@/Schemas/Opening';
import { formSections } from '@/Schemas/Formalization';

import { usePermissions } from '@/Composables/usePermissions';
import { useDate } from '@/Composables/useDate';
import { useSnackbar } from '@/Composables/useSnackbar';
import { useAlert } from '@/Composables/useAlert';
import { useSaveShortcut } from '@/Composables/useSaveShortcut';

const props = defineProps({
    project: { type: Object, required: true },
    canReturn: { type: Boolean, default: false },
    currentStage: { type: Object, default: null },
    reportStatus: { type: Array, default: () => [] },
    deliberation: { type: Array, default: () => [] },
});

const { canManageFormalization } = usePermissions();
const { normalizeDate } = useDate();
const { showSnackbar } = useSnackbar();
const { showAlert } = useAlert();

const canUserHandleFormalization = canManageFormalization;

const stage = computed(() => props.project.stages?.find((s) => s.slug === 'formalizacao'));

useSaveShortcut(
    () => submit(),
    computed(() => canUserHandleFormalization.value && !form.processing)
);

const showReturnModal = ref(false);
const activeViewIndex = ref('all');
const activeEditIndex = ref('all');

const form = useForm({
    asjur_finalistic_processing_date: null,
    asjur_received_at: null,
    process_assigned_to: null,
    report_status: null,
    eparcerias_certificate_date: null,
    asjur_processing_date: null,
    responsible_at_asjur: null,
    term_number: null,
    term_signature_sent_at: null,
    term_signed_at: null,
    sent_to_office_at: null,
    signed_by_office_at: null,
    sacc_number: null,
    cge_atende_ticket: null,
    deliberation: null,
    sent_to_chief_of_staff_at: null,
    official_gazette_published_at: null,
    validity_start_at: null,
    validity_end_at: null,
    legal_opinion_date: null,
    official_gazette_file: null,
    _method: null,
});

onMounted(() => {
    const formalization = props.project.formalizations || {};

    form.asjur_finalistic_processing_date = normalizeDate(formalization.asjur_finalistic_processing_date) ?? null;
    form.asjur_received_at = normalizeDate(formalization.asjur_received_at) ?? null;
    form.process_assigned_to = formalization.process_assigned_to ?? null;
    form.report_status = formalization.report_status ?? null;
    form.eparcerias_certificate_date = normalizeDate(formalization.eparcerias_certificate_date) ?? null;
    form.asjur_processing_date = normalizeDate(formalization.asjur_processing_date) ?? null;
    form.responsible_at_asjur = formalization.responsible_at_asjur ?? null;
    form.term_number = formalization.term_number ?? null;
    form.term_signature_sent_at = normalizeDate(formalization.term_signature_sent_at) ?? null;
    form.term_signed_at = normalizeDate(formalization.term_signed_at) ?? null;
    form.sent_to_office_at = normalizeDate(formalization.sent_to_office_at) ?? null;
    form.signed_by_office_at = normalizeDate(formalization.signed_by_office_at) ?? null;
    form.sacc_number = formalization.sacc_number ?? null;
    form.cge_atende_ticket = formalization.cge_atende_ticket ?? null;
    form.deliberation = formalization.deliberation ?? null;
    form.sent_to_chief_of_staff_at = normalizeDate(formalization.sent_to_chief_of_staff_at) ?? null;
    form.official_gazette_published_at = normalizeDate(formalization.official_gazette_published_at) ?? null;
    form.validity_start_at = normalizeDate(formalization.validity_start_at) ?? null;
    form.validity_end_at = normalizeDate(formalization.validity_end_at) ?? null;
    form.legal_opinion_date = normalizeDate(formalization.legal_opinion_date) ?? null;
});

const requiredFields = {
    asjur_finalistic_processing_date: 'Data de tramitação da finalística para a ASJUR',
    asjur_received_at: 'Data de recebimento do processo pela ASJUR',
    process_assigned_to: 'Processo distribuído para',
    report_status: 'Informe regularidade e inadimplência',
    eparcerias_certificate_date: 'Data da certidão',
    asjur_processing_date: 'Data de tramitação na ASJUR',
    responsible_at_asjur: 'Responsável (Distribuido para)',
    term_number: 'Número do termo',
    term_signature_sent_at: 'Data do envio para assinatura do termo',
    term_signed_at: 'Data da assinatura do termo',
    sent_to_office_at: 'Data de envio para Gabinete',
    signed_by_office_at: 'Data de assinatura do termo pelo Gabinete',
    sacc_number: 'Número do SACC',
    cge_atende_ticket: 'Chamado CGE atende',
    deliberation: 'Deliberação',
    sent_to_chief_of_staff_at: 'Data de envio para Casa Civil',
    official_gazette_published_at: 'Data de Publicação do Diário Oficial do Estado',
    validity_start_at: 'Data de início da vigência do instrumento',
    validity_end_at: 'Data de término da vigência do instrumento',
    legal_opinion_date: 'Data do parecer jurídico',
};

const isBlank = (value) => value === null || value === undefined || value === '';

const requiredFormalizationDocuments = {
    tc: 'Termo de execução cultural',
    et: 'Extrato do termo',
    pj: 'Parecer jurídico',
};

const projectDocuments = computed(() => {
    const documents = props.project.documents ?? [];

    if (Array.isArray(documents)) {
        return documents;
    }

    if (Array.isArray(documents?.data)) {
        return documents.data;
    }

    return [];
});

const getValue = (value) => {
    if (value && typeof value === 'object') {
        return value.value ?? value.id ?? value.name ?? null;
    }

    return value;
};

const generatedFormalizationDocumentTypes = computed(() => {
    return new Set(
        projectDocuments.value
            .filter((document) => getValue(document.phase) === 'formalization')
            .filter((document) => !document.deleted_at)
            .map((document) => getValue(document.type))
    );
});

const missingGeneratedDocuments = computed(() => {
    return Object.entries(requiredFormalizationDocuments)
        .filter(([type]) => !generatedFormalizationDocumentTypes.value.has(type))
        .map(([, label]) => label);
});

const hasGeneratedRequiredDocuments = computed(() => missingGeneratedDocuments.value.length === 0);

const missingRequiredFields = computed(() => {
    const missing = Object.entries(requiredFields)
        .filter(([field]) => isBlank(form[field]))
        .map(([, message]) => message);

    // Verifica se não há anexo selecionado na tela nem salvo no banco
    if (!form.official_gazette_file && !officialGazetteFile.value) {
        missing.push('Anexo do documento do Diário Oficial do Estado');
    }

    return missing;
});

const hasRequiredFieldsFilled = computed(() => missingRequiredFields.value.length === 0);

const canTramitFormalization = computed(() => {
    return canUserHandleFormalization.value && hasRequiredFieldsFilled.value && hasGeneratedRequiredDocuments.value;
});

const tramitBlockedMessage = computed(() => {
    if (!canUserHandleFormalization.value) {
        return 'Usuário não tem permissão para tramitar a Formalização.';
    }

    if (!hasRequiredFieldsFilled.value) {
        return `Preencha os campos obrigatórios antes de tramitar: ${missingRequiredFields.value.join(', ')}.`;
    }

    if (!hasGeneratedRequiredDocuments.value) {
        return `Gere os documentos obrigatórios antes de tramitar: ${missingGeneratedDocuments.value.join(', ')}.`;
    }

    return '';
});

const showTramitBlockedMessage = () => {
    if (canTramitFormalization.value || tramitLoading.value) {
        return;
    }

    showSnackbar(tramitBlockedMessage.value, 'warning');
};

const officialGazetteFileInput = ref(null);

const officialGazetteFile = computed(() => {
    const files = props.project.formalizations?.files;

    if (!files) {
        return null;
    }

    const groupedFiles = files.official_gazette;

    if (Array.isArray(groupedFiles)) {
        return groupedFiles[0] ?? null;
    }

    if (Array.isArray(groupedFiles?.data)) {
        return groupedFiles.data[0] ?? null;
    }

    if (Array.isArray(files)) {
        return files.find((file) => file.grp === 'official_gazette') ?? null;
    }

    if (Array.isArray(files?.data)) {
        return files.data.find((file) => file.grp === 'official_gazette') ?? null;
    }

    return null;
});

const officialGazetteFileLabel = computed(() => {
    if (form.official_gazette_file?.name) {
        return form.official_gazette_file.name;
    }

    return 'Anexe o documento';
});

const onOfficialGazetteFileSelected = (event) => {
    form.clearErrors('official_gazette_file');

    const file = event.target.files?.[0] ?? null;

    if (!file) {
        form.official_gazette_file = null;
        return;
    }

    const maxSize = 10 * 1024 * 1024;

    if (file.size > maxSize) {
        form.setError('official_gazette_file', 'O arquivo deve ter no máximo 10MB.');
        form.official_gazette_file = null;
        event.target.value = null;
        return;
    }

    form.official_gazette_file = file;
};

const removeOfficialGazetteFile = () => {
    if (!officialGazetteFile.value?.id) {
        return;
    }

    router.delete(
        route('projects.formalizations.files.destroy', {
            project: props.project.id,
            formalization: props.project.formalizations.id,
            file: officialGazetteFile.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                showSnackbar('Arquivo removido com sucesso!', 'success');
            },
            onError: () => {
                showSnackbar('Ocorreu um erro ao remover o arquivo.', 'error');
            },
        }
    );
};

const viewOfficialGazetteFile = () => {
    if (!officialGazetteFile.value?.id) {
        return;
    }

    window.open(
        route('projects.formalizations.files.show', {
            project: props.project.id,
            formalization: props.project.formalizations.id,
            file: officialGazetteFile.value.id,
        }),
        '_blank'
    );
};

const downloadOfficialGazetteFile = () => {
    if (!officialGazetteFile.value?.id) {
        return;
    }

    window.open(
        route('projects.formalizations.files.download', {
            project: props.project.id,
            formalization: props.project.formalizations.id,
            file: officialGazetteFile.value.id,
        }),
        '_blank'
    );
};

const saveFormalization = ({ showSuccess = true } = {}) => {
    return new Promise((resolve) => {
        const formalization = props.project.formalizations;

        const options = {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                form.official_gazette_file = null;
                form._method = null;

                if (officialGazetteFileInput.value) {
                    officialGazetteFileInput.value.value = null;
                }

                if (showSuccess) {
                    showSnackbar('Formalização salva com sucesso!', 'success');
                }

                resolve(true);
            },
            onError: (errors) => {
                const details = Object.values(errors).flat().filter(Boolean).join(', ');

                const message = details
                    ? `Ocorreu um erro ao salvar a formalização. ${details}`
                    : 'Ocorreu um erro ao salvar a formalização.';

                showSnackbar(message, 'error');

                resolve(false);
            },
        };

        if (formalization?.id) {
            form._method = 'patch';

            form.post(
                route('projects.formalizations.update', {
                    project: props.project.id,
                    formalization: formalization.id,
                }),
                options
            );

            return;
        }

        form._method = null;

        form.post(
            route('projects.formalizations.store', {
                project: props.project.id,
            }),
            options
        );
    });
};

const submit = () => {
    saveFormalization();
};

const tramitLoading = ref(false);

const tramit = async () => {
    if (!stage.value?.id) {
        showSnackbar('Etapa de formalização não encontrada.', 'error');
        return;
    }

    if (!canTramitFormalization.value) {
        showSnackbar(tramitBlockedMessage.value, 'warning');
        return;
    }

    tramitLoading.value = true;

    const saved = await saveFormalization({ showSuccess: false });

    if (!saved) {
        tramitLoading.value = false;
        return;
    }

    router.patch(
        route('projects.stages.advance', {
            project: props.project.id,
            stage: stage.value.id,
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
    if (!canUserHandleFormalization.value) {
        return 'Usuário não tem permissão para fazer alterações na Formalização.';
    }

    if (stage.value?.status === 'bloqueado') {
        return 'Este projeto está bloqueado e não pode receber alterações no momento.';
    }

    return 'Projeto já foi tramitado e não está mais na fase de Formalização.';
});
</script>

<template>
    <SplitScreenTab value="formalization">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <p class="font-bold text-lg d-flex justify-between">
                        Dados disponíveis para consulta

                        <v-btn
                            v-if="canReturn && currentStage"
                            v-permission="{
                                condition: !canUserHandleFormalization || stage?.status !== 'aprovado',
                                message: !canUserHandleFormalization
                                    ? 'Usuário não tem permissão para devolver processo'
                                    : 'Formalização já foi tramitada.',
                            }"
                            class="!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
                            data-cy="return-process-button-formalization"
                            @click="showReturnModal = true"
                        >
                            DEVOLVER PROCESSO
                        </v-btn>
                    </p>

                    <p class="text-sm text-gray-600">Utilize os filtros abaixo para navegar entre os dados</p>
                </div>

                <SectionChips v-model="activeViewIndex" :sections="viewSections" show-all-option />

                <div class="space-y-8">
                    <template v-for="(section, index) in viewSections" :key="'view-' + section.title">
                        <SectionContent
                            v-if="activeViewIndex === 'all' || activeViewIndex === index"
                            :section="section"
                            :project="project"
                        />
                    </template>
                </div>
            </div>
        </template>

        <template #right-content>
            <div class="space-y-6" data-cy="right-panel">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-bold text-lg">Campos para você inserir ou editar dados</p>

                        <p class="font-bold text-md mt-2 text-black">Links auxiliares</p>
                    </div>

                    <SaveButton :loading="form.processing" :can-save="canUserHandleFormalization" @click="submit" />
                </div>

                <AuxLinks />

                <SectionChips v-model="activeEditIndex" :sections="formSections" />

                <div
                    v-permission="{
                        condition:
                            !canUserHandleFormalization ||
                            (stage?.status !== 'aprovado' && stage?.status !== 'bloqueado'),
                        message: permissionMessage,
                    }"
                    class="mt-4"
                >
                    <SectionForm :active-edit-index="activeEditIndex" :sections="formSections">
                        <template #default="{ section }">
                            <template v-if="section.key === 'dates_and_status_process'">
                                <div class="grid grid-cols-2 gap-4">
                                    <FormField label="Data de tramitação da finalística para a ASJUR">
                                        <TextField
                                            v-model="form.asjur_finalistic_processing_date"
                                            type="date"
                                            data-cy="asjur-finalistic-processing-date"
                                        />
                                    </FormField>

                                    <FormField label="Data de recebimento do processo pela ASJUR">
                                        <TextField
                                            v-model="form.asjur_received_at"
                                            type="date"
                                            data-cy="asjur-process-received-date"
                                        />
                                    </FormField>

                                    <div class="col-span-2 grid grid-cols-2 gap-4">
                                        <FormField label="Processo distribuído para">
                                            <TextField
                                                v-model="form.process_assigned_to"
                                                data-cy="process-assigned-to"
                                            />
                                        </FormField>

                                        <div></div>
                                    </div>

                                    <FormField label="Informe regularidade e inadimplência">
                                        <SelectField
                                            v-model="form.report_status"
                                            :items="reportStatus"
                                            item-title="label"
                                            item-value="value"
                                            placeholder="Selecione um status"
                                            data-cy="report-status-select"
                                        />
                                    </FormField>

                                    <FormField label="Data da certidão">
                                        <TextField
                                            v-model="form.eparcerias_certificate_date"
                                            type="date"
                                            data-cy="eparcerias-certificate-date"
                                        />
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'signature_form'">
                                <div class="grid grid-cols-2 gap-4">
                                    <FormField label="Data de tramitação na ASJUR">
                                        <TextField
                                            v-model="form.asjur_processing_date"
                                            type="date"
                                            data-cy="asjur-processing-date-input"
                                        />
                                    </FormField>

                                    <FormField label="Responsável (Distribuido para)">
                                        <TextField
                                            v-model="form.responsible_at_asjur"
                                            data-cy="responsible-at-asjur-input"
                                        />
                                    </FormField>

                                    <FormField label="Número do termo">
                                        <TextField v-model="form.term_number" data-cy="term-number-input" />
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'signing_term'">
                                <div class="grid grid-cols-2 gap-4">
                                    <FormField label="Data do envio para assinatura do termo">
                                        <TextField
                                            v-model="form.term_signature_sent_at"
                                            type="date"
                                            data-cy="term-signature-sent-at-input"
                                        />
                                    </FormField>

                                    <FormField label="Data da assinatura do termo">
                                        <TextField
                                            v-model="form.term_signed_at"
                                            type="date"
                                            data-cy="term-signed-at-input"
                                        />
                                    </FormField>

                                    <FormField label="Data de envio para Gabinete">
                                        <TextField
                                            v-model="form.sent_to_office_at"
                                            type="date"
                                            data-cy="sent-to-office-at-input"
                                        />
                                    </FormField>

                                    <FormField label="Data de assinatura do termo pelo Gabinete">
                                        <TextField
                                            v-model="form.signed_by_office_at"
                                            type="date"
                                            data-cy="signed-by-office-at-input"
                                        />
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'sacc'">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2 grid grid-cols-2 gap-4">
                                        <FormField label="Número do SACC">
                                            <TextField v-model="form.sacc_number" data-cy="sacc-number-input" />
                                        </FormField>

                                        <div></div>
                                    </div>

                                    <FormField label="Chamado CGE atende">
                                        <TextField v-model="form.cge_atende_ticket" data-cy="cge-atende-ticket-input" />
                                    </FormField>

                                    <FormField label="Deliberação">
                                        <SelectField
                                            v-model="form.deliberation"
                                            :items="deliberation"
                                            item-title="label"
                                            item-value="value"
                                            placeholder="Selecione um status"
                                            data-cy="deliberation-select"
                                        />
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'official_gazette'">
                                <div class="grid grid-cols-2 gap-4">
                                    <FormField label="Data de envio para Casa Civil">
                                        <TextField
                                            v-model="form.sent_to_chief_of_staff_at"
                                            type="date"
                                            data-cy="sent-to-chief-of-staff-at-input"
                                        />
                                    </FormField>

                                    <FormField label="Data de Publicação do Diário Oficial do Estado">
                                        <TextField
                                            v-model="form.official_gazette_published_at"
                                            type="date"
                                            data-cy="official-gazette-published-at-input"
                                        />
                                    </FormField>

                                    <FormField
                                        label="Anexo do documento do Diário Oficial do Estado"
                                        :error="form.errors.official_gazette_file"
                                    >
                                        <div
                                            v-if="officialGazetteFile && !form.official_gazette_file"
                                            class="flex items-center h-[44px] border border-gray-300 rounded-lg bg-white px-4 shadow-sm"
                                        >
                                            <span class="flex-1 text-sm font-bold text-black truncate">
                                                {{ officialGazetteFile.name }}
                                            </span>

                                            <div class="flex items-center gap-3 text-[#007A3D]">
                                                <button
                                                    type="button"
                                                    title="Remover arquivo"
                                                    data-cy="remove-official-gazette-file-button"
                                                    @click="removeOfficialGazetteFile"
                                                >
                                                    <v-icon size="20">mdi-trash-can-outline</v-icon>
                                                </button>

                                                <button
                                                    type="button"
                                                    title="Visualizar arquivo"
                                                    data-cy="view-official-gazette-file-button"
                                                    @click="viewOfficialGazetteFile"
                                                >
                                                    <v-icon size="20">mdi-eye-outline</v-icon>
                                                </button>

                                                <button
                                                    type="button"
                                                    title="Baixar arquivo"
                                                    data-cy="download-official-gazette-file-button"
                                                    @click="downloadOfficialGazetteFile"
                                                >
                                                    <v-icon size="20">mdi-download-box</v-icon>
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            v-else
                                            class="flex items-center h-[44px] border border-gray-400 rounded bg-white overflow-hidden"
                                        >
                                            <span class="flex-1 px-4 text-sm text-gray-600 truncate">
                                                {{ officialGazetteFileLabel }}
                                            </span>

                                            <input
                                                ref="officialGazetteFileInput"
                                                type="file"
                                                class="hidden"
                                                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                data-cy="official-gazette-file-input"
                                                @change="onOfficialGazetteFileSelected"
                                            />

                                            <button
                                                type="button"
                                                class="mr-3 px-3 py-1 rounded-full bg-[#ffcc05FF] text-[#2d353fFF] text-xs font-bold"
                                                data-cy="attach-official-gazette-file-button"
                                                @click="officialGazetteFileInput?.click()"
                                            >
                                                anexar
                                            </button>
                                        </div>
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'validity_instrument'">
                                <div class="grid grid-cols-2 gap-4">
                                    <FormField label="Data de início da vigência do instrumento">
                                        <TextField
                                            v-model="form.validity_start_at"
                                            type="date"
                                            data-cy="instrument-validity-start-at-input"
                                        />
                                    </FormField>

                                    <FormField label="Data de término da vigência do instrumento">
                                        <TextField
                                            v-model="form.validity_end_at"
                                            type="date"
                                            data-cy="instrument-validity-end-at-input"
                                        />
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'legal_opinion'">
                                <div class="grid grid-cols-2 gap-4">
                                    <FormField label="Data do parecer jurídico">
                                        <TextField
                                            v-model="form.legal_opinion_date"
                                            type="date"
                                            data-cy="legal-opinion-date-input"
                                        />
                                    </FormField>
                                </div>
                            </template>
                        </template>
                    </SectionForm>

                    <div
                        data-cy="tramit-container"
                        :class="{ 'cursor-not-allowed': !canTramitFormalization }"
                        @click="showTramitBlockedMessage"
                    >
                        <TramitButton
                            :action="tramit"
                            :disabled="!canTramitFormalization"
                            :loading="tramitLoading"
                            :class="{ 'pointer-events-none': !canTramitFormalization }"
                        />
                    </div>
                </div>
            </div>
        </template>
    </SplitScreenTab>

    <ReturnProcessModal
        v-if="canReturn && currentStage"
        v-model="showReturnModal"
        :project-id="project.id"
        :stage-id="currentStage.id"
    />
</template>
