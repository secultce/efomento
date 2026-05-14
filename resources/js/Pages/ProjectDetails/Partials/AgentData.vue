<script setup>
import { useEnums } from '@/Composables/useEnums';

const props = defineProps({
    project: { type: Object, default: null },
    agentStatus: { type: Array, default: () => [] },
    reportStatus: { type: Array, default: () => [] },
    openingStatus: { type: Array, default: () => [] },
});

const { getLabel } = useEnums();
</script>
<template>
    <v-card class="w-full !shadow-none border border-gray-800 rounded-lg">
        <div class="grid grid-cols-5 p-[0.5em] pl-4 gap-1">
            <div>
                <p>Nome do agente cultural</p>
                <p class="font-bold">{{ props.project?.agent?.name.toUpperCase() }}</p>
                <p class="text-primary hover:cursor-pointer hover:underline">Conferir histórico de participações</p>
            </div>
            <div>
                <p>Número do processo</p>
                <p class="font-bold">{{ props.project?.opening_nup }}</p>
            </div>
            <div>
                <p>Status do agente cultural</p>
                <p class="font-bold">
                    {{ getLabel(props.agentStatus, props.project?.opening?.agent_status) }}
                </p>
            </div>
            <div>
                <p>Status do processo</p>
                <p class="font-bold">
                    {{ getLabel(props.openingStatus, props.project?.opening?.status) }}
                </p>
            </div>
        </div>
    </v-card>
</template>
