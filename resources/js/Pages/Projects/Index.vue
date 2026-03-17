<script setup>
import AppSubHeader from '@/Components/AppSubHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
  notices: Object,
  projects: Array,
});

</script>

<template>

  <Head title="Projetos" />
  <authenticated-layout>
    <app-sub-header>
    </app-sub-header>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <v-container class="py-6">
        <p class="text-[1em] font-weight-bold text-grey-darken-3">
          Utilize os quadros abaixo para filtrar e exibir a quantidade de agentes culturais em cada fase de processo
        </p>
      </v-container>
    </div>
  </authenticated-layout>
</template>