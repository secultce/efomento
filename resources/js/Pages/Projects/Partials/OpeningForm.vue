<script setup>
import { ref, reactive } from 'vue';

const subTab = ref('todos');

const form = reactive({
    numero_processo: '',
    data_abertura: '',
    status_agente_cultural: '',
    responsavel_abertura: '',
    valor_repasse: '',
    valor_repasse_extenso: '',
    valor_repasse_primeira_parcela: '',
    parcelas_adicionais: [],
    regularidade_inadimplencia: '',
    data_certidao: '',
    banco: '',
    tipo_conta: '',
    agencia: '',
    conta: '',
    fiscal_nome: '',
    fiscal_cpf: '',
    fiscal_matricula: '',
});

const statusOptions = [
    'Habilitado',
    'Inabilitado',
    'Pendente',
    'Desclassificado',
];

const regularidadeOptions = [
    'Regular',
    'Irregular',
    'Pendente de verificação',
];

const tipoContaOptions = [
    'Conta Corrente',
    'Conta Poupança',
];

const addParcela = () => {
    form.parcelas_adicionais.push({ valor: '', descricao: '' });
};

const removeParcela = (index) => {
    form.parcelas_adicionais.splice(index, 1);
};

const emit = defineEmits(['submit']);

const onSubmit = () => {
    emit('submit', { ...form });
};
</script>

