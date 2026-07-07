<script setup>
import { ref, watch } from 'vue';
import { useDate } from '@/Composables/useDate';
import { useMask } from '@/Composables/useMask';

const { formatDate, normalizeDate } = useDate();
const { applyMask } = useMask();

const props = defineProps({
    modelValue: { type: [String, Number, Array, Object, Date], default: null },
    type: {
        type: String,
        default: 'text',
    },
    mask: { type: String, default: undefined },
    format: { type: Function, default: undefined },
    items: {
        type: Array,
        default: () => [],
    },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const editing = ref(false);
const localValue = ref(props.modelValue);

watch(
    () => props.modelValue,
    (val) => {
        if (props.type === 'date' && val) {
            localValue.value = normalizeDate(val);
        } else {
            localValue.value = val;
        }
    }
);

const startEdit = () => {
    editing.value = true;
};

const stopEdit = () => {
    editing.value = false;
    emit('update:modelValue', localValue.value);
};
</script>

<template>
    <p>
        <span class="font-bold">{{ label }} </span>
        <!-- EDIT MODE -->
        <template v-if="editing">
            <!-- TEXT / NUMBER -->
            <v-text-field
                v-if="type === 'text' || type === 'number' || type === 'email'"
                :model-value="props.mask ? applyMask(localValue, props.mask) : localValue"
                :type="type"
                density="compact"
                autofocus
                hide-details
                :error-messages="error"
                data-cy="notice-edit-textfield"
                @update:model-value="
                    (val) => {
                        localValue = props.mask ? val.replace(/\D/g, '') : val;
                    }
                "
                @blur="stopEdit"
                @keyup.enter="stopEdit"
            />

            <!-- TEXTAREA -->
            <v-textarea
                v-else-if="type === 'textarea'"
                v-model="localValue"
                density="compact"
                :error-messages="error"
                auto-grow
                data-cy="notice-edit-textarea"
                @blur="stopEdit"
            />

            <!-- SELECT -->
            <v-select
                v-else-if="type === 'select'"
                v-model="localValue"
                :items="items"
                :error-messages="error"
                density="compact"
                data-cy="notice-edit-select"
                @update:model-value="stopEdit"
            />

            <!-- DATE -->
            <v-text-field
                v-else-if="type === 'date'"
                v-model="localValue"
                type="date"
                density="compact"
                :error-messages="error"
                @blur="stopEdit"
            />
        </template>

        <!-- DISPLAY MODE -->
        <template v-else>
            <span
                :class="
                    disabled
                        ? 'cursor-not-allowed opacity-60 px-1 rounded'
                        : 'hover:bg-gray-100 cursor-pointer px-1 rounded'
                "
                @click="!disabled && startEdit()"
            >
                <!-- DATE FORMAT -->
                <template v-if="type === 'date'">
                    {{ modelValue ? formatDate(modelValue) : '-' }}
                </template>

                <!-- formatted -->
                <template v-else-if="format">
                    {{ modelValue ? format(modelValue) : '-' }}
                </template>

                <!-- select label -->
                <template v-else-if="type === 'select'">
                    {{ modelValue ? (items.find((i) => i.value === modelValue)?.title ?? modelValue) : '-' }}
                </template>

                <!-- default -->
                <template v-else>
                    {{ modelValue ? (props.mask ? applyMask(String(modelValue), props.mask) : modelValue) : '-' }}
                </template>
            </span>
            <div v-if="error" class="text-red-500 text-xs mt-1">
                {{ error }}
            </div>
        </template>
    </p>
</template>
