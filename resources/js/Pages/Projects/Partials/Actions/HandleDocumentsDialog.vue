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
    set: (val) => emit('update:modelValue', val),
});

const form = useForm({
    content: '',
});

const headerLayout = ref(IMAGE_LAYOUTS.NONE);
const footerLayout = ref(IMAGE_LAYOUTS.NONE);

const layoutOptions = [
    {
        title: 'Sem cabeçalho',
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

function insertPlaceholder(value) {
    window.tinymce?.activeEditor?.insertContent(value);
}

function closeDialog() {
    form.reset();

    form.clearErrors();

    resetImages();

    headerLayout.value = IMAGE_LAYOUTS.NONE;

    footerLayout.value = IMAGE_LAYOUTS.NONE;

    emit('update:modelValue', false);
}

function buildPayload() {
    const payload = new FormData();

    props.projectIds.forEach((id) => {
        payload.append('selected_projects[]', id);
    });

    payload.append('type', props.type);
    payload.append('content', form.content);

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
            form.content = props.editData.content || '';

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
    <v-dialog v-model="isOpen" max-width="900" :retain-focus="false" persistent>
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
                <div class="flex flex-wrap gap-2 mb-4">
                    <v-chip
                        v-for="p in config.placeholders"
                        :key="p.value"
                        size="small"
                        color="primary"
                        variant="outlined"
                        @click="insertPlaceholder(p.value)"
                    >
                        {{ p.label }}
                    </v-chip>
                </div>

                <!-- EDITOR -->
                <app-text-editor
                    v-model="form.content"
                    :error="form.errors.content"
                    class="flex-grow"
                    data-cy="document-text-area"
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

                    <v-btn variant="outlined" color="#004c27" class="rounded-lg" @click="closeDialog"> Cancelar </v-btn>

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
