<script setup>
import { computed, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RegistrationPhasesModal from '@/Components/RegistrationPhasesModal.vue';

const props = defineProps({
    project: { type: Object, required: true },
});

const open = ref(false);

const phases = computed(() => props.project.registration_phases ?? []);
</script>

<template>
    <div>
        <PrimaryButton
            type="button"
            data-cy="registration-phases-button"
            :disabled="!phases.length"
            :class="{ 'opacity-50 cursor-not-allowed': !phases.length }"
            @click="open = true"
        >
            CONFERIR DOCUMENTAÇÃO
        </PrimaryButton>
        <RegistrationPhasesModal v-model="open" :phases="phases" :total-phases="project.total_phases" />
    </div>
</template>
