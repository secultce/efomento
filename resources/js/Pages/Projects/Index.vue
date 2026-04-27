<script setup>
import AppContainer from '@/Components/AppContainer.vue'
import AppSubHeader from '@/Components/AppSubHeader.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ProjectList from '@/Pages/Projects/Partials/ProjectList.vue'
import PhaseFilter from '@/Pages/Projects/Partials/PhaseFilter.vue'
import ProjectNoticeEdit from '@/Pages/Projects/Partials/ProjectNoticeEdit.vue'
import ProjectActions from '@/Pages/Projects/Partials/ProjectActions.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  notice: Object,
  projects: Array,
  filters: Object,
  phases: Array,
  instrumentTypes: Array,
  supervisors_available: Array,
})

const search = ref(props.filters?.search ?? '')
const selectedPhase = ref(props.filters?.phase ?? null)

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

function onSearch(value) {
  search.value = value
  router.get(route('notices.projects', props.notice.id), {
    phase: selectedPhase.value,
    search: value,
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

function getChipColor(status) {
  const map = {
    draft: 'gray',
    pending_signature: 'yellow',
    signed: 'green',
  }

  return map[status] ?? 'default'
}

const chips = (item) => {
  if (!Array.isArray(item.documents)) return []


  const uniqueDocs = Object.values(
    item.documents.reduce((acc, doc) => {
      acc[doc.type] = doc
      return acc
    }, {})
  )

  return uniqueDocs.map(doc => ({
    label: doc.type_label,
    color: getChipColor(doc.status),
  }))
}

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
  isSelectable: (item) => item.openings_count > 0,
}

const selectedProjects = ref([])

function handleSaved() {
  selectedProjects.value = []
}

function handleAction({ action, item }) {
  if (action === 'open') {
    router.get(route('notices.projects.show', {
      notice: props.notice.id,
      project: item.id,
    }))
  }
}

</script>

<template>

  <Head :title="`Projetos`" />
  <AuthenticatedLayout>
    <AppSubHeader variant="large" :show-back="true" back-route="/editais">
      <ProjectNoticeEdit :notice="notice" :instrumentTypes="instrumentTypes" />
    </AppSubHeader>
    <AppContainer>
      <div class="grid grid-cols-4 grid-rows-1 gap-10">
        <div class="col-span-4 col-start-1 text-[#1a1a1aFF]">
          <PhaseFilter :phases="phases" :selected-phase="selectedPhase" @select="selectPhase" />
        </div>
        <div class="col-span-3 row-span-2 col-start-1 row-start-2 flex flex-col w-full h-full">
          <ProjectList :projects="projects" :table-config="tableConfig" v-model="selectedProjects" :search="search"
            @update:search="onSearch" @clearPhaseFilter="clearPhaseFilter" @action="handleAction"/>
        </div>
        <div class="row-span-2 col-start-4 row-start-2">
          <ProjectActions :selected-projects="selectedProjects" :projects="projects"
            :supervisors_available="supervisors_available" :notice="notice" @saved="handleSaved" />
        </div>
      </div>
    </AppContainer>
  </AuthenticatedLayout>
</template>
