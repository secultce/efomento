<script setup>
defineProps({
  phases: Array,
  selectedPhase: [String, null],
})

const emit = defineEmits(['select'])

function select(phase) {
  emit('select', phase)
}
</script>

<template>
  <div>
    <p class="text-sm">
      Utilize os quadros abaixo para filtrar e exibir a quantidade de agentes culturais em cada fase de processo
    </p>

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
          :disabled="phase.total === 0"
          :class="selectedPhase === phase.value
            ? '!bg-[#008344FF] !border-[#008344]'
            : '!border-[#ccccccFF]'"
          @click="select(phase)"
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
</template>