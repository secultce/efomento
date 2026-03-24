<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: [String, Number, Array, Object, Date],
    type: {
        type: String,
        default: 'text', //text | number | textarea | select | date
    },

    mask: String,

    format: Function,

    items: {
        type: Array,
        default: () => []
    },

    label: String,

    error: String,
})

const emit = defineEmits(['update:modelValue'])

const editing = ref(false)
const localValue = ref(props.modelValue)

const formatDate = (value) => {
    if (!value) return '-'

    if (typeof value === 'string' && value.includes('-')) {
        const [year, month, day] = value.slice(0, 10).split('-')
        return `${day}/${month}/${year}`
    }

    const date = new Date(value)

    return date.toLocaleDateString('pt-BR')
}

const normalizeDate = (value) => {
    if (!value) return null

    if (typeof value === 'string' && value.includes('-')) {
        return value.slice(0, 10)
    }

    const date = new Date(value)

    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
}

function applyMask(value) {
    if (!props.mask) return value
    if (!value) return ''
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

watch(() => props.modelValue, (val) => {
    if (props.type === 'date' && val) {
        localValue.value = normalizeDate(val)
    } else {
        localValue.value = val
    }
})

const startEdit = () => {
    editing.value = true
}

const stopEdit = () => {
    editing.value = false
    emit('update:modelValue', localValue.value)
}


</script>

<template>
    <p>
        <span class="font-bold">{{ label }} </span>
        <!-- EDIT MODE -->
        <template v-if="editing">

            <!-- TEXT / NUMBER -->
            <v-text-field v-if="type === 'text' || type === 'number' || type === 'email'"
                :model-value="props.mask ? applyMask(localValue) : localValue" @update:modelValue="val => {
                    localValue = props.mask ? val.replace(/\D/g, '') : val
                }" :type="type" density="compact" autofocus hide-details :error-messages="error" @blur="stopEdit"
                @keyup.enter="stopEdit" />

            <!-- TEXTAREA -->
            <v-textarea v-else-if="type === 'textarea'" v-model="localValue" density="compact" :error-messages="error"
                auto-grow @blur="stopEdit" />

            <!-- SELECT -->
            <v-select v-else-if="type === 'select'" v-model="localValue" :items="items" :error-messages="error"
                density="compact" @update:modelValue="stopEdit" />

            <!-- DATE -->
            <v-text-field v-else-if="type === 'date'" v-model="localValue" type="date" density="compact"
                :error-messages="error" @blur="stopEdit" />

        </template>

        <!-- DISPLAY MODE -->
        <template v-else>
            <span @click="startEdit" class="hover:bg-gray-100 cursor-pointer px-1 rounded">

                <!-- DATE FORMAT -->
                <template v-if="type === 'date'">
                    {{ modelValue ? formatDate(modelValue) : '-' }}
                </template>

                <!-- formatted -->
                <template v-else-if="format">
                    {{ modelValue ? format(modelValue) : '-' }}
                </template>

                <!-- select label -->
                <template v-else-if="type === 'select'">
                    {{
                        modelValue ?
                            items.find(i => i.value === modelValue)?.title ?? modelValue
                            : '-'
                    }}
                </template>

                <!-- default -->
                <template v-else>
                    {{ modelValue ?
                        props.mask
                            ? applyMask(String(modelValue))
                            : modelValue
                        : '-'
                    }}
                </template>
            </span>
            <div v-if="error" class="text-red-500 text-xs mt-1">
                {{ error }}
            </div>
        </template>
    </p>
</template>