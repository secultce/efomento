<script setup>
import { computed } from 'vue'
import ListDataTable from '@/Components/ListDataTable.vue'

const props = defineProps({
    projects: Array,
    tableConfig: Object,
    search: String,
})

const emit = defineEmits([
    'update:search',
    'clearPhaseFilter',
])

const selected = defineModel()

const isSelectable = computed(() => props.tableConfig?.isSelectable ?? (() => true))

const selectableProjects = computed(() =>
    props.projects?.filter(p => isSelectable.value(p)) ?? []
)

const allSelected = computed(() =>
    selectableProjects.value.length > 0 &&
    selectableProjects.value.every(p => selected.value?.includes(p.id))
)

function toggleAll() {
    if (allSelected.value) {
        selected.value = []
    } else {
        selected.value = selectableProjects.value.map(p => p.id)
    }
}

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

            <v-btn variant="outlined"
                class="!font-bold !border-[#cccccc] !text-[#3b3b3c] flex-[1_1_0%] !h-[3em] mb-2 !px-2 !py-1"
                density="compact" rounded="lg" @click="toggleAll">
                <v-checkbox-btn :model-value="allSelected" density="compact" hide-details class="mr-1 pointer-events-none" />
                Marcar todos
            </v-btn>
        </div>

        <ListDataTable :items="projects" v-bind="tableConfig" v-model="selected" selectable />
    </div>
</template>