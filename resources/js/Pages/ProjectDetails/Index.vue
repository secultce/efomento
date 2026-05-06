<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppContainer from '@/Components/AppContainer.vue';
import AppSubHeader from '@/Components/AppSubHeader.vue';
import AgentData from './Partials/AgentData.vue';
import ProcessTabs from './Partials/ProcessTabs.vue';

const props = defineProps({
  project: Object,
  supervisorsAvailable: Array,
  agentStatus: Array,
  reportStatus: Array,
  accountType: Array
})
</script>

<template>
    <Head :title="`Projetos`" />
    <AuthenticatedLayout>
        <AppSubHeader show-back="true">
            <div class="grid grid-cols-[1fr_1fr_1fr_auto] items-start lg:w-10/12 gap-x-4 gap-y-2">
                <div class="col-span-3 col-start-1">
                    <h1>
                        {{ project?.notice?.name }}
                    </h1>
                </div>
            </div>
        </AppSubHeader>
        <AppContainer variant="large">
            <div class="gap-4 flex flex-col">
                <AgentData :project="project" />
                <ProcessTabs :project="project" :supervisorsAvailable="supervisorsAvailable" :agentStatus="agentStatus" :reportStatus="reportStatus" :accountType="accountType" />
            </div>
        </AppContainer>
    </AuthenticatedLayout>
</template>
