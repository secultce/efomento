<script setup>
defineProps({
    loading: { type: Boolean, default: false },
    canSave: { type: Boolean, default: true },
});
</script>

<template>
    <v-btn
        variant="outlined"
        color="outlineSecondary"
        class="w-full rounded-lg"
        height="40"
        :loading="loading"
        :disabled="!canSave || loading"
        v-bind="$attrs"
    >
        <slot>Salvar Alterações</slot>
    </v-btn>
</template>
