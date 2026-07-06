<script setup>
const props = defineProps({
    action: {
        type: Function,
        required: true,
    },

    loading: {
        type: Boolean,
        default: false,
    },

    disabled: {
        type: Boolean,
        default: false,
    },

    phase: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="w-full justify-center flex">
        <v-btn
            class="w-1/2 mt-4 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
            :loading="props.loading"
            :disabled="props.disabled || props.loading"
            data-cy="tramit-button"
            @click="props.action"
        >
            <slot>tramitar</slot>
        </v-btn>
    </div>
</template>
