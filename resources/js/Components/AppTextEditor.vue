<script setup>
import { ref, watch } from 'vue'
import Editor from '@tinymce/tinymce-vue'
import '@/plugins/tinymce/tinymce.js'

const props = defineProps({
    modelValue: String,
    label: String,
    error: String,
})

const emit = defineEmits(['update:modelValue'])

const editorWrapper = ref(null)

const content = ref(props.modelValue)

watch(content, (val) => {
    emit('update:modelValue', val)
})

watch(() => props.modelValue, (val) => {
    content.value = val
})

const tinyBaseUrl = import.meta.env.VITE_TINYMCE_BASE_URL
</script>

<template>
    <div ref="editorWrapper">
        <label v-if="label" class="text-subtitle-2 mb-1">
            {{ label }}
        </label>

        <Editor
            v-model="content"
            :init="{
                base_url: tinyBaseUrl,
                language: 'pt_BR',
                language_url: `${tinyBaseUrl}/langs/pt_BR.js`,
                license_key: 'gpl',
                menubar: 'file edit view insert format tools table help',
                promotion: false,
                plugins: [
                    'link', 'lists', 'table', 'code', 'image',
                    'wordcount', 'fullscreen', 'preview',
                    'searchreplace', 'autolink', 'directionality',
                    'visualblocks', 'visualchars',
                    'insertdatetime', 'media', 'help'
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
                ui_container: editorWrapper,
                height: 600
            }"
        />

        <div v-if="error" class="text-error text-caption mt-1">
            {{ error }}
        </div>
    </div>
</template>