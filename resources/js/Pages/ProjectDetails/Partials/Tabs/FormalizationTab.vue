<script setup>
import { computed, onMounted, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import SectionForm from '@/Components/SectionForm.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import ReturnProcessAction from '@/Components/ReturnProcessAction.vue';
import FormField from '@/Components/FormField.vue';
import TextField from '@/Components/TextField.vue';
import SelectField from '@/Components/SelectField.vue';
import TramitButton from '@/Pages/ProjectDetails/Partials/Tabs/Actions/TramitButton.vue';
import SaveButton from '@/Pages/ProjectDetails/Partials/Tabs/Actions/SaveButton.vue';

import { viewSections } from '@/Schemas/Opening';
import { formSections } from '@/Schemas/Formalization';

import { useDate } from '@/Composables/useDate';
import { useSnackbar } from '@/Composables/useSnackbar';
import { useAlert } from '@/Composables/useAlert';
import { useSaveShortcut } from '@/Composables/useSaveShortcut';
import { useStageAdvance } from '@/Composables/useStageAdvance';

const props = defineProps({
    project: { type: Object, required: true },
    canReturn: { type: Boolean, default: false },
    currentStage: { type: Object, default: null },
    canAdvance: { type: Boolean, default: false },
    reportStatus: { type: Array, default: () => [] },
    deliberation: { type: Array, default: () => [] },
});

const { normalizeDate } = useDate();
const { showSnackbar } = useSnackbar();
const { showAlert } = useAlert();

const STAGE_SLUG = 'formalizacao';

const { canUserHandle: canUserHandleFormalization } = useStageAdvance(props, STAGE_SLUG);

const stage = computed(() => props.project.stages?.find((s) => s.slug === STAGE_SLUG));

const canEditFormalization = computed(() => canUserHandleFormalization.value && stage.value?.status === 'em_andamento');

useSaveShortcut(
    () => submit(),
    computed(() => canEditFormalization.value && !form.processing)
);

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
});

const hasText = (value) => String(value ?? '').trim().length > 0;

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

const hasOfficialGazetteFile = computed(() => {
    return !!(form.official_gazette_file || officialGazetteFile.value);
});

const hasRequiredFieldsFilled = computed(() => {
    return (
        hasText(form.term_number) &&
        hasText(form.term_signed_at) &&
        hasText(form.signed_by_office_at) &&
        hasText(form.sacc_number) &&
        hasText(form.official_gazette_published_at) &&
        hasOfficialGazetteFile.value &&
        hasText(form.validity_start_at) &&
        hasText(form.validity_end_at)
    );
});

const showValidationErrors = ref(false);

const errors = computed(() => {
    if (!showValidationErrors.value) return {};

    const standardMessage = 'Preencha este campo';

    return {
        term_number: !hasText(form.term_number) ? standardMessage : null,
        term_signed_at: !hasText(form.term_signed_at) ? standardMessage : null,
        signed_by_office_at: !hasText(form.signed_by_office_at) ? standardMessage : null,
        sacc_number: !hasText(form.sacc_number) ? standardMessage : null,
        official_gazette_published_at: !hasText(form.official_gazette_published_at) ? standardMessage : null,
        official_gazette_file: !hasOfficialGazetteFile.value ? 'Anexe o documento' : null,
        validity_start_at: !hasText(form.validity_start_at) ? standardMessage : null,
        validity_end_at: !hasText(form.validity_end_at) ? standardMessage : null,
    };
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

    if (!canEditFormalization.value) {
        showSnackbar(permissionMessage.value || 'Você não tem permissão para alterar ou tramitar esta etapa.', 'error');
        return;
    }

    if (!hasRequiredFieldsFilled.value) {
        showValidationErrors.value = true;
        showSnackbar('Preencha e salve todos os campos obrigatórios em destaque antes de tramitar.', 'error');
        return;
    }

    if (!hasGeneratedRequiredDocuments.value) {
        showSnackbar(
            `Gere os documentos obrigatórios antes de tramitar: ${missingGeneratedDocuments.value.join(', ')}.`,
            'error'
        );
        return;
    }

    showValidationErrors.value = false;
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
                    alertTitle: 'Tramitação realizada',
                    alertMessage: 'O processo seguirá com outro setor a partir de agora.',
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
    if (stage.value?.status === 'aprovado') {
        return 'Projeto já foi tramitado e não está mais na fase de Formalização.';
    }

    if (!canUserHandleFormalization.value && stage.value?.status === 'em_andamento') {
        return 'Usuário não tem permissão para fazer alterações na Formalização';
    }

    if (stage.value?.status !== 'aprovado' && stage.value?.status !== 'em_andamento') {
        return 'Este projeto não está disponível nessa fase no momento.';
    }

    return '';
});
</script>

