<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: null,
    items: Array,
    required: Boolean,
    clearable: Boolean,
    showAvatar: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['update:modelValue'])

const rules = computed(() => {
    if (!props.required) return []
    return [v => !!v || 'Campo obrigatório']
})
</script>

<template>
<v-autocomplete
    v-bind="$attrs"
    :items="items"
    :model-value="modelValue"
    :rules="rules"
    :clearable="clearable"
    item-title="name"
    item-value="id"
    menu-icon="null"
    class="no-arrow"
    @update:model-value="emit('update:modelValue', $event)"
>
    <!-- RIGHT SIDE BUTTON -->
    <template #append-inner>
        <div class="w-100 d-flex justify-end">
        <v-btn
            size="small"
            color="#ffcc05FF"
            class="rounded-xl !font-bold"
            @click.stop="$emit('changeSupervisor')"
        >
            Alterar fiscal
        </v-btn>
        </div>
    </template>
    <!-- Dropdown -->
    <template #item="{ props, item }">
        <v-list-item v-bind="props">
            <template v-if="showAvatar" #prepend>
                <v-avatar size="28">
                    <img v-if="item.raw.avatar" class="w-100 h-100 object-cover" :src="item.raw.avatar" />
                    <span v-else>
                        {{ item.raw.name.charAt(0) }}
                    </span>
                </v-avatar>
            </template>
        </v-list-item>
    </template>

    <!-- Selected -->
    <template #selection="{ item }">
        <div class="d-flex align-center">
            <v-avatar v-if="showAvatar" size="24" class="mr-2">
                <img v-if="item.raw.avatar" :src="item.raw.avatar" />
                <span v-else>
                    {{ item.raw.name.charAt(0) }}
                </span>
            </v-avatar>

            <span>{{ item.raw.name }}</span>
        </div>
    </template>
</v-autocomplete>
</template>