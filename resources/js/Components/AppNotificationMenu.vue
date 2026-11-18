<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDate } from '@/Composables/useDate';
import { useSnackbar } from '@/Composables/useSnackbar';
import { fetchNotifications, markNotificationAsRead, markAllNotificationsAsRead } from '@/Services/notificationService';
import MessageDetailsDialog from '@/Components/MessageDetailsDialog.vue';

const notificationsMenu = ref(false);
const detailsDialog = ref(false);
const selectedNotification = ref(null);

const { formatRelativeDate } = useDate();
const { showSnackbar } = useSnackbar();

const props = defineProps({
    notificationsCount: {
        type: Number,
        default: 0,
    },

    initialNotifications: {
        type: Array,
        default: () => [],
    },
});

const notificationsData = ref(props.initialNotifications);

const currentPage = ref(1);
const pageCount = ref(1);
const loading = ref(false);
const selectedFilter = ref(null);

const selectedNotificationMeta = computed(() => {
    return selectedNotification.value?.data?.meta || {};
});

const selectedNotificationReason = computed(() => {
    return selectedNotificationMeta.value?.reason || '';
});

const selectedNotificationRoute = computed(() => {
    return selectedNotificationMeta.value?.route || null;
});

const selectedNotificationParams = computed(() => {
    return selectedNotificationMeta.value?.params || {};
});

const loadNotifications = async () => {
    if (loading.value) return;

    loading.value = true;

    try {
        const response = await fetchNotifications(currentPage.value, selectedFilter.value);

        notificationsData.value = response.data.userNotifications || [];
        pageCount.value = response.data.pageCount || 1;
    } catch (error) {
        console.error('Erro ao buscar notificações:', error);
    } finally {
        loading.value = false;
    }
};

watch(notificationsMenu, async (opened) => {
    if (opened) {
        await loadNotifications();
    }
});

watch(currentPage, async () => {
    if (notificationsMenu.value) {
        await loadNotifications();
    }
});

watch(selectedFilter, async () => {
    currentPage.value = 1;

    if (notificationsMenu.value) {
        await loadNotifications();
    }
});

const closeMenu = () => {
    notificationsMenu.value = false;
};

const openDetailsDialog = (notification) => {
    selectedNotification.value = notification;
    detailsDialog.value = true;
    closeMenu();
};

const goToSelectedNotificationRoute = () => {
    if (!selectedNotificationRoute.value) {
        return;
    }

    router.get(route(selectedNotificationRoute.value, selectedNotificationParams.value));

    detailsDialog.value = false;
};

const markAllAsRead = () => {
    markAllNotificationsAsRead({
        preserveScroll: true,

        onSuccess: () => {
            showSnackbar('Todas as notificações foram marcadas como lidas!', 'success');

            closeMenu();
        },

        onError: () => {
            showSnackbar('Erro ao marcar as notificações como lidas!', 'error');
        },
    });
};

const handleNavigation = (notification) => {
    const meta = notification?.data?.meta;

    const action = () => {
        if (meta?.reason) {
            openDetailsDialog(notification);
            return;
        }

        if (meta?.route) {
            router.get(route(meta.route, meta.params || {}));

            closeMenu();
        }
    };

    if (!notification.read_at) {
        markNotificationAsRead(notification.id, {
            preserveScroll: true,
            preserveState: true,

            onSuccess: () => {
                notification.read_at = new Date().toISOString();

                action();
            },
        });

        return;
    }

    action();
};
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

                <v-btn variant="text" color="primary" size="small" @click="markAllAsRead">
                    Marcar todas como lidas
                </v-btn>
            </v-card-title>

            <v-divider />

            <v-card-text class="!p-0">
                <div class="px-1 pb-1 pt-1 flex gap-2">
                    <v-chip
                        size="small"
                        :variant="selectedFilter === null ? 'flat' : 'outlined'"
                        color="primary"
                        @click="selectedFilter = null"
                    >
                        Todas
                    </v-chip>

                    <v-chip
                        size="small"
                        :variant="selectedFilter === false ? 'flat' : 'outlined'"
                        color="primary"
                        @click="selectedFilter = false"
                    >
                        Não lidas
                    </v-chip>

                    <v-chip
                        size="small"
                        :variant="selectedFilter === true ? 'flat' : 'outlined'"
                        color="primary"
                        @click="selectedFilter = true"
                    >
                        Lidas
                    </v-chip>
                </div>

                <div v-if="loading" class="p-4 text-center">Carregando...</div>

                <div v-else-if="!notificationsData || notificationsData.length === 0" class="p-4 text-center">
                    Você não possui notificações.
                </div>

                <div v-else>
                    <div
                        v-for="notification in notificationsData"
                        :key="notification.id"
                        :class="['mt-1 p-2 flex space-x-2', notification.read_at ? 'bg-[#f5f5f5]' : 'bg-white']"
                    >
                        <v-avatar color="primary">
                            <v-img
                                v-if="notification?.data?.meta?.user?.avatar"
                                :src="notification?.data?.meta?.user?.avatar"
                                alt="User Avatar"
                            />

                            <span v-else class="text-h6">
                                {{ notification?.data?.meta?.user?.name?.charAt(0).toUpperCase() || 'U' }}
                            </span>
                        </v-avatar>

                        <div class="flex-1">
                            <p class="text-sm font-bold hyphens-auto">
                                {{ notification?.data?.title }}
                            </p>

                            <p
                                v-if="notification?.data?.message"
                                class="text-sm leading-relaxed whitespace-pre-line break-words hyphens-auto"
                            >
                                {{ notification?.data?.message }}
                            </p>

                            <div class="mt-1">
                                <v-btn
                                    v-if="notification?.data?.meta?.route || notification?.data?.meta?.reason"
                                    class="bg-secondary !font-bold !text-xs !rounded-lg !shadow-none"
                                    size="small"
                                    @click="handleNavigation(notification)"
                                >
                                    Conferir
                                </v-btn>
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500 whitespace-nowrap">
                                {{ formatRelativeDate(notification?.created_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </v-card-text>

            <v-divider />

            <v-card-actions class="justify-center py-3">
                <v-pagination
                    v-if="pageCount > 1"
                    v-model="currentPage"
                    :length="pageCount"
                    :total-visible="pageCount"
                    density="comfortable"
                    rounded="circle"
                />
            </v-card-actions>
        </v-card>
    </v-menu>

    <MessageDetailsDialog
        v-model="detailsDialog"
        title="Detalhes da devolução"
        message-label="Mensagem enviada"
        :message="selectedNotificationReason"
        :sent-at="formatRelativeDate(selectedNotification?.created_at)"
        confirm-label="Ir para processo"
        :show-confirm="!!selectedNotificationRoute"
        message-html
        @confirm="goToSelectedNotificationRoute"
    >
        <template #description>
            <p v-if="selectedNotification?.data?.message" class="text-base mb-4">
                {{ selectedNotification.data.message }}
            </p>
        </template>
    </MessageDetailsDialog>
</template>
