<script setup>
import ListDataTable from '@/Components/ListDataTable.vue'

defineProps({
    projects: Array,
    tableConfig: Object,
    search: String,
})

const emit = defineEmits([
    'update:search',
    'clearPhaseFilter',
    'update:selectedProjects',
    'action',
])

const selected = defineModel()

function onSearch(value) {
    emit('update:search', value)
}

function clear() {
    emit('clearPhaseFilter')
}
</script>

<template>
    <div class="flex flex-col w-full h-full">
        <h3 class="!text-[#1a1a1aFF] mb-4">
            Lista de agentes culturais em processo
        </h3>

        <div class="d-flex w-full items-center gap-4">
            <v-text-field :model-value="search" @update:model-value="onSearch"
                placeholder="Busque pelo agente ou nº do processo" append-inner-icon="mdi-magnify" variant="outlined"
                density="compact" hide-details rounded="xl" class="mb-2" />

            <v-btn variant="outlined"
                class="!text-[#008344] !font-bold !border-[#008344] flex-[1_1_0%] !h-[3em] mb-2 !px-2 !py-1"
                density="compact" rounded="lg" @click="clear">
                Exibir todos os proponentes
            </v-btn>

            <div class="flex-[2_1_0%]"></div>
        </div>

        <ListDataTable :items="projects" v-bind="tableConfig" v-model="selected"  @action="(payload) => emit('action', payload)" selectable />
    </div>
</template>