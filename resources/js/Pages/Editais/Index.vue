<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from "@inertiajs/vue3";
import { ref } from 'vue'

const search = ref('')
const items = [
    {
        name: 'Nebula GTX 3080',
        image: '1.png',
        price: 699.99,
        rating: 5,
        stock: true,
    },
    {
        name: 'Galaxy RTX 3080',
        image: '2.png',
        price: 799.99,
        rating: 4,
        stock: false,
    },
    {
        name: 'Orion RX 6800 XT',
        image: '3.png',
        price: 649.99,
        rating: 3,
        stock: true,
    },
    {
        name: 'Vortex RTX 3090',
        image: '4.png',
        price: 1499.99,
        rating: 4,
        stock: true,
    },
    {
        name: 'Cosmos GTX 1660 Super',
        image: '5.png',
        price: 299.99,
        rating: 4,
        stock: false,
    },
]

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
    <Head title="Editais E-Fomento" />
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
                            <p class="font-weight-bold">Editais disponíveis para acompanhamento abaixo</p>
                            <p class="font-thin text-xs mt-2">Editais disponíveis para acompanhamento abaixo</p>
                        </v-col>
                        <v-col cols="12">
                            <v-row>
                                <v-col cols="4">
                                    <v-text-field
                                        v-model="search"
                                        density="compact"
                                        label="Search"
                                        prepend-inner-icon="mdi-magnify"
                                        variant="solo-filled"
                                        flat
                                        hide-details
                                        single-line
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="4">
                                    <v-select
                                        label="Filtre por status do processo"
                                        :items="['In stock', 'Out of stock']"
                                        variant="solo-inverted"
                                        density="compact"
                                        hide-details
                                        flat
                                        v-model="search"
                                    ></v-select>
                                </v-col>
                                <v-col cols="4">
                                    <v-select
                                        label="Filtre pelo tipo de instrumento"
                                        :items="['California', 'Colorado', 'Florida', 'Georgia', 'Texas', 'Wyoming']"
                                        variant="solo-inverted"
                                        density="compact"
                                        hide-details
                                        flat
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
                    :filter-keys="['name']"
                    :items="items"
                >
                    <template v-slot:header.stock>
                        <div class="text-end">Stock</div>
                    </template>

                    <template v-slot:item.image="{ item }">
                        <v-card class="my-2" elevation="1" rounded>
                            <v-img
                                :src="`https://cdn.vuetifyjs.com/docs/images/graphics/gpus/${item.image}`"
                                height="64"
                                cover
                            ></v-img>
                        </v-card>
                    </template>

                    <template v-slot:item.rating="{ item }">
                        <v-rating
                            :model-value="item.rating"
                            color="orange-darken-2"
                            density="compact"
                            size="small"
                            readonly
                        ></v-rating>
                    </template>

                    <template v-slot:item.stock="{ item }">
                        <div class="text-end">
                            <v-chip
                                :color="item.stock ? 'green' : 'red'"
                                :text="item.stock ? 'In stock' : 'Out of stock'"
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
