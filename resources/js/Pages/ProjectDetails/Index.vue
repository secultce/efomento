<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppContainer from '@/Components/AppContainer.vue';
import AppSubHeader from '@/Components/AppSubHeader.vue';
import AgentData from './Partials/AgentData.vue';
import ProcessTabs from './Partials/ProcessTabs.vue';

const props = defineProps({
  project: Object,
})
</script>

<template>
    <Head :title="`Projetos`" />
    <AuthenticatedLayout>
        <AppSubHeader>
            <div class="grid grid-cols-[1fr_1fr_1fr_auto] items-start lg:mx-[8em] lg:w-10/12 gap-x-4 gap-y-2">
                <div class="col-span-3 col-start-1">
                    <h1>
                        {{ props?.project?.notice?.name }}
                    </h1>
                </div>
            </div>
        </AppSubHeader>
        <AppContainer variant="large">
            <div class="gap-4 flex flex-col">
                <AgentData :project="props.project" />
                <ProcessTabs :project="props.project" />
            </div>
        </AppContainer>
    </AuthenticatedLayout>
</template>
