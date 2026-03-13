<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    edital: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div>
        <v-sheet color="#485465FF" class="pa-3">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="d-flex align-center ga-3">
                    <Link :href="route('editais.index')">
                        <v-btn variant="outlined" color="white" size="small"
                               prepend-icon="mdi-arrow-back">
                            Voltar
                        </v-btn>
                    </Link>
                    <span class="text-white text-h6 font-weight-bold">
                        {{ edital.titulo }}
                    </span>
                </div>
            </div>

        </v-sheet>
    </div>

</template>
