<script setup>
import { useSnackbar } from '@/Composables/useSnackbar'
import { ref } from 'vue'

const hoveredIndex = ref(null)
const { showSnackbar } = useSnackbar() 

const props = defineProps({
  section: {
    type: Object,
    required: true
  },
  project: {
    type: Object,
    required: true
  }
})

const formatDate = (date) => {
  if (!date) return '—'
  const d = new Date(date)
  return isNaN(d) ? '—' : d.toLocaleDateString('pt-BR')
}

const resolvePath = (obj, key) => {
  if (!obj) return { exists: false, value: undefined }

  const path = key
    .replace(/\[(\d+)\]/g, '.$1')
    .split('.')

  let current = obj

  for (const part of path) {
    if (current == null || !(part in current)) {
      return { exists: false, value: undefined }
    }
    current = current[part]
  }

  return { exists: true, value: current }
}

const getFieldValue = (key) => {
  const formResult = resolvePath(props.form, key)

  if (formResult.exists) {
    return formResult.value
  }

  const projectResult = resolvePath(props.project, key)

  return projectResult.exists ? projectResult.value : null
}

const copyValue = async (value) => {
  if (!value) return

  try {
    await navigator.clipboard.writeText(value)

    showSnackbar('Copiado!', 'success')
  } catch (e) {
    console.error('Erro ao copiar', e)
    showSnackbar('Erro ao copiar', 'error')
  }
}
</script>

<template>
  <div class="border-t pt-4">
    <p class="font-bold mb-4 uppercase text-xs tracking-wider">
      {{ section.title }}
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 text-sm">
      <div v-for="(field, index) in section.fields" :key="field.label" class="flex flex-col w-full"
        @mouseenter="hoveredIndex = index" @mouseleave="hoveredIndex = null">
        <div class="flex items-center  gap-2">
          <span class="text-gray-800 text-xs">
            {{ field.label }}
          </span>

          <v-icon v-show="hoveredIndex === index" size="14" class="cursor-pointer"
            @click.stop="copyValue(getFieldValue(field))">
            mdi-content-copy
          </v-icon>
        </div>

        <span class="font-bold break-words">
          {{ getFieldValue(field.key) }}
        </span>
      </div>
    </div>
  </div>
</template>
