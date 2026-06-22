<script setup>
import { computed } from 'vue';
const props = defineProps({
    items: { type: Array, default: () => [] },

    reference: { type: Function, required: true },

    // optional subtitle
    subtitle: { type: Function, default: undefined },

    // chips (badges)
    chips: {
        type: [Array, Function],
        default: () => [],
        // [{ label: 'CL', color: 'green' }] OR function
    },

    // middle info blocks
    data: {
        type: Array,
        default: () => [],
        // [{ label: 'Número', value: (item) => item.process }]
    },

    // right side content (phase, status, etc)
    right: { type: Function, default: undefined },

    // actions
    actions: {
        type: Array,
        default: () => [],
        // [{ name, label, icon }]
    },

    selectable: { type: Boolean, default: false },

    isSelectable: {
        type: Function,
        default: () => true,
    },

    modelValue: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['action', 'update:modelValue']);

const selected = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

function toggle(item) {
    if (!props.isSelectable(item)) return;

    const exists = selected.value.includes(item.id);

    if (exists) {
        selected.value = selected.value.filter((id) => id !== item.id);
    } else {
        selected.value = [...selected.value, item.id];
    }
}

function isChecked(item) {
    return selected.value.includes(item.id);
}

function resolveChips(item) {
    if (typeof props.chips === 'function') {
        return props.chips(item);
    }

    return props.chips;
}

function runAction(action, item) {
    emit('action', { action, item });
}
</script>

<template>
    <div class="w-full">
        <v-card
            v-for="item in items"
            :key="item.id"
            class="mb-3 pa-4 h-[4em] rounded d-flex justify-between items-center !border-[#ccccccFF]"
            rounded="lg"
            variant="outlined"
            data-cy="project-list-row-table"
        >
            <!-- LEFT: Checkbox + Reference -->
            <div class="d-flex items-center ga-2">
                <v-checkbox
                    v-if="selectable"
                    :model-value="isChecked(item)"
                    :disabled="!isSelectable(item)"
                    hide-details
                    density="compact"
                    class="mr-3"
                    data-cy="checkbox-project-list"
                    @update:model-value="() => toggle(item)"
                />
                <div class="w-[15em] flex-shrink-0">
                    <div class="text-caption text-[#3b3b3cFF]">
                        {{ reference(item).label }}
                    </div>
                    <div class="font-weight-bold text-[#3b3b3cFF]" data-cy="agent-name-project-list">
                        {{ reference(item).value }}
                    </div>
                </div>
            </div>

            <!-- MIDDLE: Chips + Data -->
            <div class="flex-1 d-flex justify-center items-start gap-[3.5em]">
                <!-- Chips -->
                <div v-if="resolveChips(item).length" class="d-flex flex-col">
                    <div class="text-caption opacity-0 mb-1">Label</div>
                    <!-- fake label space -->
                    <div class="d-flex gap-2">
                        <v-chip
                            v-for="(chip, i) in resolveChips(item)"
                            :key="i"
                            size="small"
                            rounded="full"
                            class="h-[2em] ml-[-1.5em]"
                            data-cy="chip-project-list"
                            :color="typeof chip.color === 'function' ? chip.color(item) : chip.color"
                        >
                            {{ typeof chip.label === 'function' ? chip.label(item) : chip.label }}
                        </v-chip>
                    </div>
                </div>

                <!-- Data blocks -->
                <div v-for="(m, i) in data" :key="i" class="flex flex-col text-center w-[10em]">
                    <div class="text-caption text-[#3b3b3cFF]">
                        {{ m.label }}
                    </div>
                    <div
                        class="font-weight-bold text-[#3b3b3cFF]"
                        :data-cy="m.label === 'Número do processo' ? 'project-nup-project-list' : null"
                    >
                        {{ m.value(item) }}
                    </div>
                </div>
            </div>

            <!-- RIGHT: Actions -->
            <div class="d-flex justify-end ga-2">
                <v-btn
                    v-for="a in actions"
                    :key="a.name"
                    size="small"
                    color="#004c27FF"
                    variant="ghost"
                    class="leading-normal"
                    data-cy="open-project-opening-tab-button"
                    @click="runAction(a.name, item)"
                >
                    {{ a.label }}
                </v-btn>
            </div>
        </v-card>
    </div>
</template>
