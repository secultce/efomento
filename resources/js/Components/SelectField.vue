<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: null,
    items: Array,
    required: Boolean,
    clearable: Boolean
})

const emit = defineEmits(['update:modelValue'])

const rules = computed(() => {
    if (!props.required) return []

    return [
        v => !!v || 'Campo obrigatório'
    ]
})
</script>

<template>
<v-select
    v-bind="$attrs"
    :items="items"
    :model-value="modelValue"
    :rules="rules"
    :clearable="clearable"
    @update:model-value="emit('update:modelValue', $event)"
    variant="outlined"
/>
</template>