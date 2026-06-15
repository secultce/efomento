<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SplitScreenTab from '@/Components/SplitScreenTab.vue';
import SectionChips from '@/Components/SectionChips.vue';
import SectionContent from '@/Components/SectionContent.vue';
import SectionForm from '@/Components/SectionForm.vue';
import FormField from '@/Components/FormField.vue';
import TextField from '@/Components/TextField.vue';
import AuxLinks from '@/Components/AuxLinks.vue';
import DiligenceChat from '@/Components/DiligenceChat.vue';
import { viewSections, formSections } from '@/Schemas/Monitoring';

const props = defineProps({
    project: {
        type: Object,
        default: () => ({}),
    },
});

const activeViewIndex = ref('all');

const form = useForm({
    technical_opinions: props.project.monitoring?.technical_opinions?.length
        ? props.project.monitoring.technical_opinions
        : [{ suite_number: '', processing_date: '' }],
    observations: props.project.monitoring?.observations ?? '',
});

function addOpinion() {
    form.technical_opinions.push({ suite_number: '', processing_date: '' });
}
</script>

<template>
    <split-screen-tab value="monitoramento">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <p class="font-bold text-lg">Dados disponíveis para consulta</p>
                    <p class="text-sm text-gray-600">Utilize os filtros abaixo para navegar entre os dados</p>
                </div>
                <section-chips v-model="activeViewIndex" :sections="viewSections" show-all-option />
                <div class="mt-4 space-y-8">
                    <template v-for="(section, index) in viewSections" :key="'view-' + section.title">
                        <section-content
                            v-if="activeViewIndex === 'all' || activeViewIndex === index"
                            :section="section"
                            :project="project"
                        />
                    </template>
                </div>
            </div>
        </template>

        <template #right-content>
            <div class="space-y-6">
                <div>
                    <p class="font-bold text-lg">Campos para você inserir ou editar dados</p>
                    <p class="font-bold text-md mt-2 text-black">Links auxiliares</p>
                </div>
                <aux-links />
                <diligence-chat
                    :project="project"
                    stage="monitoramento"
                    description="Envie mensagem ao agente cultural sobre o relatório de monitoramento (não vale para notificações, comunicados, solicitações etc.)"
                />
                <section-form :active-edit-index="'all'" :sections="formSections">
                    <template #default="{ section }">
                        <template v-if="section.key === 'opinion'">
                            <div
                                v-for="(opinion, i) in form.technical_opinions"
                                :key="i"
                                class="grid grid-cols-2 gap-4"
                            >
                                <form-field label="Número do parecer no SUITE *" required>
                                    <text-field v-model="opinion.suite_number" />
                                </form-field>
                                <form-field label="Data da tramitação do parecer via Suite">
                                    <text-field v-model="opinion.processing_date" type="date" />
                                </form-field>
                            </div>
                            <v-btn
                                variant="text"
                                color="primary"
                                class="mt-2 pl-0 font-bold text-xs"
                                prepend-icon="mdi-plus"
                                @click="addOpinion"
                            >
                                Registre novo parecer técnico
                            </v-btn>
                        </template>
                        <template v-if="section.key === 'observations'">
                            <text-field v-model="form.observations" :rows="4" type="textarea" />
                        </template>
                    </template>
                </section-form>
            </div>
        </template>
    </split-screen-tab>
</template>
