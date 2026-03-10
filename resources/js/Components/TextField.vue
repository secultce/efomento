<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: [Number, String],
  required: Boolean,
  money: Boolean,
  capitalize: Boolean,
  mask: String,
  clearable: Boolean
})

const emit = defineEmits(['update:modelValue'])

const rules = computed(() => {
  if (!props.required) return []
  return [v => !!v || 'Campo obrigatório']
})

const formatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL'
})

const displayValue = computed(() => {
  let value = props.modelValue

  if (!value) return ''

  if (props.money) {
    return formatter.format(value)
  }

  if (props.mask) {
    return applyMask(String(value))
  }

  if (props.capitalize && typeof value === 'string') {
    return capitalizeWords(value)
  }

  return value
})

function capitalizeWords(value) {
  if (!value) return value

  return value
    .toLowerCase()
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

function applyMask(value) {
  if (!props.mask) return value

  const digits = value.replace(/\D/g, '')
  let result = ''
  let digitIndex = 0

  for (let i = 0; i < props.mask.length; i++) {
    if (props.mask[i] === '#') {
      if (digits[digitIndex] === undefined) break
      result += digits[digitIndex]
      digitIndex++
    } else {
      result += props.mask[i]
    }
  }

  return result
}

function removeMask(value) {
  if (!props.mask) return value
  return value.replace(/\D/g, '')
}

function handleInput(value) {
  if (props.money) {
    const digits = value.replace(/\D/g, '')
    const numeric = Number(digits) / 100

    emit('update:modelValue', numeric)
    return
  }

  let newValue = value

  if (props.capitalize) {
    newValue = capitalizeWords(newValue)
  }

  // remove mask before emitting
  if (props.mask) {
    newValue = newValue.replace(/\D/g, '')
  }

  emit('update:modelValue', newValue)
}

function handleKeypress(e) {
  if (!props.money) return

  const key = e.key

  // allow digits
  if (/[0-9]/.test(key)) return

  // allow control keys
  if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(key)) return

  e.preventDefault()
}
</script>

<template>
<v-text-field
  v-bind="$attrs"
  :model-value="displayValue"
  :rules="rules"
  @update:model-value="handleInput"
  :inputmode="money ? 'decimal' : undefined"
  @keypress="handleKeypress"
  :clearable="clearable"
  variant="outlined"
/>
</template>