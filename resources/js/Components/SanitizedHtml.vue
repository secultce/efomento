<script setup>
import { computed } from 'vue';
import DOMPurify from 'dompurify';

const props = defineProps({
    content: {
        type: String,
        default: '',
    },
});

const cleanContent = computed(() => DOMPurify.sanitize(props.content));
</script>

<template>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div v-html="cleanContent" />
</template>
