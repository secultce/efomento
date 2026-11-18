<script setup>
import { Link } from '@inertiajs/vue3';
import { useEnums } from '@/Composables/useEnums';
import { useMask } from '@/Composables/useMask';

const { getLabel } = useEnums();
const { maskProcessNumber } = useMask();

const props = defineProps({
    project: { type: Object, default: null },
    agentStatus: { type: Array, default: () => [] },
    reportStatus: { type: Array, default: () => [] },
    openingStatus: { type: Array, default: () => [] },
});
</script>
<template>
    <v-card class="w-full !shadow-none border border-gray-800 rounded-lg">
        <div class="grid grid-cols-5 p-[0.5em] pl-4 gap-1">
            <div>
                <p>Nome do agente cultural</p>
                <p class="font-bold">{{ props.project?.agent?.name.toUpperCase() }}</p>
                <Link
                    v-if="props.project?.agent?.id"
                    :href="
                        route('agents.participations', {
                            agent: props.project.agent.id,
                            project: props.project.id,
                        })
                    "
                    class="text-primary hover:underline"
                >
                    Conferir histórico de participações
                </Link>
            </div>
            <div>
                <p>Número do processo</p>
                <p class="font-bold">{{ maskProcessNumber(props.project?.opening_nup) }}</p>
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
                    {{ props.project?.phase }}
                </p>
            </div>
        </div>
    </v-card>
</template>
