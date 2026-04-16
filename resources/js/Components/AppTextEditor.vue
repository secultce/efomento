<script setup>
import { ref, watch } from 'vue'
import Editor from '@tinymce/tinymce-vue'
import '@/plugins/tinymce'

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
  ? `${import.meta.env.VITE_TINYMCE_BASE_URL}/tinymce`
  : 'http://localhost:5173/tinymce'
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
                license_key: 'gpl',
                menubar: false,
                plugins: [
                    'link', 'lists', 'table', 'code', 'image', 'wordcount'
                ],
                toolbar: `
                    undo redo |
                    bold italic underline |
                    bullist numlist |
                    link table image |
                    code
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