<script setup>
import { ref } from 'vue'
import SplitScreenTab from '@/Components/SplitScreenTab.vue'
import SectionChips from '@/Components/SectionChips.vue'
import SectionContent from '@/Components/SectionContent.vue'
import SectionForm from '@/Components/SectionForm.vue'
import AuxLinks from '@/Components/AuxLinks.vue'
import { viewSections, formSections } from '@/Schemas/Opening'
import { useForm } from '@inertiajs/vue3'
import { onMounted } from 'vue'

const props = defineProps({
  project: {
    type: Object,
    default: () => ({})
  },
})

defineEmits(['update:field'])

const form = useForm({})
const handleFieldUpdate = ({ key, value }) => {
  form[key] = value
}

onMounted(() => {
  form.defaults({
    name: props.project.name,
    cpf_cnpj: props.project.cpf_cnpj,
    category_id: props.project.category?.id,
  })

  form.reset()
})

const submit = () => {
  form.patch(route('projects.update', props.project.id), {
    preserveScroll: true,
    onSuccess: () => {
      console.log('Saved successfully')
    },
    onError: (errors) => {
      console.error(errors)
    }
  })
}
const activeViewIndex = ref('all')
const activeEditIndex = ref('all')
</script>
<template>
  <split-screen-tab value="opening">
    <template #left-content>
      <div class="space-y-6">
        <div>
          <p class="font-bold text-lg">Dados disponíveis para consulta</p>
          <p class="text-sm text-gray-600">
            Utilize os filtros abaixo para navegar entre os dados
          </p>
        </div>

        <!-- Chips para o lado esquerdo (Visualização) -->
        <section-chips 
          v-model="activeViewIndex" 
          :sections="viewSections" 
          show-all-option
        />

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
          <div class="flex items-center justify-between">
            <div>
              <p class="font-bold text-lg">Campos para você inserir ou editar dados</p>
              <p class="font-bold text-md mt-2 text-black">
                Links auxiliares
              </p>
            </div>
            <v-btn variant="outlined" color="outlineSecondary" class="rounded-lg" :loading="form.processing" @click="submit">
              Salvar Alterações
            </v-btn>
          </div>
        </div>
        <aux-links />
        <section-chips 
          v-model="activeEditIndex" 
          :sections="formSections" 
        />
        <div class="mt-4">
          <section-form 
            :active-edit-index="activeEditIndex"
            :sections="formSections" 
            :project="project"
            :form="form"
            @update:field="handleFieldUpdate"
          />
          <div class="w-full justify-center flex">
            <v-btn
              class="w-1/2 mt-4 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg  text-xs"
              Atribuir Fiscal>
                tramitar
            </v-btn>
          </div>
        </div>
      </div>
    </template>
  </split-screen-tab>
</template>
