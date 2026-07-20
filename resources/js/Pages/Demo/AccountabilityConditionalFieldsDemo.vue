<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DynamicField from '@/Components/DynamicField.vue';
import { useConditionalFields } from '@/Composables/useConditionalFields';
import { Head } from '@inertiajs/vue3';
import { reactive } from 'vue';
import fields from '../../../schemas/accountability-fields-demo.json';

const formData = reactive({});

const { visibleFields } = useConditionalFields(fields, formData);

const sections = [
    { key: 'visit', title: 'Visita presencial' },
    { key: 'analysis', title: 'Análise do Relatório de Execução do Objeto' },
    { key: 'authority', title: 'Autoridade Julgadora' },
    { key: 'conclusion', title: 'Parecer Conclusivo' },
];
</script>

<template>
    <Head title="Demo — Prestação de Contas" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-3xl mx-auto">
            <v-alert type="info" variant="tonal" class="mb-6">
                Página de demonstração — não persiste dados. Mostra a abstração de campos condicionais proposta para a
                aba "Prestação de Contas" (issue #319): cada campo é declarado em
                <code>resources/schemas/accountability-fields-demo.json</code> com uma condição
                <code>visibleWhen</code>, avaliada em tempo real pelo composable <code>useConditionalFields</code>.
            </v-alert>

            <v-card v-for="section in sections" :key="section.key" class="mb-4 pa-4" variant="outlined">
                <template v-if="visibleFields.some((field) => field.section === section.key)">
                    <h3 class="text-subtitle-1 font-weight-bold mb-3">{{ section.title }}</h3>

                    <div
                        v-for="field in visibleFields.filter((f) => f.section === section.key)"
                        :key="field.key"
                        class="mb-4"
                    >
                        <DynamicField v-model="formData[field.key]" :field="field" />
                    </div>
                </template>
            </v-card>

            <v-card class="pa-4" variant="tonal">
                <h3 class="text-subtitle-1 font-weight-bold mb-2">Estado atual do formulário</h3>
                <pre class="text-caption">{{ JSON.stringify(formData, null, 2) }}</pre>
            </v-card>
        </div>
    </AuthenticatedLayout>
</template>
