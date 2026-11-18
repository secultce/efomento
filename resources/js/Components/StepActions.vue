<script setup>
import { useSlots } from 'vue';

const slots = useSlots();
</script>

<template>
    <div class="flex w-full gap-6 mt-4" :class="{ 'justify-center': !slots.save }">
        <div v-if="slots.save" class="flex-1">
            <slot name="save" />
        </div>
        <div :class="slots.save ? 'flex-1' : 'w-1/2'">
            <slot />
        </div>
    </div>
</template>
