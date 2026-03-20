<script setup>
import AppContainer from '@/Components/AppContainer.vue'
import AppSubHeader from '@/Components/AppSubHeader.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ProjectList from '@/Pages/Projects/Partials/ProjectList.vue'
import PhaseFilter from '@/Pages/Projects/Partials/PhaseFilter.vue'
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

  <Head :title="`Projetos`" />
  <AuthenticatedLayout>
    <AppSubHeader />
    <app-container>
        <div class="grid grid-cols-4 grid-rows-1 gap-10">
          <div class="col-span-4 col-start-1 text-[#1a1a1aFF]">
            <phase-filter
              :phases="phases"
              :selected-phase="selectedPhase"
              @select="selectPhase"
            />
          </div>
          <div class="col-span-3 row-span-2 col-start-1 row-start-2 flex flex-col w-full h-full">
            <project-list
              :projects="projects"
              :table-config="tableConfig"
              v-model="selectedProjects"
              :search="search"
              @update:search="onSearch"
              @clearPhaseFilter="clearPhaseFilter"
            />
          </div>
          <div class="row-span-2 col-start-4 row-start-2 bg-green">3</div>
        </div>
      </app-container>
  </AuthenticatedLayout>
</template>