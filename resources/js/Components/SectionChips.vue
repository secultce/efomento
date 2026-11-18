<script setup>
defineProps({
    modelValue: {
        type: [Number, String],
        required: true,
    },
    sections: {
        type: Array,
        required: true,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="w-full">
        <v-chip-group
            :model-value="modelValue"
            mandatory
            selected-class="bg-primary text-white"
            column
            @update:model-value="$emit('update:modelValue', $event)"
        >
            <v-chip value="all" filter variant="outlined"> Ver tudo </v-chip>

            <v-chip v-for="(section, index) in sections" :key="section.title" :value="index" filter variant="outlined">
                {{ section.title }}
            </v-chip>
        </v-chip-group>
    </div>
</template>
