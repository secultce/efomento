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
  maxWidth: {
    type: String,
    default: 'max-w-fit',
  },
})
</script>

<template>
  <div :class="['mx-auto', maxWidth, 'px-0']">
    <v-container
      :fluid="fluid"
      :class="[paddingY, paddingX]"
    >
      <slot />
    </v-container>
  </div>
</template>