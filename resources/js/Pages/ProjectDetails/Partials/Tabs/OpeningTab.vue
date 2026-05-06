<script setup>
import { ref, watch } from 'vue'
import SplitScreenTab from '@/Components/SplitScreenTab.vue'
import SectionChips from '@/Components/SectionChips.vue'
import SectionContent from '@/Components/SectionContent.vue'
import SectionForm from '@/Components/SectionForm.vue'
import AuxLinks from '@/Components/AuxLinks.vue'
import { viewSections, formSections } from '@/Schemas/Opening'
import { useForm } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import { usePayload } from '@/Composables/usePayload'
import { useSnackbar } from '@/Composables/useSnackbar'
import TextField from '@/Components/TextField.vue'
import UserAutocompleteField from '@/Components/UserAutocompleteField.vue'
import FormField from '@/Components/FormField.vue'
import SelectField from '@/Components/SelectField.vue'

const { showSnackbar } = useSnackbar()

const props = defineProps({
  project: {
    type: Object,
    default: () => ({})
  },
  availableSupervisors: {
    type: Array,
    default: () => []
  },
  agentStatus: {
    type: Array,
    default: () => []
  },
  reportStatus: {
    type: Array,
    default: () => []
  },  
  accountType: {
    type: Array,
    default: () => []
  }
})

console.log(props.agentStatus)
defineEmits(['update:field'])

const form = useForm({
  opening: {
    opening_nup: null,
    opening_date: null,
    agent_status: null,
    opened_by: null,
    bank: null,
    account_type: null,
    branch: null,
    account: null,
    supervisors: [
      { id: null, nup: '' },
      { id: null, nup: '' }
    ]
  },

  formalization: {
    report_status: null,
    eparcerias_certificate_date: null
  },

  proponent: {
    secondary_email: null,
    secondary_phone: null
  }
})

console.log(props.availableSupervisors)
onMounted(() => {
  const opening = props.project.opening || {}
  const formalization = props.project.formalization || {}
  const proponent = props.project.proponent || {}

  form.opening = {
    opening_nup: opening.opening_nup ?? null,
    opening_date: opening.opening_date ?? null,
    agent_status: opening.agent_status ?? null,
    opened_by: opening.opened_by ?? null,
    bank: opening.bank ?? null,
    account_type: opening.account_type ?? null,
    branch: opening.branch ?? null,
    account: opening.account ?? null,
    supervisors: [
  {
    id: opening.supervisors?.[0]?.id ? Number(opening.supervisors[0].user_id) : null,
    nup: opening.supervisors?.[0]?.nup ?? ''
  },
  {
    id: opening.supervisors?.[1]?.id ? Number(opening.supervisors[1].user_id) : null,
    nup: opening.supervisors?.[1]?.nup ?? ''
  }
]
  }

  form.formalization = {
    report_status: formalization.report_status ?? null,
    eparcerias_certificate_date: formalization.eparcerias_certificate_date ?? null
  }

  form.proponent = {
    secondary_email: proponent.secondary_email ?? null,
    secondary_phone: proponent.secondary_phone ?? null
  }
})
const submit = () => {
  form
    .patch(
      route('projects.openings.update', {
        project: props.project.id,
        opening: props.project.opening.id,
      }),
      {
        preserveScroll: true,
        onSuccess: () => {
          showSnackbar('Abertura atualizada com sucesso!', 'success')
        },
        onError: (errors) => {
          console.error(errors)
        }
      }
    )
}
watch(() => form.opening.supervisors, (newVal) => {
  console.log(newVal)
  console.log('Current Form IDs:', newVal.map(s => s.id));
  console.log('Available Items:', props.availableSupervisors);
}, { deep: true });
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
        <section-chips v-model="activeViewIndex" :sections="viewSections" show-all-option />
        <div class="mt-4 space-y-8">
          <template v-for="(section, index) in viewSections" :key="'view-' + section.title">
            <section-content v-if="activeViewIndex === 'all' || activeViewIndex === index" :section="section"
              :project="project" />
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
            <v-btn variant="outlined" color="outlineSecondary" class="rounded-lg" :loading="form.processing"
              @click="submit">
              Salvar Alterações
            </v-btn>
          </div>
        </div>
        <aux-links />
        <section-chips v-model="activeEditIndex" :sections="formSections" />
        <div class="mt-4">
          <section-form :active-edit-index="activeEditIndex" :sections="formSections">
            <template #default="{ section }">
              <!-- Dados da Abertura -->
              <template v-if="section.key === 'opening'">
                <div class="grid grid-cols-2 gap-4">
                  <form-field label="Número do processo*" required>
                    <text-field v-model="form.opening.opening_nup" mask="####.######/####-##" />
                  </form-field>

                  <form-field label="Data de abertura do processo">
                    <text-field v-model="form.opening.opening_date" type="date" />
                  </form-field>

                  <form-field label="Status do agente cultural">
                    <select-field v-model="form.opening.agent_status" :items="props.agentStatus" placeholder="Selecione um tipo" />
                  </form-field>

                  <form-field label="Responsável por abrir o processo">
                    <text-field v-model="form.opening.opened_by" />
                  </form-field>
                </div>
              </template>
              <template v-else-if="section.key === 'certificate'">
                <div class="grid grid-cols-2 gap-4">
                  <form-field label="Regularidade e inadimplência">
                    <select-field v-model="form.formalization.report_status" :items="props.reportStatus" placeholder="Selecione um tipo" />
                  </form-field>

                  <form-field label="Data da certidão">
                    <text-field v-model="form.formalization.eparcerias_certificate_date" type="date" />
                  </form-field>
                </div>
              </template>
              <template v-else-if="section.key === 'bank'">
                <div class="grid grid-cols-2 gap-4">
                  <form-field label="Banco">
                    <text-field v-model="form.opening.bank" />
                  </form-field>
                  <form-field label="Tipo de conta">
                    <select-field v-model="form.opening.account_type" :items="props.accountType" placeholder="Selecione um tipo" />
                  </form-field>
                  <form-field label="Agência">
                    <text-field v-model="form.opening.branch" />
                  </form-field>
                  <form-field label="Conta">
                    <text-field v-model="form.opening.account" />
                  </form-field>
                </div>
              </template>
              <template v-else-if="section.key === 'supervisors'">
                <div class="grid grid-cols-2 gap-4">
                  <form-field label="Fiscal titular">
                    <user-autocomplete-field
                      v-if="availableSupervisors.length"
                      v-model="form.opening.supervisors[0].id"
                      :items="availableSupervisors"
                    />
                  </form-field>

                  <form-field label="Matrícula titular">
                    <text-field v-model="form.opening.supervisors[0].nup" />
                  </form-field>

                  <form-field label="Fiscal suplente">
                    <user-autocomplete-field v-model="form.opening.supervisors[1].id" :items="availableSupervisors" />
                  </form-field>

                  <form-field label="Matrícula suplente">
                    <text-field v-model="form.opening.supervisors[1].nup" />
                  </form-field>
                </div>
              </template>
              <template v-else-if="section.key === 'proponent'">
                <form-field label="Email secundário">
                  <text-field v-model="form.proponent.secondary_email" />
                </form-field>

                <form-field label="Telefone secundário">
                  <text-field v-model="form.proponent.secondary_phone" />
                </form-field>
              </template>

            </template>
          </section-form>
          <div class="w-full justify-center flex">
            <v-btn class="w-1/2 mt-4 !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg  text-xs"
              Atribuir Fiscal>
              tramitar
            </v-btn>
          </div>
        </div>
      </div>
    </template>
  </split-screen-tab>
</template>
