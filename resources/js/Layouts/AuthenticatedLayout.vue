<script setup>
import { usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import AppSubHeader from '@/Components/AppSubHeader.vue';
import AppSnackbar from '@/Components/AppSnackbar.vue';

const page = usePage();
const usuario = page.props.auth.user;
</script>

<template>
    <v-app theme="efomento">
        <AppHeader :usuario="usuario" />
        <v-main>
            <app-snackbar />
            <AppSubHeader v-if="$slots.subheader">
                <slot name="subheader" />
            </AppSubHeader>
            
            <div class="bg-background">
                <slot />
            </div>
        </v-main>
    </v-app>
</template>
