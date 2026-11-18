<script setup>
import { DOCUMENT_DOWNLOAD_OPTIONS } from '@/Schemas/Config/documentConfig';

defineProps({
    label: {
        type: String,
        default: 'Baixar',
    },
    location: {
        type: String,
        default: 'bottom',
    },
    variant: {
        type: String,
        default: 'outlined',
    },
    color: {
        type: String,
        default: 'primary',
    },
    size: {
        type: String,
        default: undefined,
    },
    buttonClass: {
        type: [String, Array, Object],
        default: '',
    },
    loading: Boolean,
    disabled: Boolean,
    iconOnly: Boolean,
    ariaLabel: {
        type: String,
        default: 'Baixar documento',
    },
});

defineEmits(['download']);
</script>

<template>
    <v-menu :location="location">
        <template #activator="{ props: menuProps }">
            <v-btn
                v-if="iconOnly"
                v-bind="menuProps"
                icon
                :size="size"
                :variant="variant"
                :color="color"
                :class="buttonClass"
                :loading="loading"
                :disabled="disabled"
                :aria-label="ariaLabel"
            >
                <v-icon size="20">mdi-download</v-icon>
            </v-btn>

            <v-btn
                v-else
                v-bind="menuProps"
                :size="size"
                :variant="variant"
                :color="color"
                :class="buttonClass"
                :loading="loading"
                :disabled="disabled"
            >
                {{ label }}
                <v-icon end size="16">mdi-chevron-down</v-icon>
            </v-btn>
        </template>

        <v-list density="compact">
            <v-list-item
                v-for="option in DOCUMENT_DOWNLOAD_OPTIONS"
                :key="option.value"
                :title="option.title"
                :prepend-icon="option.icon"
                @click="$emit('download', option.value)"
            />
        </v-list>
    </v-menu>
</template>
