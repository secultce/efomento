<script setup>
import { ref } from 'vue';

const notificationsMenu = ref(false);
</script>

<template>
    <v-menu v-model="notificationsMenu" location="bottom end" :close-on-content-click="false" offset="12">
        <template #activator="{ props }">
            <v-btn v-bind="props" color="black" class="bg-white" rounded>
                <v-badge :content="notificacoes" color="error" :model-value="notificacoes > 0">
                    <v-icon>mdi-bell-outline</v-icon>
                </v-badge>

                <span class="ml-2">Notificações</span>
            </v-btn>
        </template>

        <v-card width="420" rounded="lg">
            <v-card-title class="d-flex align-center justify-space-between">
                <span>Notificações</span>

                <v-btn icon="mdi-close" variant="text" size="small" @click="notificationsMenu = false" />
            </v-card-title>

            <v-divider />

            <v-card-text> Você não possui notificações. </v-card-text>

            <v-divider />

            <v-card-actions>
                <v-spacer />

                <v-btn variant="text" color="primary"> Ver todas </v-btn>
            </v-card-actions>
        </v-card>
    </v-menu>
</template>
