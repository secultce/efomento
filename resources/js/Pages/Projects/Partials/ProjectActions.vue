<template>
    <v-card class="w-full">
        <v-card-title class="font-weight-bold !text-lg">Ações disponíveis para você </v-card-title>
        <v-card-text>
            <!-- Add your Vuetify components here -->
             <div class="w-full">
                <v-btn color="primary">Criar CI</v-btn>
            </div>
            <v-btn color="secondary">Atribuir Fiscal</v-btn>
            <v-btn color="secondary">Conferir Histórico</v-btn>
        </v-card-text>
    </v-card>
</template>

<script>
export default {
    name: 'ProjectActions',
    // Add your component logic here
}
</script>

<style scoped>
/* Add your styles here */
</style>