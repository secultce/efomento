<script setup>
import { computed } from 'vue';
import Editor from '@tinymce/tinymce-vue';
import '@/plugins/tinymce/tinymce.js';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const editorValue = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const tinyBaseUrl = import.meta.env.VITE_TINYMCE_BASE_URL;
</script>

<template>
    <div ref="editorWrapper">
        <label v-if="label" class="text-subtitle-2 mb-1 block">
            {{ label }}
        </label>

        <Editor
            v-model="editorValue"
            :init="{
                base_url: tinyBaseUrl,
                language: 'pt_BR',
                language_url: `${tinyBaseUrl}/langs/pt_BR.js`,
                license_key: 'gpl',
                menubar: 'file edit view insert format tools table help',
                promotion: false,
                plugins: [
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
                toolbar: `
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
                branding: false,
                ui_container: '.editor-container',
                height: 500,
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            }"
        />

        <div v-if="error" class="text-red-600 text-caption mt-1">
            {{ error }}
        </div>
    </div>
</template>
