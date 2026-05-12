<script setup>
defineProps({
    value: {
        type: [String, Number],
        required: true,
    },
    isTabsWindowItemVisible: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <v-tabs-window-item v-if="isTabsWindowItemVisible" :value="value">
        <v-divider />
        <v-sheet>
            <div class="flex">
                <div class="w-1/2 m-[1.5em] gap-4 flex flex-col">
                    <slot name="left-content" />
                </div>
                <v-divider inset vertical />
                <div class="w-1/2 m-[1.5em] gap-2 flex flex-col">
                    <slot name="right-content" />
                </div>
            </div>
        </v-sheet>
    </v-tabs-window-item>
</template>
