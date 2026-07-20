<script setup>
import FormField from '@/Components/FormField.vue';
import TextField from '@/Components/TextField.vue';
import SelectField from '@/Components/SelectField.vue';

const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [String, Number, Boolean], default: null },
});

const emit = defineEmits(['update:modelValue']);
console.log('DynamicField', props.field);
const update = (value) => emit('update:modelValue', value);
</script>

<template>
    <FormField :label="field.label" :required="field.required">
        <TextField v-if="field.type === 'text'" :model-value="modelValue" @update:model-value="update" />

        <TextField
            v-else-if="field.type === 'date'"
            type="date"
            :model-value="modelValue"
            @update:model-value="update"
        />

        <v-textarea
            v-else-if="field.type === 'textarea'"
            :model-value="modelValue"
            variant="outlined"
            auto-grow
            @update:model-value="update"
        />

        <SelectField
            v-else-if="field.type === 'select'"
            :items="field.options"
            item-title="label"
            item-value="value"
            :model-value="modelValue"
            @update:model-value="update"
        />

        <v-radio-group v-else-if="field.type === 'radio'" :model-value="modelValue" inline @update:model-value="update">
            <v-radio v-for="option in field.options" :key="option.value" :label="option.label" :value="option.value" />
        </v-radio-group>
    </FormField>
</template>
