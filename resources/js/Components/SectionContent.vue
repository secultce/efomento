<script setup>
import { useFormHelper } from '@/Composables/useFormHelper';
import { useSnackbar } from '@/Composables/useSnackbar';
import { ref } from 'vue';

const hoveredIndex = ref(null);
const { showSnackbar } = useSnackbar();

const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
    project: {
        type: Object,
        required: true,
    },
});

const { getFieldValue } = useFormHelper({
    project: props.project,
});

const formatters = {
    datetime: (value) => {
        if (!value) return null;
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }).format(new Date(value));
    },
};

const displayValue = (field) => {
    const raw = field.compute ? field.compute(props.project) : getFieldValue(field.key);
    return field.format && formatters[field.format] ? formatters[field.format](raw) : raw;
};

const copyValue = async (value) => {
    if (!value) return;

    try {
        await navigator.clipboard.writeText(value);

        showSnackbar('Copiado!', 'success');
    } catch (e) {
        showSnackbar('Erro ao copiar', 'error');
    }
};
</script>

<template>
    <div class="border-t pt-4">
        <p class="font-bold mb-4 uppercase text-xs tracking-wider">
            {{ section.title }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 text-sm">
            <div
                v-for="(field, index) in section.fields"
                :key="field.label"
                class="flex flex-col w-full"
                :class="{ 'md:col-span-2': field.fullWidth }"
                @mouseenter="hoveredIndex = index"
                @mouseleave="hoveredIndex = null"
            >
                <div class="flex items-center gap-2">
                    <span class="text-gray-800 text-xs">
                        {{ field.label }}
                    </span>

                    <v-icon
                        v-show="hoveredIndex === index"
                        size="14"
                        class="cursor-pointer"
                        @click.stop="copyValue(displayValue(field))"
                    >
                        mdi-content-copy
                    </v-icon>
                </div>

                <div v-if="field.items" class="space-y-1">
                    <p v-for="(item, itemIndex) in field.items(project)" :key="itemIndex" class="break-words">
                        {{ item.label }}: <span class="font-bold">{{ item.value }}</span>
                    </p>
                </div>
                <span v-else class="font-bold break-words" :class="{ 'whitespace-pre-line': field.multiline }">
                    {{ displayValue(field) }}
                </span>
            </div>
        </div>
    </div>
</template>
