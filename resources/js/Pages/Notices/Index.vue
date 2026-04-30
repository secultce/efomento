<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import NoticesListPage from '@/Pages/Notices/NoticesListPage.vue'

defineProps({
    totais: {
        type: Object,
        default: () => ({ oportunidades: 0, pendentes: 0, concluidos: 0, monitoramento: 0 }),
    },
    notices: {
        type: Array,
        default: () => [],
    },
    user: {
        type: Object,
        required: true,
    },
    instrumentTypes: {
        type: Array,
        default: () => [],
    }
})

const stats = [
    { title: 'Todos os editais disponíveis', key: 'pendentes' },
    { title: 'Editais com processos em andamento',            key: 'oportunidades'  },
    { title: 'Processos Finalizados', key: 'concluidos' }
]
</script>

<template>
    <AuthenticatedLayout>
        <template #subheader>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <v-container class="py-6">
                    <v-row>
                        <p class="text-white text-h6 mb-3" data-cy="mensagemDeBoasVindas">
                            Bem-vindo ao seu espaço, {{ user.name }}
                        </p>
                    </v-row>
                    <v-row>
                        <v-col
                            v-for="stat in stats"
                            :key="stat.key"
                            cols="12" sm="6" lg="4"
                        >
                            <v-card variant="outlined" class="pa-5 bg-white" height="100%">
                                <v-row align="center">
                                    <v-col cols="6">
                                        <div class="text-body-2 font-weight-bold" data-cy="cardDashboard">{{ stat.title }}</div>
                                    </v-col>
                                    <v-col cols="6" class="text-right">
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
            <NoticesListPage
                :notices="notices"
                :total-notices="totais.notices"
                :instrument-types="instrumentTypes"
            />
        </div>
    </AuthenticatedLayout>
</template>
