<script setup>
import { ref } from 'vue';

const notificationsMenu = ref(false);

defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
    notificationsCount: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <v-menu v-model="notificationsMenu" location="bottom end" :close-on-content-click="false" offset="12">
        <template #activator="{ props: menuProps }">
            <v-btn v-bind="menuProps" color="black" class="bg-white" rounded>
                <v-badge :content="notificationsCount" color="error" :model-value="notificationsCount > 0">
                    <v-icon>mdi-bell-outline</v-icon>
                </v-badge>
                <span class="ml-2">Notificações</span>
            </v-btn>
        </template>

        <v-card width="420" rounded="lg">
            <v-card-title class="d-flex align-center justify-space-between">
                <span class="font-bold text-sm">Notificações</span>

                <v-btn variant="text" color="primary" size="small" @click="notificationsMenu = false">
                    Marcar todas como lidas
                </v-btn>
            </v-card-title>

            <v-divider />

            <v-card-text class="!p-0">
                <div v-if="notifications.length === 0">Você não possui notificações.</div>

                <div v-else>
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="bg-[#f5f5f5FF] mt-1 p-2 flex space-x-2"
                    >
                        <v-avatar
                            :image="
                                notification?.data?.avatar ||
                                'https://i.ibb.co/ZRZh8d7F/Captura-de-tela-de-2026-05-14-15-56-17.png'
                            "
                        ></v-avatar>
                        <div>
                            <p>{{ notification?.data?.title }}</p>
                            <div>
                                <v-btn class="bg-secondary !font-bold !text-xs !rounded-lg">Conferir</v-btn>
                            </div>
                        </div>
                    </div>
                </div>
            </v-card-text>

            <v-divider />

            <v-card-actions>
                <v-spacer />
            </v-card-actions>
        </v-card>
    </v-menu>
</template>
