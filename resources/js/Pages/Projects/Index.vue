<script setup>
import AppContainer from '@/Components/AppContainer.vue'
import AppSubHeader from '@/Components/AppSubHeader.vue'
import ListDataTable from '@/Components/ListDataTable.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  notice: Object,
  projects: Array,
  filters: Object,
  phases: Array,
})

const selectedPhase = ref(props.filters?.phase ?? null)
const search = ref(props.filters?.search ?? '')

let timeout = null

function onSearch(value) {
  search.value = value
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    router.get(route('notices.projects', props.notice.id), {
      phase: selectedPhase.value,
      search: value,
    }, {
      preserveState: true,
      replace: true,
    })
  }, 400)
}

function selectPhase(phase) {
  selectedPhase.value = phase.value

  router.get(route('notices.projects', props.notice.id), {
    phase: selectedPhase.value,
    search: search.value,
  }, {
    preserveState: true,
    replace: true,
  })
}

function clearPhaseFilter() {
  selectedPhase.value = null
  
  router.get(route('notices.projects', props.notice.id), 
  {}, 
  {
    preserveState: true,
    replace: true,
  })
}

const reference = (item) => ({
  label: 'Nome do Proponente',
  value: item.agent?.name ?? '-',
})

const chips = [
  {
    label: (item) => item.phase ?? 'xd',
    color: (item) => {
      if (item.phase === 'aprovado') return 'green'
      if (item.phase === 'reprovado') return 'red'
      return 'grey'
    },
  },
  {
    label: 'tc',
    color: 'blue',
  },
]

const data = [
  {
    label: 'Número do processo',
    value: (item) => item.opening_nup ?? '-',
  },
  {
    label: 'Fase',
    value: (item) => item.phase ?? '-',
  },
]

const actions = [
  { name: 'open', label: 'Abrir' },
]

const tableConfig = {
  reference,
  chips,
  data,
  actions,
}


const selectedProjects = ref([])
console.log(selectedProjects)

</script>

<template>

  <Head :title="`Projetos - ${notice?.titulo}`" />
  <AuthenticatedLayout>
    <AppSubHeader />
    <app-container>
        <div class="grid grid-cols-4 grid-rows-1 gap-10">
          <div class="col-span-4 col-start-1 text-[#1a1a1aFF]">
            <p class="text-sm">Utilize os quadros abaixo para filtrar e exibir a quantidade de agentes culturais em cada fase de
              processo</p>
            <v-row no-wrap class="overflow-x-auto mt-1">
              <v-col
                v-for="phase in phases"
                :key="phase.title"
                cols="auto"
              >
                <v-card
                  variant="outlined"
                  class="mx-auto !p-2 cursor-pointer transition-all"
                  rounded="lg"
                  :class="selectedPhase === phase.value
                    ? '!bg-[#008344FF] !border-[#008344]'
                    : '!border-[#ccccccFF]'"
                  @click="selectPhase(phase)"
                > 
                  <v-card-title
                    class="font-weight-bold !text-xs"
                    :class="selectedPhase === phase.value
                      ? '!text-white'
                      : '!text-[#004c27FF]'"
                  >
                    {{ phase.title }}
                  </v-card-title>

                  <v-card-subtitle
                    class="!text-xs"
                    :class="selectedPhase === phase.value
                      ? '!text-white'
                      : '!text-[#1a1a1aFF]'"
                  >
                    Total de processos nessa fase: {{ phase.total ?? 0 }}
                  </v-card-subtitle>
                </v-card>
              </v-col>
            </v-row>
          </div>
          <div class="col-span-3 row-span-2 col-start-1 row-start-2 flex flex-col w-full h-full">
            <h3 class="!text-[#1a1a1aFF] mb-4">Lista de agentes culturais em processo</h3>
            <div class="d-flex w-full items-center gap-4">
             <v-text-field
                :model-value="search"
                @update:model-value="onSearch"
                placeholder="Busque pelo agente ou nº do processo"
                append-inner-icon="mdi-magnify"
                variant="outlined"
                density="compact"
                hide-details
                rounded="xl"
                class="mb-2"
              />
              <v-btn
                variant="outlined"
                class="!text-[#008344] !font-bold !border-[#008344] flex-[1_1_0%] !h-[3em] mb-2 !px-2 !py-1"
                density="compact"
                rounded="lg"
                @click="clearPhaseFilter"
              >
                Exibir todos os proponentes (40)
              </v-btn>
              <div class="flex-[2_1_0%]"></div>
            </div>
            <list-data-table :items="projects" v-bind="tableConfig" v-model="selectedProjects" selectable />

          </div>
          <div class="row-span-2 col-start-4 row-start-2 bg-green">3</div>
        </div>
      </app-container>
  </AuthenticatedLayout>
</template>