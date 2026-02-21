<script setup>
import { ref } from 'vue';

defineProps({
    agent: {
        type: Object,
        required: true,
    },
});

const subTab = ref('dados_agente');
</script>

<template>
    <v-card variant="flat" color="grey-lighten-5" class="h-100">
        <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
            Dados disponíveis para consulta
        </v-card-title>
        <v-card-subtitle class="text-caption">
            Somente leitura
        </v-card-subtitle>

        <v-tabs v-model="subTab" density="compact" color="primary" class="mt-2">
            <v-tab value="dados_agente" size="small">Dados do agente e do projeto</v-tab>
            <v-tab value="endereco" size="small">Endereço</v-tab>
            <v-tab value="dados_adicionais" size="small">Dados adicionais</v-tab>
            <v-tab value="perfil_socio" size="small">Perfil socioeconômico</v-tab>
            <v-tab value="arquivos" size="small">Arquivos e documentos</v-tab>
        </v-tabs>

        <v-card-text>
            <v-tabs-window v-model="subTab">
                <v-tabs-window-item value="dados_agente">
                    <v-list density="compact" bg-color="transparent">
                        <v-list-item>
                            <v-list-item-subtitle>Nome social / Fantasia</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.nome_social }}</v-list-item-title>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-subtitle>Personalidade jurídica</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.personalidade_juridica }}</v-list-item-title>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-subtitle>CPF / CNPJ</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.cpf_cnpj }}</v-list-item-title>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-subtitle>Área / Linguagem / Ciclo</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.area_linguagem }}</v-list-item-title>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-subtitle>Categoria de inscrição</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.categoria_inscricao }}</v-list-item-title>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-subtitle>Título do projeto</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.titulo_projeto }}</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-tabs-window-item>

                <v-tabs-window-item value="endereco">
                    <v-list density="compact" bg-color="transparent">
                        <v-list-item>
                            <v-list-item-subtitle>Endereço</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.endereco }}</v-list-item-title>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-subtitle>Cidade / UF</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.cidade }} / {{ agent.uf }}</v-list-item-title>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-subtitle>CEP</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.cep }}</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-tabs-window-item>

                <v-tabs-window-item value="dados_adicionais">
                    <v-list density="compact" bg-color="transparent">
                        <v-list-item>
                            <v-list-item-subtitle>E-mail</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.email }}</v-list-item-title>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-subtitle>Telefone</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.telefone }}</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-tabs-window-item>

                <v-tabs-window-item value="perfil_socio">
                    <v-list density="compact" bg-color="transparent">
                        <v-list-item>
                            <v-list-item-subtitle>Gênero</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.genero }}</v-list-item-title>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-subtitle>Raça / Cor</v-list-item-subtitle>
                            <v-list-item-title>{{ agent.raca_cor }}</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-tabs-window-item>

                <v-tabs-window-item value="arquivos">
                    <div class="pa-4 text-body-2 text-grey-darken-1">
                        Nenhum arquivo disponível.
                    </div>
                </v-tabs-window-item>
            </v-tabs-window>
        </v-card-text>
    </v-card>
</template>
