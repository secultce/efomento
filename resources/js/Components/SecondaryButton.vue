<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex bg-[#485465FF] items-center justify-center rounded-md px-4 py-2 text-xs font-semibold tracking-widest transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 !text-white"
    >
        <slot />
    </button>
</template>
