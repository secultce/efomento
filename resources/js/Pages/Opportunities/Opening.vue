<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EditalContextBar from '@/Pages/Opportunities/Partials/EditalContextBar.vue';
import AgentHeader from '@/Pages/Opportunities/Partials/AgentHeader.vue';
import ReadOnlyDataPanel from '@/Pages/Opportunities/Partials/ReadOnlyDataPanel.vue';
import OpeningForm from '@/Pages/Opportunities/Partials/OpeningForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';

const page = usePage();
const hasRole = (role) => page.props.auth.roles.includes(role);

const mainTab = ref('open');

// Dados mockados do edital
const edital = reactive({
    id: 1,
    titulo: 'Edital Ciclo Cearense Carnavalesco 2025 - SECULT-CE',
});

// Dados mockados do projeto/agente
const opportunity = reactive({
    id: 1,
    agent_name: 'Maria da Silva Santos',
    agent_cpf_cnpj: '123.456.789-00',
    numero_processo: 'NUP 44001.002345/2025-01',
    status: 'em_andamento',
    status_label: 'Em andamento',
    status_agente: 'Habilitado',
});

// Dados mockados para leitura (coluna esquerda)
const agentData = reactive({
    nome_social: 'Maria da Silva Santos',
    personalidade_juridica: 'Pessoa Física',
    cpf_cnpj: '123.456.789-00',
    area_linguagem: 'Artes Visuais / Pintura / Ciclo Carnavalesco',
    categoria_inscricao: 'Artista Individual',
    titulo_projeto: 'Cores do Carnaval Cearense',
    endereco: 'Rua das Flores, 123 - Centro',
    cidade: 'Fortaleza',
    uf: 'CE',
    cep: '60000-000',
    email: 'maria.silva@email.com',
    telefone: '(85) 99999-0000',
    genero: 'Feminino',
    raca_cor: 'Parda',
});

const handleSubmit = (formData) => {
    console.log('Formulário enviado:', formData);
};
</script>

<template>
    <Head title="Abertura de Processo" />

    <AuthenticatedLayout>
        <!-- Barra de contexto do edital -->

        <EditalContextBar :edital="edital" />


        <div class="py-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Cabeçalho do agente -->
                <AgentHeader :opportunity="opportunity" class="mb-4" />

                <!-- Tabs horizontais principais -->
                <v-card>
                    <v-tabs v-model="mainTab" color="primary" bg-color="white">
                        <v-tab value="open">Abertura</v-tab>
                        <v-tab value="ana_jur">Análise jurídica</v-tab>
                        <v-tab value="for_pro">Formalização de processos</v-tab>
                        <v-tab value="or_par">Orçamento e parcela</v-tab>
                        <v-tab value="pay">Pagamentos</v-tab>
                        <v-tab value="monitor">Monitoramento</v-tab>
                    </v-tabs>

                    <v-divider />

                    <v-tabs-window v-model="mainTab">
                        <!-- Aba Abertura -->
                        <v-tabs-window-item value="open">
                            <div class="pa-4">
                                <v-row>
                                    <!-- Coluna esquerda - Dados somente leitura -->
                                    <v-col cols="12" md="5">
                                        <ReadOnlyDataPanel :agent="agentData" />
                                    </v-col>

                                    <!-- Coluna direita - Formulário editável -->
                                    <v-col cols="12" md="7">
                                        <OpeningForm @submit="handleSubmit" />
                                    </v-col>
                                </v-row>
                            </div>
                        </v-tabs-window-item>

                        <!-- Demais abas (placeholder) -->
                        <v-tabs-window-item value="ana_jur">
                            <div class="pa-8 text-center text-grey-darken-1">
                                Análise jurídica — em desenvolvimento
                            </div>
                        </v-tabs-window-item>

                        <v-tabs-window-item value="for_pro">
                            <div class="pa-8 text-center text-grey-darken-1">
                                Formalização de processos — em desenvolvimento
                            </div>
                        </v-tabs-window-item>

                        <v-tabs-window-item value="or_par">
                            <div class="pa-8 text-center text-grey-darken-1">
                                Orçamento e parcela — em desenvolvimento
                            </div>
                        </v-tabs-window-item>

                        <v-tabs-window-item value="pay">
                            <div class="pa-8 text-center text-grey-darken-1">
                                Pagamentos — em desenvolvimento
                            </div>
                        </v-tabs-window-item>

                        <v-tabs-window-item value="monitor">
                            <div class="pa-8 text-center text-grey-darken-1">
                                Monitoramento — em desenvolvimento
                            </div>
                        </v-tabs-window-item>
                    </v-tabs-window>
                </v-card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
