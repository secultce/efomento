<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: Boolean,
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    loading: Boolean,
    canConfirm: Boolean,
    hasExistingAllocations: Boolean,
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
</script>

<template>
    <v-dialog v-model="isOpen" width="1200" max-width="calc(100vw - 32px)">
        <v-card class="rounded-lg pa-4">
            <v-card-title class="font-weight-bold !text-lg"> Informações da vinculação orçamentária </v-card-title>

            <v-card-text>
                <div class="max-h-[65vh] overflow-auto border border-[#22c7b8]">
                    <table class="w-max min-w-full border-collapse text-xs">
                        <thead class="sticky top-0 z-10 bg-white">
                            <tr>
                                <th
                                    v-for="column in columns"
                                    :key="column.key"
                                    class="whitespace-nowrap border border-gray-500 px-2 py-1 text-left font-bold"
                                >
                                    {{ column.label }}
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(row, rowIndex) in rows" :key="rowIndex">
                                <td
                                    v-for="column in columns"
                                    :key="column.key"
                                    class="min-w-40 max-w-64 whitespace-normal border border-gray-500 px-2 py-1 align-top"
                                >
                                    {{ row[column.key] ?? '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </v-card-text>

            <v-card-actions class="flex w-full items-center justify-between gap-3 px-2 pt-4">
                <v-btn
                    class="justify-self-start rounded-lg px-4 py-2 text-xs !bg-[#ffcc05FF] !font-bold !text-[#2d353fFF] !shadow-none"
                    :disabled="!canConfirm || loading"
                    :loading="loading"
                    @click="$emit('confirm')"
                >
                    {{ hasExistingAllocations ? 'Atualizar importação' : 'Confirmar importação' }}
                </v-btn>

                <v-btn
                    variant="outlined"
                    color="outlineSecondary"
                    class="rounded-lg px-4 py-2 text-xs !font-bold !shadow-none"
                    :disabled="loading"
                    @click="isOpen = false"
                >
                    Voltar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
