<script setup>
import { computed } from 'vue';
import { useMask } from '@/Composables/useMask';

const { maskProcessNumber } = useMask();

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },

    reference: {
        type: Function,
        required: true,
    },

    subtitle: {
        type: Function,
        default: undefined,
    },

    chips: {
        type: [Array, Function],
        default: () => [],
    },

    data: {
        type: Array,
        default: () => [],
    },

    right: {
        type: Function,
        default: undefined,
    },

    actions: {
        type: Array,
        default: () => [],
    },

    selectable: {
        type: Boolean,
        default: false,
    },

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
    set: (value) => emit('update:modelValue', value),
});

const hasExtraData = computed(() => props.data.length > 2);

function toggle(item) {
    if (!props.isSelectable(item)) {
        return;
    }

    const exists = selected.value.includes(item.id);

    if (exists) {
        selected.value = selected.value.filter((id) => id !== item.id);

        return;
    }

    selected.value = [...selected.value, item.id];
}

function isChecked(item) {
    return selected.value.includes(item.id);
}

function resolveChips(item) {
    if (typeof props.chips === 'function') {
        return props.chips(item) ?? [];
    }

    return props.chips ?? [];
}

function resolveChipLabel(chip, item) {
    if (typeof chip.label === 'function') {
        return chip.label(item);
    }

    return chip.label;
}

function resolveChipColor(chip, item) {
    if (typeof chip.color === 'function') {
        return chip.color(item);
    }

    return chip.color;
}

function resolveDataValue(dataItem, item) {
    const value = typeof dataItem.value === 'function' ? dataItem.value(item) : (dataItem.value ?? '-');

    if (dataItem.label === 'Número do processo') {
        return maskProcessNumber(value) || value;
    }

    return value;
}

function runAction(action, item) {
    emit('action', {
        action,
        item,
    });
}

function getDataByLabel(label) {
    const data = {
        Fase: 'project-phase',
        'Número do processo': 'project-nup-project-list',
        'Referência de parcelas': 'project-installment-reference',
        'Status do pagamento': 'project-payment-status',
    };

    return data[label];
}
</script>

