<script setup>
import { ref } from 'vue'
import SplitScreenTab from '@/Components/SplitScreenTab.vue'
import SectionChips from '@/Components/SectionChips.vue'
import SectionContent from '@/Components/SectionContent.vue'
import SectionForm from '@/Components/SectionForm.vue'
import AuxLinks from '@/Components/AuxLinks.vue'

const props = defineProps({
  project: {
    type: Object,
    default: () => ({})
  },
  viewSections: {
    type: Object,
    default: () => ({})
  },
  formSections: {
    type: Array,
    default: () => []
  }
})

const sections = [
  {
    title: 'Dados do agente e do projeto',
    fields: [
      { label: 'Nome social / Nome fantasia', key: 'name' },
      { label: 'Personalidade jurídica', key: 'legal_type' },
      { label: 'CPF / CNPJ do agente cultural', key: 'cpf_cnpj' },
      { label: 'Área / linguagem / ciclo', key: 'area' },
      { label: 'Categoria de inscrição', key: 'category' },
      { label: 'Título do projeto', key: 'project_title' },
    ],
  },
  {
    title: 'Dados de identificação',
    fields: [
      { label: 'N° da inscrição', key: 'registration_id' },
      { label: 'Título do projeto', key: 'project_title' },
      { label: 'Nome social / Nome fantasia', key: 'name' },
      { label: 'CPF / CNPJ', key: 'cpf_cnpj' },
      { label: 'Categoria de inscrição', key: 'category' },
    ],
  },
  {
    title: 'Endereço',
    fields: [
      { label: 'Endereço completo', key: 'address' },
      { label: 'Macrorregião', key: 'region' },
      { label: 'CEP', key: 'cep' },
      { label: 'Município', key: 'city' },
    ],
  },
  {
    title: 'Campos adicionais',
    fields: [
      { label: 'Nome completo do dirigente', key: 'manager_name' },
      { label: 'Cargo do dirigente', key: 'manager_role' },
      { label: 'Telefone do dirigente', key: 'manager_phone' },
      { label: 'CPF do dirigente', key: 'manager_cpf' },
      { label: 'E-mail do dirigente', key: 'manager_email' },
      { label: 'E-mail do proponente', key: 'email' },
      { label: 'E-mail secundário', key: 'secondary_email' },
      { label: 'Telefone secundário', key: 'secondary_phone' },
    ],
  },
  {
    title: 'Perfil socioeconômico',
    fields: [
      { label: 'Data de nascimento', key: 'birthdate' },
      { label: 'Escolaridade', key: 'education' },
      { label: 'Raça / cor', key: 'race' },
      { label: 'Orientação sexual', key: 'sexual_orientation' },
      { label: 'Possui deficiência?', key: 'has_disability' },
      { label: 'Gênero', key: 'gender' },
    ],
  },
  {
    title: 'Arquivos e documentos',
    fields: [
      { label: 'Comprovante bancário', key: 'bank_file' },
      { label: 'Parecer individual', key: 'review_file' },
      { label: 'Ficha de inscrição', key: 'registration_file' },
      { label: 'Autodeclaração', key: 'self_declaration' },
      { label: 'Plano de ação', key: 'action_plan' },
      { label: 'Documento de identificação', key: 'document' },
      { label: 'Publicação da comissão', key: 'committee_publication' },
      { label: 'Resultado após recurso', key: 'post_appeal_result' },
      { label: 'Recurso', key: 'appeal' },
      { label: 'Resultado final', key: 'final_result' },
      { label: 'Publicação no DOE', key: 'official_gazette' },
    ],
  },
]
defineEmits(['update:field'])

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
          :sections="sections" 
          show-all-option
        />

        <div class="mt-4 space-y-8">
          <template v-for="(section, index) in sections" :key="'view-' + section.title">
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
            <v-btn variant="outlined" color="outlineSecondary" class="rounded-lg">
              Salvar Alterações
            </v-btn>
          </div>
        </div>
        <aux-links />
        <section-chips 
          v-model="activeEditIndex" 
          :sections="sections" 
        />

        <div class="mt-4">
          <section-form 
            :active-edit-index="activeEditIndex"
            :sections="sections" 
            :project="project" 
            @update:field="$emit('update:field', $event)"
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
