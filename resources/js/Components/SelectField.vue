<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, Object, Array, Boolean], default: null },
    items: { type: Array, default: () => [] },
    required: Boolean,
    clearable: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const rules = computed(() => {
    if (!props.required) return [];

    return [(v) => !!v || 'Campo obrigatório'];
});
</script>

<template>
    <v-select
        v-bind="$attrs"
        :items="items"
        :model-value="modelValue"
        :rules="rules"
        :clearable="clearable"
        variant="outlined"
        @update:model-value="emit('update:modelValue', $event)"
    />
</template>
