<script setup>
import { computed, ref } from 'vue';
import SupervisorDialog from '@/Pages/Projects/Partials/SupervisorDialog.vue';
import { useAuth } from '@/Composables/useAuth';
import HandleCIDialog from './HandleCIDialog.vue';

const { canPerform, hasRole } = useAuth()

const props = defineProps({
    selectedProjects: Array,
    supervisors_available: Array,
    projects: Array,
})

const supervisorDialog = ref(false)
const ciDialog = ref(false)

function openSupervisorDialog() {
    supervisorDialog.value = true
}

const hasProjectsWithSupervisor = computed(() => {
    return props.projects
        .filter(p => props.selectedProjects.includes(p.id))
        .some(p =>
            p.opening?.supervisors?.some(s => s.is_active)
        )
})

const canAssignSupervisor = computed(() => {
    return hasRole('super_admin') || canPerform('opening.assign_supervisor');
});

const canCreateCI = computed(() => {
    return hasRole('super_admin') || canPerform('ci.create');
});

const selectedProjectsList = computed(() => {
    return props.projects.filter(p => 
        props.selectedProjects.includes(p.id)
    )
})

const anyProjectsHasCI = computed(() => {
    return selectedProjectsList.value.some(p =>
        p.documents?.some(d => d.type === 'ci')
    )
})

const selectedCI = computed(() => {
    const projectWithCI = selectedProjectsList.value.find(p =>
        p.documents?.some(d => d.type === 'ci')
    )

    if (!projectWithCI) return null

    const ciDocument = projectWithCI.documents.find(d => d.type === 'ci')

    return {
        content: ciDocument.body
    }
})

function openCIDialog() {
    ciDialog.value = true;
}
</script>
<template>
    <handle-c-i-dialog v-model="ciDialog" :project-ids="selectedProjects" :edit-data="selectedCI"
        @saved="$emit('saved')" />
    <supervisor-dialog v-model="supervisorDialog" :project-ids="selectedProjects" :supervisors="supervisors_available"
        @saved="$emit('saved')" />
    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg">Ações disponíveis para você </v-card-title>
        <v-card-text class="flex flex-col gap-4">
            <div class="w-full pt-2 flex flex-col gap-1" v-permission="{
                condition: canCreateCI,
                message: 'Você não tem permissão para criar um documento de comunicação interna, contate o administrador do sistema.'
            }">
                <template v-if="anyProjectsHasCI">
                    <p>Editar comunicação interna (CI)</p>
                    <v-btn
                        class="w-full !shadow-none !font-bold !border-gray-300 !bg-white !text-[#2d353fFF] rounded-lg text-xs gap-6"
                        :disabled="!(props.selectedProjects?.length > 0) || !canCreateCI" variant="outlined"
                        @click="openCIDialog">
                        <span class="w-full text-left">
                            Editar Comunicação Interna
                        </span>

                        <template #append>
                            <v-icon size="18">
                                mdi-pencil
                            </v-icon>
                        </template>
                    </v-btn>
                </template>

                <template v-else>
                    <p>Criar comunicação interna (CI)</p>
                    <v-btn
                        class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                        :disabled="!(props.selectedProjects?.length > 0) || !canCreateCI" @click="openCIDialog">
                        Criar CI
                    </v-btn>
                </template>
            </div>
            <div class="w-full pt-2 flex flex-col gap-1" v-permission="{
                condition: canAssignSupervisor,
                message: 'Você não tem permissão para atribuir fiscais ao projeto, contate o administrador do sistema.'
            }">
                <p>Atribuir fiscal aos selecionados</p>
                <v-btn
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="!(props.selectedProjects?.length > 0) || !canAssignSupervisor"
                    @click="openSupervisorDialog">
                    Atribuir Fiscal
                </v-btn>
                <p v-if="hasProjectsWithSupervisor" class="text-orange-600 max-w-[22em] whitespace-normal text-xs">Um ou
                    mais
                    projetos selecionados já possuem um fiscal. Ao atribuir um novo fiscal, o anterior será desativado.
                </p>
            </div>
            <div class="w-full flex flex-col gap-1">
                <v-divider class="my-4"></v-divider>
                <p class="">Conferir histórico de alterações nos processos</p>
                <v-btn variant="outlined" color="outlineSecondary" class="rounded-lg w-full">Conferir Histórico</v-btn>
            </div>
        </v-card-text>
    </v-card>
</template>