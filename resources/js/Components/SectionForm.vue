<script setup>
const props = defineProps({
    sections: {
        type: Array,
        required: true
    },
    activeEditIndex: {
        type: [Number, String],
        required: true
    },
    form: Object
})

</script>

<template>
  <template v-for="(section, index) in sections" :key="'form-' + section.title">
    <div v-if="activeEditIndex === index || activeEditIndex === 'all'" class="space-y-4">
      <p class="font-bold mt-4 uppercase text-xs tracking-wider">
        {{ section.title }}
      </p>
      <div>
        <slot :section="section" :index="index" />
      </div>
    </div>
  </template>
</template>
