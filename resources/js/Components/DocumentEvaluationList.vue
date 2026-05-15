<script setup>
import DocumentEvaluationItem from '@/Components/DocumentEvaluationItem.vue';

defineProps({
    groups: {
        type: Array,
        required: true,
    },
    projectId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['status-updated']);
</script>

<template>
    <div class="flex flex-col gap-6">
        <div v-for="group in groups" :key="group.group" class="flex flex-col gap-2">
            <div class="flex flex-col gap-2">
                <DocumentEvaluationItem
                    v-for="file in group.files"
                    :key="file.id"
                    :file="file"
                    :project-id="projectId"
                    @status-updated="emit('status-updated', $event)"
                />
            </div>
        </div>
    </div>
</template>
