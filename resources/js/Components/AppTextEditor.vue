<script setup>
import { computed } from 'vue';
import Editor from '@tinymce/tinymce-vue';
import '@/plugins/tinymce/tinymce.js';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    height: { type: Number, default: 500 },
    menubar: { type: [String, Boolean], default: 'file edit view insert format tools table help' },
    statusbar: { type: Boolean, default: true },
    toolbarLocation: { type: String, default: 'top' },
    plugins: {
        type: Array,
        default: () => [
            'link',
            'lists',
            'table',
            'code',
            'image',
            'wordcount',
            'fullscreen',
            'preview',
            'searchreplace',
            'autolink',
            'directionality',
            'visualblocks',
            'visualchars',
            'insertdatetime',
            'media',
            'help',
        ],
    },
    toolbar: {
        type: String,
        default: `
            undo redo | blocks fontfamily fontsize |
            bold italic underline strikethrough |
            forecolor backcolor |
            alignleft aligncenter alignright alignjustify |
            bullist numlist outdent indent |
            link image media table |
            insertdatetime |
            searchreplace |
            code preview fullscreen |
            removeformat help
        `,
    },
});

const emit = defineEmits(['update:modelValue']);

const editorValue = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const tinyBaseUrl = import.meta.env.VITE_TINYMCE_BASE_URL;

const editorInit = computed(() => ({
    base_url: tinyBaseUrl,
    language: 'pt_BR',
    language_url: `${tinyBaseUrl}/langs/pt_BR.js`,
    license_key: 'gpl',
    menubar: props.menubar,
    promotion: false,
    plugins: props.plugins,
    toolbar: props.toolbar,
    toolbar_location: props.toolbarLocation,
    statusbar: props.statusbar,
    placeholder: props.placeholder,
    branding: false,
    ui_container: '.editor-container',
    height: props.height,
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
}));
</script>

<template>
    <div ref="editorWrapper">
        <label v-if="label" class="text-subtitle-2 mb-1 block">
            {{ label }}
        </label>

        <Editor v-model="editorValue" :init="editorInit" />

        <div v-if="error" class="text-red-600 text-caption mt-1">
            {{ error }}
        </div>
    </div>
</template>
