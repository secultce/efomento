<script setup>
import { useSlots, cloneVNode } from 'vue'

const props = defineProps({
    label: String,
    error: String,
    required: Boolean,
    clearable: Boolean
})

const slots = useSlots()
</script>

<template>
    <div>
        <label class="text-xs font-weight-bold d-block">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <component v-for="vnode in slots.default?.()" :is="cloneVNode(vnode, { required, clearable })" />

        <p v-if="error" class="text-red-500 text-xs">
            {{ error }}
        </p>
    </div>
</template>