<template>
    <div class="w-full">
        <v-card
            v-if="!items.length"
            class="pa-8 d-flex flex-col items-center justify-center rounded !border-[#ccccccFF]"
            rounded="lg"
            variant="outlined"
            data-cy="project-list-empty-state"
        >
            <span class="font-weight-bold text-[#3b3b3cFF]">Não foi encontrado dados para sua pesquisa</span>
        </v-card>

        <v-card
            v-for="item in items"
            :key="item.id"
            class="mb-3 !h-auto rounded !border-[#ccccccFF] lg:!min-h-[4em]"
            rounded="lg"
            variant="outlined"
            data-cy="project-list-row-table"
        >
            <div
                class="flex w-full flex-col gap-3 p-3 lg:min-h-[4em] lg:flex-row lg:items-center lg:justify-between lg:gap-2 lg:px-4 lg:py-2"
            >
                <!-- LEFT: Checkbox + Reference -->
                <div class="flex w-full min-w-0 items-center gap-2 lg:w-auto lg:flex-shrink-0">
                    <v-checkbox
                        v-if="selectable"
                        :model-value="isChecked(item)"
                        :disabled="!isSelectable(item)"
                        hide-details
                        density="compact"
                        class="!m-0 flex-shrink-0 lg:mr-3"
                        data-cy="checkbox-project-list"
                        @update:model-value="toggle(item)"
                    />

                    <div class="min-w-0 flex-1 lg:w-[15em] lg:flex-none">
                        <div class="text-caption leading-tight text-[#3b3b3cFF]">
                            {{ reference(item).label }}
                        </div>

                        <div
                            class="truncate font-weight-bold text-[#3b3b3cFF]"
                            data-cy="agent-name-project-list"
                            :title="reference(item).value"
                        >
                            {{ reference(item).value }}
                        </div>

                        <div v-if="subtitle" class="mt-1 truncate text-caption text-[#6b6b6b]" :title="subtitle(item)">
                            {{ subtitle(item) }}
                        </div>
                    </div>
                </div>

                <!-- MIDDLE: Chips + Data -->
                <div
                    class="grid w-full min-w-0 grid-cols-2 gap-x-3 gap-y-2 sm:grid-cols-3 lg:flex lg:flex-1 lg:items-start lg:gap-y-0"
                    :class="{
                        /*
                         * Payment layout: more fields, so use
                         * smaller and more adaptive spacing.
                         */
                        'lg:justify-center lg:gap-x-3 xl:gap-x-6': hasExtraData,

                        /*
                         * Other phases: restores the more spacious
                         * appearance of the original component.
                         */
                        'lg:justify-center lg:gap-x-[3.5em]': !hasExtraData,
                    }"
                >
                    <!-- Chips -->
                    <div
                        v-if="resolveChips(item).length"
                        class="col-span-2 flex min-w-0 flex-col sm:col-span-3 lg:col-span-1 lg:w-auto lg:flex-shrink-0"
                    >
                        <!-- Keeps chips vertically aligned with data -->
                        <div class="hidden text-caption opacity-0 lg:block lg:mb-1">Label</div>

                        <div class="flex max-w-full flex-wrap gap-2 lg:flex-nowrap">
                            <v-chip
                                v-for="(chip, index) in resolveChips(item)"
                                :key="index"
                                size="small"
                                rounded="full"
                                class="!h-[2em] max-w-full lg:ml-[-1.5em]"
                                data-cy="chip-project-list"
                                :color="resolveChipColor(chip, item)"
                            >
                                <span class="truncate">
                                    {{ resolveChipLabel(chip, item) }}
                                </span>
                            </v-chip>
                        </div>
                    </div>

                    <!-- Data blocks -->
                    <div
                        v-for="(dataItem, index) in data"
                        :key="`${dataItem.label}-${index}`"
                        class="min-w-0 text-left lg:text-center"
                        :class="{
                            /*
                             * Payment fields need narrower blocks
                             * so all four fields continue fitting.
                             */
                            'lg:w-[7.5em] lg:flex-shrink xl:w-[9em] 2xl:w-[10em]': hasExtraData,

                            /*
                             * Normal phases keep the original width.
                             */
                            'lg:w-[10em] lg:flex-none': !hasExtraData,
                        }"
                    >
                        <div class="truncate text-caption leading-tight text-[#3b3b3cFF]" :title="dataItem.label">
                            {{ dataItem.label }}
                        </div>

                        <div
                            class="truncate font-weight-bold leading-tight text-[#3b3b3cFF]"
                            :data-cy="getDataByLabel(dataItem.label)"
                            :title="String(resolveDataValue(dataItem, item))"
                        >
                            {{ resolveDataValue(dataItem, item) }}
                        </div>
                    </div>

                    <!-- Optional right content -->
                    <div v-if="right" class="col-span-2 min-w-0 sm:col-span-1 lg:col-span-1 lg:w-[8em] lg:text-center">
                        {{ right(item) }}
                    </div>
                </div>

                <!-- RIGHT: Actions -->
                <div v-if="actions.length" class="flex w-full flex-shrink-0 justify-end gap-2 lg:w-auto">
                    <v-btn
                        v-for="action in actions"
                        :key="action.name"
                        size="small"
                        color="#004c27FF"
                        variant="ghost"
                        class="leading-normal max-sm:flex-1"
                        data-cy="open-project-opening-tab-button"
                        @click.stop="runAction(action.name, item)"
                    >
                        <v-icon v-if="action.icon" :icon="action.icon" size="small" class="mr-1" />

                        {{ action.label }}
                    </v-btn>
                </div>
            </div>
        </v-card>
    </div>
</template>
