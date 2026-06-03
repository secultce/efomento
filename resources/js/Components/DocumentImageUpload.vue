<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    images: {
        type: Array,
        default: () => [],
    },
    type: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['change', 'remove']);
</script>

<template>
    <p class="font-semibold mb-1">
        {{ title }}
    </p>

    <div class="border rounded-xl p-6 mb-6">
        <div class="grid gap-6" :class="images.length === 1 ? 'grid-cols-1' : 'grid-cols-3'">
            <div v-for="(_, index) in images" :key="index" class="w-full">
                <div class="relative group w-full">
                    <label class="cursor-pointer block w-full">
                        <input
                            hidden
                            type="file"
                            accept="image/*"
                            @click="$event.target.value = ''"
                            @change="emit('change', $event, type, index)"
                        />

                        <div
                            class="w-full h-[100px] border-2 border-dashed border-emerald-300 rounded-lg overflow-hidden flex items-center justify-center"
                        >
                            <img
                                v-if="images[index] && !images[index].removed"
                                :src="images[index].url"
                                class="w-full h-full object-cover"
                            />

                            <span v-else class="text-3xl text-emerald-600"> + </span>
                        </div>
                    </label>

                    <button
                        v-if="images[index] && !images[index].removed"
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center shadow"
                        @click.prevent="emit('remove', type, index)"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
