<script setup>
import { useSnackbar } from '@/Composables/useSnackbar';

const { snackbar, snackbarText, snackbarColor, snackbarTimeout } = useSnackbar();
</script>

<template>
    <v-snackbar
        v-model="snackbar"
        :color="snackbarColor"
        :timeout="snackbarTimeout"
        location="bottom right"
        data-cy="snack-alert"
    >
        {{ snackbarText }}
    </v-snackbar>
</template>
