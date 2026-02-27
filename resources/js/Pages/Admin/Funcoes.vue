<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
    modules: {
        type: Array,
        default: () => [],
    },
    actions: {
        type: Array,
        default: () => [],
    },
    allPermissions: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <template #subheader>
            <p class="text-white text-h6 ma-0">Administração de funções</p>
            <p class="text-caption ma-0" style="color: rgba(255,255,255,0.7);">
                Gerencie as permissões de cada perfil de acesso
            </p>
        </template>

        <v-container class="py-6">
            <slot />
        </v-container>
    </AuthenticatedLayout>
</template>
