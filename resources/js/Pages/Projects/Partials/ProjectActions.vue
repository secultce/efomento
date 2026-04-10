<script setup>
import { computed, ref } from 'vue';
import SupervisorDialog from './supervisorDialog.vue';

const props = defineProps({
    selectedProjects: Array,
    supervisors_available: Array,
    projects: Array,
})

const supervisorDialog = ref(false)

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

</script>
<template>
    <supervisor-dialog v-model="supervisorDialog" :project-ids="selectedProjects"
        :supervisors="supervisors_available" @saved="$emit('saved')" />
    <v-card class="w-full pb-4 pt-4 !shadow-none border border-gray-800 rounded-lg">
        <v-card-title class="font-weight-bold !text-lg">Ações disponíveis para você </v-card-title>
        <v-card-text class="flex flex-col gap-4">
            <div class="w-full pt-2 flex flex-col gap-1">
                <p>Criar comunicação interna (CI)</p>
                <v-btn
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs ">Criar
                    CI</v-btn>
            </div>
            <div class="w-full pt-2 flex flex-col gap-1">
                <p>Atribuir fiscal aos selecionados</p>
                <v-btn
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg px-4 py-2 text-xs"
                    :disabled="selectedProjects.length === 0" @click="openSupervisorDialog">Atribuir Fiscal</v-btn>
                <p v-if="hasProjectsWithSupervisor" class="text-orange-600 max-w-[22em] whitespace-normal text-xs">Um ou mais projetos selecionados já possuem um fiscal. Ao atribuir um novo fiscal, o anterior será desativado.</p>
            </div>
            <div class="w-full flex flex-col gap-1">
                <v-divider class="my-4"></v-divider>
                <p class="">Conferir histórico de alterações nos processos</p>
                <v-btn variant="outlined" color="#004c27" class="rounded-lg w-full">Conferir Histórico</v-btn>
            </div>
        </v-card-text>
    </v-card>
</template>