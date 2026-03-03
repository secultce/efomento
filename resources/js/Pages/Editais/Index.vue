<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue'

const search = ref('')

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
const headers = [
    { title: 'Título', key: 'titulo', align: 'right' },
    { title: 'Status', key: 'status', align: 'right' },
    { title: 'Tipo Instrumento', key: 'type_ins', align: 'right' },
    { title: 'Número de processo de mãe', key: 'mae', align: 'right' },
    { title: 'Acessar', key: 'rating', align: 'center' },
]

const items = [
    {
        titulo: 'EDITAL DE CHAMAMENTO PUBLICO Nº',
        status: true,
        instrumento: '1.png',
        type_ins: 699.99,
        mae: '000054554654/45457',
        acessar: 5,

    },
    {
        titulo: 'EDITAL DE CHAMAMENTO PUBLICO Nº',
        status: false,
        instrumento: '2.png',
        type_ins: 799.99,
        mae: '000054554654/45457',
        acessar: 4,
    },
    {
        titulo: 'EDITAL DE CHAMAMENTO PUBLICO Nº',
        status: true,
        instrumento: '3.png',
        type_ins: 649.99,
        mae: '000054554654/45457',
        acessar: 3,
    },
    {
        titulo: 'EDITAL DE CHAMAMENTO PUBLICO Nº',
        status: true,
        instrumento: '4.png',
        type_ins: 1499.99,
        mae: '000054554654/45457',
        acessar: 4,
    },
    {
        titulo: 'EDITAL DE CHAMAMENTO PUBLICO Nº',
        status: false,
        instrumento: '5.png',
        type_ins: 299.99,
        mae: '000054554654/45457',
        acessar: 4,
    },
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

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <v-card flat>
                <v-card-title class="d-flex align-center pe-2">
                    <v-row>
                        <v-col cols="12">
                            <p
                                class="text-xl-subtitle-2 font-weight-bold"
                            >
                                Editais disponiveis para acompanhamento abaixo
                            </p>
                            <p
                                class="text-lg-caption font-weight-light"
                            >
                                Total de editais encontrados: <span class="font-weight-bold">134</span>
                            </p>
                        </v-col>
                        <v-col cols="12">
                            <v-row>
                                <v-col cols="4">
                                    <v-text-field
                                        v-model="search"
                                        density="compact"
                                        label="Pesquise editais especifico"
                                        append-inner-icon="mdi-magnify"
                                        variant="solo-filled"
                                        flat
                                        hide-details
                                        single-line
                                        rounded="xl"
                                        class="border border-green-700 rounded-xl"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="4">
                                    <v-select
                                        density="compact"
                                        label="Filtre por status do processo"
                                        variant="solo-filled"
                                        flat

                                        :items="['California', 'Colorado', 'Florida', 'Georgia', 'Texas', 'Wyoming']"
                                    ></v-select>
                                </v-col>
                                <v-col cols="4">
                                    <v-select
                                        density="compact"
                                        label="Filtre pelo tipo de instrumento"
                                        variant="solo-filled"
                                        flat
                                        :items="['California', 'Colorado', 'Florida', 'Georgia', 'Texas', 'Wyoming']"
                                    ></v-select>
                                </v-col>
                            </v-row>

                        </v-col>
                    </v-row>



                    <v-spacer></v-spacer>


                </v-card-title>

                <v-divider></v-divider>
                <v-data-table
                    v-model:search="search"
                    :filter-keys="['titulo']"
                    :headers="headers"
                    :items="items"
                >


                    <template v-slot:item.instrumento="{ item }">
                        <v-card class="my-2" elevation="1" rounded>
                            <p>${item.instrumento}</p>
                        </v-card>
                    </template>

                    <template v-slot:item.rating="{ item }">
                        <div class="">
                            <v-btn variant="text" color="white" size="small" class="text-primary"
                                   prepend-icon="mdi-eye-circle">

                            </v-btn>

                        </div>
                    </template>

                    <template v-slot:item.status="{ item }">
                        <div class="">
                            <v-chip
                                :color="item.status ? 'green' : 'red'"
                                :text="item.status ? 'Em andamento' : 'Bloqueado'"
                                class="text-uppercase"
                                size="small"
                                label
                            ></v-chip>
                        </div>
                    </template>
                </v-data-table>
            </v-card>
        </div>
    </AuthenticatedLayout>
</template>
