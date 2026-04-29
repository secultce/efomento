<script setup>
import FormField from './FormField.vue'
import SelectField from './SelectField.vue'
import TextField from './TextField.vue'
import { useDate } from '@/Composables/useDate'

const props = defineProps({
    sections: {
        type: Array,
        required: true
    },
    activeEditIndex: {
        type: [Number, String],
        required: true
    },
    project: {
        type: Object,
        required: true
    },
    form: Object
})

const emit = defineEmits(['update:field'])

const { normalizeDate } = useDate()

const updateValue = (key, value) => {
    emit('update:field', { key, value })
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

</script>

<template>
  <template v-for="(section, index) in sections" :key="'form-' + section.title">
    <div v-if="activeEditIndex === index || activeEditIndex === 'all'" class="space-y-4">
      
      <p class="font-bold mt-4 uppercase text-xs tracking-wider">
        {{ section.title }}
      </p>

      <div class="grid grid-cols-2 gap-4">
        
        <div v-for="field in section.fields" :key="'field-' + field.key">

          <form-field
            :label="field.label"
            :required="field.required"
          >
            
            <!-- TEXT -->
            <text-field
              v-if="field.inputType === 'text'"
              :model-value="getFieldValue(field.key)"
              @update:model-value="updateValue(field.key, $event)"
              :mask="field.mask"
              :type="field.type || 'text'"
              :placeholder="field.placeholder"
              :disabled="!field.isEditable"
              clearable
            />

            <!-- SELECT -->
            <select-field
              v-else-if="field.inputType === 'select'"
              :model-value="getFieldValue(field.key)"
              @update:model-value="updateValue(field.key, $event)"
              :items="field.options"
              :placeholder="field.placeholder"
              disabled="!field.isEditable"
              clearable
            />

            <!-- DATE -->
            <text-field
              v-else-if="field.inputType === 'date'"
              :model-value="normalizeDate(getFieldValue(field.key))"
              @update:model-value="updateValue(field.key, $event)"
              :placeholder="field.placeholder"
              :disabled="!field.isEditable"
              type="date"
            />

        </form-field>

        </div>
      </div>

    </div>
  </template>
</template>
