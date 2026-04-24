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
    project: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['update:field'])

const updateValue = (key, value) => {
    emit('update:field', { key, value })
}

const getFieldValue = (key) => {
    return props.project?.[key] ?? props.project?.agent?.[key] ?? ''
}
</script>

<template>
        <template v-for="(section, index) in sections" :key="'form-' + section.title">
            <div>
                <div v-if="activeEditIndex === index || activeEditIndex === 'all'" class="space-y-4">
                    <p class="font-bold mt-4 uppercase text-xs tracking-wider">
                        {{ section.title }}
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="field in section.fields" :key="'field-' + field.key" class="flex flex-col space-y-1">
                            <label :for="field.key" class="text-xs ">
                                {{ field.label }}
                            </label>

                            <v-text-field :id="field.key" :model-value="getFieldValue(field.key)"
                                @update:model-value="updateValue(field.key, $event)" variant="outlined" density="compact"
                                hide-details="auto" class="bg-white" placeholder="Digite aqui..." />
                        </div>
                    </div>
                </div>
            </div>
        </template>
</template>
