<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const tab = ref('um');
const page = usePage();
const hasRole = (role) => page.props.auth.roles.includes(role);
const can = (permission) => page.props.auth.permissions.includes(permission);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>


        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg  border border-gray-200""
                >
                    <div class="p-6 text-gray-900">
                            <v-tabs v-model="tab">
                                <v-tab value="open">Abertura</v-tab>
                                <v-tab value="ana_jur">Análise Jurídica</v-tab>
                                <v-tab value="for_pro">Formalização de processos</v-tab>
                                <v-tab value="or_per">Orçamento e Parcela</v-tab>
                                <v-tab value="pay">PAGAMENTO</v-tab>
                            </v-tabs>

                            <v-tabs-window v-model="tab">
                                <v-tabs-window-item value="open">
                                    <div class="p-5">
                                        <div class="p-5" v-if="hasRole('fomento', 'coord_fomento')">
                                            Conteúdo da aba ABERTURA
                                        </div>
                                        <div v-else>
                                            <p class="font-weight-bold text-md-body-1 p-5 text-red-accent-1">
                                                Você não tem permissão para ver ABERTURA
                                            </p>
                                        </div>
                                    </div>
                                </v-tabs-window-item>
                                <v-tabs-window-item value="ana_jur">
                                    <div class="p-5" v-if="hasRole('juridico', 'coord_juridico')">
                                        Conteúdo da aba ANALISE JURIDICA
                                    </div>
                                    <div v-else>
                                        <p class="font-weight-bold text-md-body-1 p-5 text-red-accent-1">
                                        Você não tem permissão para ver ANÁLISE JURÍDICA
                                        </p>
                                    </div>
                                </v-tabs-window-item>
                                <v-tabs-window-item value="for_pro">
                                    <div class="p-5">
                                        @can('acompanhamento', 'coord_juridico')
                                            Conteúdo da aba FORMALIZAÇÃO DE PROCESSOS
                                        @endcan
                                    </div>
                                </v-tabs-window-item>
                                <v-tabs-window-item value="or_per">
                                    <div class="p-5">
                                        @can('orcamentario', 'coord_orcamentario')
                                            Conteúdo da aba ORÇAMENTO E PARCELA
                                        @endcan
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