<template>
    <v-card variant="flat" class="h-100">
        <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
            Campos para você inserir ou editar dados
        </v-card-title>

        <div class="px-4 pt-2 d-flex ga-2 flex-wrap">
            <v-btn variant="text" size="small" color="primary" prepend-icon="mdi-open-in-new">
                Conferir ficha no Mapa
            </v-btn>
            <v-btn variant="text" size="small" color="primary" prepend-icon="mdi-open-in-new">
                SUITE
            </v-btn>
            <v-btn variant="text" size="small" color="primary" prepend-icon="mdi-open-in-new">
                e-PARCERIAS
            </v-btn>
        </div>

        <v-tabs v-model="subTab" density="compact" color="primary" class="mt-2 px-4">
            <v-tab value="todos">Todos</v-tab>
            <v-tab value="abertura">Dados de abertura</v-tab>
            <v-tab value="parcela">Parcela</v-tab>
            <v-tab value="certidao">Certidão e-Parcerias</v-tab>
            <v-tab value="bancarios">Dados bancários</v-tab>
            <v-tab value="fiscal">Dados do fiscal</v-tab>
        </v-tabs>

        <v-card-text>
            <v-tabs-window v-model="subTab">
                <!-- Todos -->
                <v-tabs-window-item value="todos">
                    <!-- Dados de abertura -->
                    <h3 class="text-subtitle-2 font-weight-bold mb-3 mt-2">Dados de abertura</h3>
                    <v-row dense>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.numero_processo"
                                label="Número do processo"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.data_abertura"
                                label="Data de abertura"
                                type="date"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="form.status_agente_cultural"
                                :items="statusOptions"
                                label="Status do agente cultural"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.responsavel_abertura"
                                label="Responsável por abrir o processo"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <!-- Parcela -->
                    <h3 class="text-subtitle-2 font-weight-bold mb-3">Parcela</h3>
                    <v-row dense>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.valor_repasse"
                                label="Valor de repasse (Parcela única)"
                                prefix="R$"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.valor_repasse_extenso"
                                label="Valor por extenso"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.valor_repasse_primeira_parcela"
                                label="Valor do repasse (1ª parcela)"
                                prefix="R$"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                    </v-row>

                    <div v-for="(parcela, index) in form.parcelas_adicionais" :key="index" class="mt-2">
                        <v-row dense align="center">
                            <v-col cols="5">
                                <v-text-field
                                    v-model="parcela.valor"
                                    :label="`Valor da ${index + 2}ª parcela`"
                                    prefix="R$"
                                    variant="outlined"
                                    density="compact"
                                />
                            </v-col>
                            <v-col cols="5">
                                <v-text-field
                                    v-model="parcela.descricao"
                                    label="Descrição"
                                    variant="outlined"
                                    density="compact"
                                />
                            </v-col>
                            <v-col cols="2">
                                <v-btn icon="mdi-delete" variant="text" color="error" size="small" @click="removeParcela(index)" />
                            </v-col>
                        </v-row>
                    </div>

                    <v-btn variant="outlined" color="primary" size="small" prepend-icon="mdi-plus" class="mt-1 mb-4" @click="addParcela">
                        Adicionar nova parcela
                    </v-btn>

                    <v-divider class="my-4" />

                    <!-- Certidão e-Parcerias -->
                    <h3 class="text-subtitle-2 font-weight-bold mb-3">Certidão e-Parcerias</h3>
                    <v-row dense>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="form.regularidade_inadimplencia"
                                :items="regularidadeOptions"
                                label="Informe regularidade e inadimplência"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.data_certidao"
                                label="Data da certidão"
                                type="date"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <!-- Dados bancários -->
                    <h3 class="text-subtitle-2 font-weight-bold mb-3">Dados bancários</h3>
                    <v-row dense>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.banco"
                                label="Banco"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="form.tipo_conta"
                                :items="tipoContaOptions"
                                label="Tipo de conta"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.agencia"
                                label="Agência"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.conta"
                                label="Conta"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <!-- Dados do fiscal -->
                    <h3 class="text-subtitle-2 font-weight-bold mb-3">Dados do fiscal</h3>
                    <v-row dense>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model="form.fiscal_nome"
                                label="Nome do fiscal"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model="form.fiscal_cpf"
                                label="CPF do fiscal"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model="form.fiscal_matricula"
                                label="Matrícula do fiscal"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                    </v-row>

                    <v-btn color="warning" block size="large" class="mt-6 text-none font-weight-bold" @click="onSubmit">
                        Concluir atividade
                    </v-btn>
                </v-tabs-window-item>

                <!-- Dados de abertura (individual) -->
                <v-tabs-window-item value="abertura">
                    <h3 class="text-subtitle-2 font-weight-bold mb-3 mt-2">Dados de abertura</h3>
                    <v-row dense>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.numero_processo" label="Número do processo" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.data_abertura" label="Data de abertura" type="date" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select v-model="form.status_agente_cultural" :items="statusOptions" label="Status do agente cultural" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.responsavel_abertura" label="Responsável por abrir o processo" variant="outlined" density="compact" />
                        </v-col>
                    </v-row>
                    <v-btn color="warning" block size="large" class="mt-6 text-none font-weight-bold" @click="onSubmit">
                        Concluir atividade
                    </v-btn>
                </v-tabs-window-item>

                <!-- Parcela (individual) -->
                <v-tabs-window-item value="parcela">
                    <h3 class="text-subtitle-2 font-weight-bold mb-3 mt-2">Parcela</h3>
                    <v-row dense>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.valor_repasse" label="Valor de repasse (Parcela única)" prefix="R$" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.valor_repasse_extenso" label="Valor por extenso" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.valor_repasse_primeira_parcela" label="Valor do repasse (1ª parcela)" prefix="R$" variant="outlined" density="compact" />
                        </v-col>
                    </v-row>
                    <div v-for="(parcela, index) in form.parcelas_adicionais" :key="index" class="mt-2">
                        <v-row dense align="center">
                            <v-col cols="5">
                                <v-text-field v-model="parcela.valor" :label="`Valor da ${index + 2}ª parcela`" prefix="R$" variant="outlined" density="compact" />
                            </v-col>
                            <v-col cols="5">
                                <v-text-field v-model="parcela.descricao" label="Descrição" variant="outlined" density="compact" />
                            </v-col>
                            <v-col cols="2">
                                <v-btn icon="mdi-delete" variant="text" color="error" size="small" @click="removeParcela(index)" />
                            </v-col>
                        </v-row>
                    </div>
                    <v-btn variant="outlined" color="primary" size="small" prepend-icon="mdi-plus" class="mt-1" @click="addParcela">
                        Adicionar nova parcela
                    </v-btn>
                    <v-btn color="warning" block size="large" class="mt-6 text-none font-weight-bold" @click="onSubmit">
                        Concluir atividade
                    </v-btn>
                </v-tabs-window-item>

                <!-- Certidão e-Parcerias (individual) -->
                <v-tabs-window-item value="certidao">
                    <h3 class="text-subtitle-2 font-weight-bold mb-3 mt-2">Certidão e-Parcerias</h3>
                    <v-row dense>
                        <v-col cols="12" md="6">
                            <v-select v-model="form.regularidade_inadimplencia" :items="regularidadeOptions" label="Informe regularidade e inadimplência" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.data_certidao" label="Data da certidão" type="date" variant="outlined" density="compact" />
                        </v-col>
                    </v-row>
                    <v-btn color="warning" block size="large" class="mt-6 text-none font-weight-bold" @click="onSubmit">
                        Concluir atividade
                    </v-btn>
                </v-tabs-window-item>

                <!-- Dados bancários (individual) -->
                <v-tabs-window-item value="bancarios">
                    <h3 class="text-subtitle-2 font-weight-bold mb-3 mt-2">Dados bancários</h3>
                    <v-row dense>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.banco" label="Banco" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select v-model="form.tipo_conta" :items="tipoContaOptions" label="Tipo de conta" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.agencia" label="Agência" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="form.conta" label="Conta" variant="outlined" density="compact" />
                        </v-col>
                    </v-row>
                    <v-btn color="warning" block size="large" class="mt-6 text-none font-weight-bold" @click="onSubmit">
                        Concluir atividade
                    </v-btn>
                </v-tabs-window-item>

                <!-- Dados do fiscal (individual) -->
                <v-tabs-window-item value="fiscal">
                    <h3 class="text-subtitle-2 font-weight-bold mb-3 mt-2">Dados do fiscal</h3>
                    <v-row dense>
                        <v-col cols="12" md="4">
                            <v-text-field v-model="form.fiscal_nome" label="Nome do fiscal" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field v-model="form.fiscal_cpf" label="CPF do fiscal" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field v-model="form.fiscal_matricula" label="Matrícula do fiscal" variant="outlined" density="compact" />
                        </v-col>
                    </v-row>
                    <v-btn color="warning" block size="large" class="mt-6 text-none font-weight-bold" @click="onSubmit">
                        Concluir atividade
                    </v-btn>
                </v-tabs-window-item>
            </v-tabs-window>
        </v-card-text>
    </v-card>
</template>
