<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, Object], default: null },
    items: {
        type: Array,
        default: () => [],
    },
    required: Boolean,
    clearable: Boolean,
    showAvatar: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'changeSupervisor']);

// Valida se o ID atual realmente existe na lista de itens disponíveis
const safeValue = computed(() => {
    if (!props.modelValue || !props.items.length) return null;

    const exists = props.items.some((item) => Number(item.id) === Number(props.modelValue));
    return exists ? props.modelValue : null;
});

const rules = computed(() => {
    if (!props.required) return [];
    return [(v) => !!v || 'Campo obrigatório'];
});
</script>

<template>
    <v-autocomplete
        v-bind="$attrs"
        :items="items"
        :model-value="safeValue"
        :rules="rules"
        :clearable="clearable"
        item-title="name"
        item-value="id"
        menu-icon="null"
        class="no-arrow mt-2"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <template #append-inner>
            <div class="w-100 d-flex justify-end">
                <v-btn
                    size="small"
                    color="#ffcc05FF"
                    class="rounded-xl !font-bold"
                    @click.stop="emit('changeSupervisor')"
                >
                    Alterar fiscal
                </v-btn>
            </div>
        </template>

        <template #item="{ props: itemProps, item }">
            <v-list-item v-bind="itemProps">
                <template v-if="showAvatar" #prepend>
                    <v-avatar size="28">
                        <img v-if="item.raw?.avatar" class="w-100 h-100 object-cover" :src="item.raw?.avatar" />
                        <span v-else>
                            {{ item.title?.charAt(0) }}
                        </span>
                    </v-avatar>
                </template>
            </v-list-item>
        </template>

        <template #selection="{ item }">
            <div class="d-flex align-center">
                <v-avatar v-if="showAvatar" size="24" class="mr-2">
                    <img v-if="item.raw?.avatar" :src="item.raw?.avatar" />
                    <span v-else>
                        {{ item.title?.charAt(0) }}
                    </span>
                </v-avatar>

                <span>{{ item.title }}</span>
            </div>
        </template>
    </v-autocomplete>
</template>
