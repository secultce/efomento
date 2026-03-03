<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EditaisListPage from '@/Pages/Editais/EditaisListPage.vue'

defineProps({
    totais: {
        type: Object,
        default: () => ({ projetos: 0, pendentes: 0, concluidos: 0, monitoramento: 0 }),
    },
    projetos: {
        type: Array,
        default: () => [],
    },
    user: {
        type: Object,
        required: true,
    },
})

const stats = [
    { title: 'Editais sem processos iniciados', key: 'pendentes' },
    { title: 'Editais em andamento',            key: 'projetos'  },
    { title: 'Editais com formalização concluída', key: 'concluidos' },
    { title: 'Processos em monitoramento',      key: 'monitoramento' },
]
</script>

<template>
    <AuthenticatedLayout>
        <template #subheader>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <v-container class="py-6">
                    <v-row>
                        <p class="text-white text-h6 mb-3">
                            Bem-vindo ao seu espaço, {{ user.name }}
                        </p>
                    </v-row>
                    <v-row>
                        <v-col
                            v-for="stat in stats"
                            :key="stat.key"
                            cols="12" sm="6" lg="3"
                        >
                            <v-card variant="outlined" class="pa-4 bg-white" height="100%">
                                <v-row align="center">
                                    <v-col cols="9">
                                        <div class="text-body-2">{{ stat.title }}</div>
                                    </v-col>
                                    <v-col cols="3" class="text-right">
                                        <div class="text-h4 font-weight-bold text-green-darken-2">
                                            {{ totais[stat.key] }}
                                        </div>
                                    </v-col>
                                </v-row>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-container>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
            <EditaisListPage
                :editais="projetos"
                :total-editais="totais.projetos"
            />
        </div>
    </AuthenticatedLayout>
</template>
