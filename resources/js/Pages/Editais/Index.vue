<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    totais: {
        type: Object,
        default: () => ({ projetos: 0, pendentes: 0, concluidos: 0 }),
    },
    editais: {
        type: Array,
        default: () => [],
    },
    user: {
        type: Object,
        required: true,
    },
});
const stats = [
    {
        title: 'Editais sem processos iniciados',
        value: 32,
        color: 'text-green-darken-2'   // ou success
    },
    {
        title: 'Editais em andamento',
        value: 12,
        color: 'text-green-darken-2'
    },
    {
        title: 'Editais com formalização concluídas',
        value: 16,
        color: 'text-green-darken-2'
    },
    {
        title: 'Processos em monitoramento',
        value: 3,
        color: 'text-green-darken-2'
    }
]
</script>

<template>
    <AuthenticatedLayout>
        <template #subheader>
            <div class="d-flex gap-4 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <v-container class="py-6">
                    <v-row>
                        <p class="text-white text-h6 mb-3">Bem-vindo ao seu espaço, {{ user.name }}</p>
                    </v-row>
                    <v-row>
                        <v-col
                            v-for="item in stats"
                            :key="item.title"
                            cols="12"
                            sm="6"
                            lg="3"
                        >
                            <v-card
                                variant="outlined"
                                class="pa-4 text-center bg-white"
                                height="100%"
                            >
                                <v-row>
                                    <v-col cols="9">
                                        <div class="text-body-2 mb-1">{{ item.title }}</div>

                                    </v-col>
                                    <v-col cols="3">
                                        <div
                                            class="text-h4 font-weight-bold"
                                            :class="item.color"
                                        >
                                            {{ item.value }}
                                        </div>
                                    </v-col>

                                </v-row>
                            </v-card>
                        </v-col>
                    </v-row>

                </v-container>
            </div>
        </template>
    </AuthenticatedLayout>
</template>
