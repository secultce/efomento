<script setup>
defineProps({
    fluid: {
        type: Boolean,
        default: true,
    },
    paddingY: {
        type: String,
        default: 'py-6',
    },
    paddingX: {
        type: String,
        default: 'px-0',
    },
    variant: {
        type: String,
        default: 'default',
    },
});
</script>

<template>
    <div :class="variant === 'large' ? '[mx-[1em] lg:mx-[2em] w-full]' : '[mx-[2em] lg:mx-[10em] max-w-fit]'">
        <v-container :fluid="fluid" :class="[paddingY, paddingX]">
            <slot />
        </v-container>
    </div>
</template>
