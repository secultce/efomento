<script setup>
import { computed, watch, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

import AppTextEditor from '@/Components/AppTextEditor.vue';
import DocumentImageUpload from '@/Components/DocumentImageUpload.vue';

import { useSnackbar } from '@/Composables/useSnackbar';
import { useDocumentImages } from '@/Composables/useDocumentImages';

import { saveDocument } from '@/Services/documentService';

import { documentConfigs } from '@/Schemas/Config/documentConfig';

const IMAGE_LAYOUTS = {
    NONE: 'none',
    THREE: 'three',
    FULL: 'full',
};

const props = defineProps({
    modelValue: Boolean,

    type: {
        type: String,
        required: true,
    },

    projectIds: {
        type: Array,
        default: () => [],
    },

    noticeId: {
        type: Number,
        default: null,
    },

    editData: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const { showSnackbar } = useSnackbar();

const { headerImages, footerImages, normalizeImages, handleImage, removeImage, appendImages, resetImages } =
    useDocumentImages();

const config = computed(() => {
    return documentConfigs[props.type];
});

const dialogTitle = computed(() => {
    return props.editData ? config.value.titleEdit : config.value.titleCreate;
});

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => {
        if (!val) {
            closeDialog();
            return;
        }

        emit('update:modelValue', val);
    },
});

const form = useForm({
    content: '',
});

const headerLayout = ref(IMAGE_LAYOUTS.NONE);
const footerLayout = ref(IMAGE_LAYOUTS.NONE);

const layoutOptions = [
    {
        title: 'Sem imagens',
        value: IMAGE_LAYOUTS.NONE,
    },
    {
        title: '3 imagens',
        value: IMAGE_LAYOUTS.THREE,
    },
    {
        title: 'Imagem largura total',
        value: IMAGE_LAYOUTS.FULL,
    },
];

const showHeaderImages = computed(() => {
    return headerLayout.value !== IMAGE_LAYOUTS.NONE;
});

const showFooterImages = computed(() => {
    return footerLayout.value !== IMAGE_LAYOUTS.NONE;
});

const headerSlots = computed(() => {
    return headerLayout.value === IMAGE_LAYOUTS.FULL ? 1 : 3;
});

const footerSlots = computed(() => {
    return footerLayout.value === IMAGE_LAYOUTS.FULL ? 1 : 3;
});

const dialogHeight = computed(() => {
    if (showHeaderImages.value || showFooterImages.value) {
        return 920;
    }

    return 654;
});

const selectedPlaceholders = ref([]);
const placeholderSearch = ref('');
const placeholderInsertionId = ref(0);

const placeholderOptions = computed(() => [
    ...selectedPlaceholders.value,
    ...(config.value.placeholders ?? []).map((placeholder) => ({
        ...placeholder,
        insertionId: `${placeholderInsertionId.value}:${placeholder.value}`,
    })),
]);

function syncPlaceholders(_event, editor) {
    const placeholders = new Map(
        (config.value.placeholders ?? []).map((placeholder) => [placeholder.value, placeholder])
    );
    const selected = new Map();

    for (const node of editor.dom.select('[data-placeholder-insertion]')) {
        const insertionId = node.getAttribute('data-placeholder-insertion');
        const [sequence, value] = insertionId.split(':');
        const placeholder = placeholders.get(value);
        if (!placeholder || !/^\d+$/.test(sequence)) continue;

        selected.set(insertionId, { ...placeholder, insertionId });
        placeholderInsertionId.value = Math.max(placeholderInsertionId.value, Number(sequence) + 1);
    }

    selectedPlaceholders.value = [...selected.values()];
}

function updatePlaceholders(placeholders) {
    const editor = window.tinymce?.activeEditor;
    if (!editor) return;

    const previousIds = new Set(selectedPlaceholders.value.map((placeholder) => placeholder.insertionId));
    const nextIds = new Set(placeholders.map((placeholder) => placeholder.insertionId));
    const added = placeholders.filter((placeholder) => !previousIds.has(placeholder.insertionId));
    const removed = selectedPlaceholders.value.filter((placeholder) => !nextIds.has(placeholder.insertionId));

    editor.undoManager.transact(() => {
        for (const placeholder of removed) {
            const nodes = editor.dom.select(`[data-placeholder-insertion="${placeholder.insertionId}"]`);
            nodes.forEach((node) => editor.dom.remove(node));
        }

        for (const placeholder of added) {
            editor.insertContent(
                `<span contenteditable="false" data-placeholder-insertion="${placeholder.insertionId}">[${placeholder.value}]</span>`
            );
        }
    });
    editor.nodeChanged();
    form.content = editor.getContent();
    selectedPlaceholders.value = placeholders;

    if (added.length) {
        placeholderInsertionId.value += 1;
        placeholderSearch.value = '';
    }
}

function closeDialog() {
    form.reset();

    form.clearErrors();

    resetImages();

    selectedPlaceholders.value = [];
    placeholderInsertionId.value = 0;
    placeholderSearch.value = '';

    headerLayout.value = IMAGE_LAYOUTS.NONE;

    footerLayout.value = IMAGE_LAYOUTS.NONE;

    emit('update:modelValue', false);
}

function restoreEditablePlaceholders(content) {
    return String(content ?? '')
        .replace(
            /<!--\s*budget_result_table:start\s*-->[\s\S]*?<!--\s*budget_result_table:end\s*-->/gi,
            '[budget_result_table]'
        )
        .replace(
            /<table\b[^>]*data-document-placeholder=["']budget_result_table["'][^>]*>[\s\S]*?<\/table>/gi,
            '[budget_result_table]'
        )
        .replace(/<table\b[^>]*>[\s\S]*?<\/table>/gi, (table) =>
            /CÓDIGO\s*<br\s*\/?>\s*INSCRIÇÃO\s*<br\s*\/?>\s*MAPAS/i.test(table) ? '[budget_result_table]' : table
        );
}

function buildPayload() {
    const payload = new FormData();

    props.projectIds.forEach((id) => {
        payload.append('selected_projects[]', id);
    });

    if (props.noticeId) {
        payload.append('notice_id', props.noticeId);
    }

    payload.append('type', props.type);
    const document = new DOMParser().parseFromString(form.content, 'text/html');
    document.querySelectorAll('[data-placeholder-insertion]').forEach((node) => {
        node.replaceWith(...node.childNodes);
    });
    payload.append('content', document.body.innerHTML);

    payload.append('header_layout', headerLayout.value);
    payload.append('footer_layout', footerLayout.value);

    if (showHeaderImages.value) {
        appendImages(payload, 'header_images', headerImages.value.slice(0, headerSlots.value));
    }

    if (showFooterImages.value) {
        appendImages(payload, 'footer_images', footerImages.value.slice(0, footerSlots.value));
    }

    return payload;
}

function handleSave() {
    const payload = buildPayload();

    saveDocument(payload, {
        forceFormData: true,

        onSuccess: () => {
            showSnackbar('Documento salvo com sucesso!', 'success');

            emit('saved');

            closeDialog();
        },

        onError: (errors) => {
            const message = Object.values(errors).flat().join(', ') || 'Erro ao salvar documento';

            showSnackbar(message, 'error');
        },
    });
}

watch(
    () => headerLayout.value,
    (layout) => {
        if (layout === IMAGE_LAYOUTS.NONE) {
            headerImages.value = [null, null, null];
        }

        if (layout === IMAGE_LAYOUTS.FULL) {
            headerImages.value = [headerImages.value[0] || null, null, null];
        }
    }
);

watch(
    () => footerLayout.value,
    (layout) => {
        if (layout === IMAGE_LAYOUTS.NONE) {
            footerImages.value = [null, null, null];
        }

        if (layout === IMAGE_LAYOUTS.FULL) {
            footerImages.value = [footerImages.value[0] || null, null, null];
        }
    }
);

watch(
    () => isOpen.value,
    (open) => {
        if (!open) return;

        if (props.editData) {
            form.content = restoreEditablePlaceholders(props.editData.content);

            const existingHeaderImages = props.editData.headerImages || [];

            const existingFooterImages = props.editData.footerImages || [];

            if (props.editData.header_layout) {
                headerLayout.value = props.editData.header_layout;
            } else if (existingHeaderImages.length === 1 && existingHeaderImages[0]?.is_full_width) {
                headerLayout.value = IMAGE_LAYOUTS.FULL;
            } else if (
                existingHeaderImages.length > 1 ||
                (existingHeaderImages.length === 1 && !existingHeaderImages[0]?.is_full_width)
            ) {
                headerLayout.value = IMAGE_LAYOUTS.THREE;
            } else {
                headerLayout.value = IMAGE_LAYOUTS.NONE;
            }

            if (props.editData.footer_layout) {
                footerLayout.value = props.editData.footer_layout;
            } else if (existingFooterImages.length === 1 && existingFooterImages[0]?.is_full_width) {
                footerLayout.value = IMAGE_LAYOUTS.FULL;
            } else if (
                existingFooterImages.length > 1 ||
                (existingFooterImages.length === 1 && !existingFooterImages[0]?.is_full_width)
            ) {
                footerLayout.value = IMAGE_LAYOUTS.THREE;
            } else {
                footerLayout.value = IMAGE_LAYOUTS.NONE;
            }

            headerImages.value = normalizeImages(existingHeaderImages);

            footerImages.value = normalizeImages(existingFooterImages);
        } else {
            form.reset();

            resetImages();

            headerLayout.value = IMAGE_LAYOUTS.NONE;

            footerLayout.value = IMAGE_LAYOUTS.NONE;
        }
    }
);
</script>

<template>
    <v-dialog v-model="isOpen" max-width="900" :retain-focus="false">
        <v-card class="rounded-lg flex flex-col" :height="dialogHeight">
            <v-card-title class="font-weight-bold shrink-0">
                {{ dialogTitle }}
            </v-card-title>

            <v-container class="flex flex-col overflow-y-auto">
                <!-- HEADER TYPE -->
                <div class="mb-6">
                    <p class="font-semibold mb-2">Tipo de cabeçalho</p>

                    <v-select
                        v-model="headerLayout"
                        :items="layoutOptions"
                        item-title="title"
                        item-value="value"
                        variant="outlined"
                        density="comfortable"
                        hide-details
                    />
                </div>

                <!-- HEADER IMAGES -->
                <DocumentImageUpload
                    v-if="showHeaderImages"
                    title="Imagens de cabeçalho"
                    type="header"
                    :images="headerImages.slice(0, headerSlots)"
                    :slots="headerSlots"
                    @change="handleImage"
                    @remove="removeImage"
                />

                <!-- PLACEHOLDERS -->
                <v-autocomplete
                    v-if="config.placeholders?.length"
                    v-model:search="placeholderSearch"
                    :model-value="selectedPlaceholders"
                    :items="placeholderOptions"
                    item-title="label"
                    item-value="insertionId"
                    label="Inserir campo de preenchimento automático no documento"
                    autocomplete="off"
                    multiple
                    return-object
                    chips
                    closable-chips
                    hide-selected
                    variant="outlined"
                    density="compact"
                    hide-details
                    class="mb-4"
                    @update:model-value="updatePlaceholders"
                />

                <!-- EDITOR -->
                <app-text-editor
                    v-model="form.content"
                    :error="form.errors.content"
                    class="flex-grow"
                    data-cy="document-text-area"
                    @escape="closeDialog"
                    @undo="syncPlaceholders"
                    @redo="syncPlaceholders"
                    @input="syncPlaceholders"
                    @change="syncPlaceholders"
                />

                <!-- FOOTER TYPE -->
                <div class="mt-6 mb-6">
                    <p class="font-semibold mb-2">Tipo de rodapé</p>

                    <v-select
                        v-model="footerLayout"
                        :items="layoutOptions"
                        item-title="title"
                        item-value="value"
                        variant="outlined"
                        density="comfortable"
                        hide-details
                    />
                </div>

                <!-- FOOTER IMAGES -->
                <DocumentImageUpload
                    v-if="showFooterImages"
                    title="Imagens de rodapé"
                    type="footer"
                    :images="footerImages.slice(0, footerSlots)"
                    :slots="footerSlots"
                    @change="handleImage"
                    @remove="removeImage"
                />

                <!-- ACTIONS -->
                <v-card-actions class="mt-6">
                    <v-spacer />

                    <v-btn
                        variant="outlined"
                        color="#004c27"
                        class="rounded-lg"
                        data-cy="cancel-document-button"
                        @click="closeDialog"
                    >
                        Cancelar
                    </v-btn>

                    <v-btn
                        class="!shadow-none !font-bold !bg-[#ffcc05] !text-[#2d353f] rounded-lg"
                        :loading="form.processing"
                        :disabled="!form.content.trim()"
                        data-cy="save-document-button"
                        @click="handleSave"
                    >
                        Salvar
                    </v-btn>
                </v-card-actions>
            </v-container>
        </v-card>
    </v-dialog>
</template>
