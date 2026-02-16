<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const tab = ref('um');

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>


        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">


                            <v-tabs v-model="tab">
                                <v-tab value="open">ABERTURA</v-tab>
                                <v-tab value="ana_jur">ANALISE JURIDICA</v-tab>
                                <v-tab value="for_pro">FORMALIZAÇÃO DE PROCESSOS</v-tab>
                                <v-tab value="or_per">ORÇAMENTO E PARCELA</v-tab>
                                <v-tab value="pay">PAGAMENTO</v-tab>
                            </v-tabs>

                            <v-tabs-window v-model="tab">
                                <v-tabs-window-item value="open">
                                    <div class="p-5">
                                        <p class="font-weight-bold text-md-body-1">
                                            Conteúdo da aba ABERTURA
                                        </p>
                                    </div>
                                </v-tabs-window-item>
                                <v-tabs-window-item value="ana_jur">
                                    <div class="p-5">
                                        Conteúdo da aba ANALISE JURIDICA
                                    </div>
                                </v-tabs-window-item>
                                <v-tabs-window-item value="for_pro">
                                    <div class="p-5">
                                        Conteúdo da aba FORMALIZAÇÃO DE PROCESSOS
                                    </div>
                                </v-tabs-window-item>
                                <v-tabs-window-item value="or_per">
                                    <div class="p-5">
                                        Conteúdo da aba ORÇAMENTO E PARCELA
                                    </div>
                                </v-tabs-window-item>
                                <v-tabs-window-item value="pay">
                                    <div class="p-5">
                                        Conteúdo da aba PAGAMENTO
                                    </div>
                                </v-tabs-window-item>
                            </v-tabs-window>


                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