<template>
    <SplitScreenTab value="formalization">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <div class="font-bold text-lg d-flex justify-between">
                        <span>Dados disponíveis para consulta</span>

                        <ReturnProcessAction
                            :project="project"
                            :current-stage="currentStage"
                            :can-return="canReturn"
                            :stage-slug="STAGE_SLUG"
                            :can-user-handle="canUserHandleFormalization"
                        />
                    </div>

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

                    <SaveButton :loading="form.processing" :can-save="canEditFormalization" @click="submit" />
                </div>

                <AuxLinks />

                <SectionChips v-model="activeEditIndex" :sections="formSections" />

                <div
                    v-permission="{
                        condition: canEditFormalization,
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
                                            label="Insira a data"
                                            data-cy="asjur-finalistic-processing-date"
                                        />
                                    </FormField>

                                    <FormField label="Data de recebimento do processo pela ASJUR">
                                        <TextField
                                            v-model="form.asjur_received_at"
                                            type="date"
                                            label="Insira a data"
                                            data-cy="asjur-process-received-date"
                                        />
                                    </FormField>

                                    <div class="col-span-2 grid grid-cols-2 gap-4">
                                        <FormField label="Processo distribuído para">
                                            <TextField
                                                v-model="form.process_assigned_to"
                                                label="Insira a pessoa responsável pela análise do processo"
                                                data-cy="process-assigned-to"
                                            />
                                        </FormField>
                                    </div>

                                    <FormField label="Informe regularidade e inadimplência">
                                        <SelectField
                                            v-model="form.report_status"
                                            :items="reportStatus"
                                            item-title="label"
                                            item-value="value"
                                            placeholder="Selecione um status"
                                            label="Selecione um status"
                                            data-cy="report-status-select"
                                        />
                                    </FormField>

                                    <FormField label="Data da certidão">
                                        <TextField
                                            v-model="form.eparcerias_certificate_date"
                                            type="date"
                                            label="Insira a data em que a certidão foi gerada no e-parcerias"
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
                                            label="Insira a data de tramitação para a ASJUR"
                                            data-cy="asjur-processing-date-input"
                                        />
                                    </FormField>

                                    <FormField label="Responsável (Distribuido para)">
                                        <TextField
                                            v-model="form.responsible_at_asjur"
                                            label="Informe o responsável do projeto na ASJUR"
                                            data-cy="responsible-at-asjur-input"
                                        />
                                    </FormField>

                                    <FormField label="Número do termo" required :error="errors.term_number">
                                        <TextField
                                            v-model="form.term_number"
                                            label="Insira aqui o número do termo"
                                            :error="errors.term_number"
                                            data-cy="term-number-input"
                                        />
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'signing_term'">
                                <div class="grid grid-cols-2 gap-4">
                                    <FormField label="Data do envio para assinatura do termo">
                                        <TextField
                                            v-model="form.term_signature_sent_at"
                                            type="date"
                                            label="Insira a data de envio para o proponente"
                                            data-cy="term-signature-sent-at-input"
                                        />
                                    </FormField>

                                    <FormField
                                        label="Data da assinatura do termo"
                                        required
                                        :error="errors.term_signed_at"
                                    >
                                        <TextField
                                            v-model="form.term_signed_at"
                                            type="date"
                                            label="Insira a data de assinatura do proponente"
                                            data-cy="term-signed-at-input"
                                            :error="errors.term_signed_at"
                                        />
                                    </FormField>

                                    <FormField label="Data de envio para Gabinete">
                                        <TextField
                                            v-model="form.sent_to_office_at"
                                            type="date"
                                            label="Insira a data de envio para o gabinete"
                                            data-cy="sent-to-office-at-input"
                                        />
                                    </FormField>

                                    <FormField
                                        label="Data de assinatura do termo pelo Gabinete"
                                        required
                                        :error="errors.signed_by_office_at"
                                    >
                                        <TextField
                                            v-model="form.signed_by_office_at"
                                            type="date"
                                            label="Insira a data da assinatura pelo o gabinete"
                                            data-cy="signed-by-office-at-input"
                                            :error="errors.signed_by_office_at"
                                        />
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'sacc'">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2 grid grid-cols-2 gap-4">
                                        <FormField label="Número do SACC" required :error="errors.sacc_number">
                                            <TextField
                                                v-model="form.sacc_number"
                                                label="Insira aqui o número do SACC"
                                                data-cy="sacc-number-input"
                                                :error="errors.sacc_number"
                                            />
                                        </FormField>
                                    </div>

                                    <FormField label="Chamado CGE atende">
                                        <TextField
                                            v-model="form.cge_atende_ticket"
                                            label="Insira o chamado"
                                            data-cy="cge-atende-ticket-input"
                                        />
                                    </FormField>

                                    <FormField label="Deliberação">
                                        <SelectField
                                            v-model="form.deliberation"
                                            :items="deliberation"
                                            item-title="label"
                                            item-value="value"
                                            label="Selecione um status"
                                            data-cy="deliberation-select"
                                        />
                                    </FormField>
                                </div>
                            </template>

                            <template v-else-if="section.key === 'official_gazette'">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2 grid grid-cols-2 gap-4">
                                        <FormField label="Data de envio para Casa Civil">
                                            <TextField
                                                v-model="form.sent_to_chief_of_staff_at"
                                                label="Insira a data"
                                                data-cy="sent-to-chief-of-staff-at-input"
                                                type="date"
                                            />
                                        </FormField>
                                    </div>

                                    <FormField
                                        label="Data de Publicação do Diário Oficial do Estado"
                                        required
                                        :error="errors.official_gazette_published_at"
                                    >
                                        <TextField
                                            v-model="form.official_gazette_published_at"
                                            type="date"
                                            label="Insira a data"
                                            data-cy="official-gazette-published-at-input"
                                            :error="errors.official_gazette_published_at"
                                        />
                                    </FormField>

                                    <FormField
                                        label="Anexo do documento do Diário Oficial do Estado"
                                        :error="form.errors.official_gazette_file || errors.official_gazette_file"
                                        required
                                    >
                                        <div
                                            v-if="officialGazetteFile && !form.official_gazette_file"
                                            class="flex items-center h-[44px] border border-gray-300 rounded-lg bg-white mt-2 px-3 py-7 shadow-sm"
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
                                            class="flex items-center h-[44px] border border-gray-400 rounded bg-white overflow-hidden mt-2 mb-5 py-7"
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
                                    <FormField
                                        label="Data de início da vigência do instrumento"
                                        required
                                        :error="errors.validity_start_at"
                                    >
                                        <TextField
                                            v-model="form.validity_start_at"
                                            type="date"
                                            label="Insira a data"
                                            data-cy="instrument-validity-start-at-input"
                                            :error="errors.validity_start_at"
                                        />
                                    </FormField>

                                    <FormField
                                        label="Data de término da vigência do instrumento"
                                        required
                                        :error="errors.validity_end_at"
                                    >
                                        <TextField
                                            v-model="form.validity_end_at"
                                            type="date"
                                            label="Insira a data"
                                            data-cy="instrument-validity-end-at-input"
                                            :error="errors.validity_end_at"
                                        />
                                    </FormField>
                                </div>
                            </template>
                        </template>
                    </SectionForm>

                    <TramitButton
                        :action="tramit"
                        :disabled="!canEditFormalization"
                        :loading="tramitLoading"
                        data-cy="tramit-button"
                    />
                </div>
            </div>
        </template>
    </SplitScreenTab>
</template>
