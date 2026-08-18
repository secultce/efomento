<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppNotificationMenu from '@/Components/AppNotificationMenu.vue';
import { useSnackbar } from '@/Composables/useSnackbar';

const { showSnackbar } = useSnackbar();

defineProps({
    user: {
        type: Object,
        required: true,
    },
    notificationsCount: {
        type: Number,
        default: 0,
    },
});

const iniciais = (name) =>
    name
        ?.split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase() ?? '?';

const logout = () => router.post(route('logout'));

const syncing = ref(false);

const syncNotices = () => {
    router.post(
        route('notices.sync'),
        {},
        {
            preserveScroll: true,
            onStart: () => {
                syncing.value = true;
            },
            onSuccess: (page) => {
                showSnackbar(page.props.flash?.success ?? 'Sincronização iniciada com sucesso!', 'success');
            },
            onError: () => {
                showSnackbar('Erro ao iniciar a sincronização.', 'error');
            },
            onFinish: () => {
                syncing.value = false;
            },
        }
    );
};
</script>

<template>
    <v-app-bar color="primary" elevation="0" height="64">
        <v-app-bar-title>
            <a :href="route('notices.index')" class="text-white font-weight-bold text-h6 text-decoration-none">
                e-fomento
            </a>
        </v-app-bar-title>

        <template #append>
            <v-btn variant="text" color="white" href="/editais"> Editais </v-btn>
            <v-btn variant="text" color="white"> Indicadores </v-btn>
            <v-btn variant="text" color="white" :loading="syncing" :disabled="syncing" @click="syncNotices">
                Sincronismo
            </v-btn>
            <app-notification-menu :notifications-count="notificationsCount" />
            <v-menu location="bottom end">
                <template #activator="{ props: menuProps }">
                    <v-btn v-bind="menuProps" variant="text" color="white" class="px-3" data-cy="btnUserAvatar">
                        <v-avatar size="32" color="white" class="mr-2">
                            <v-img v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                            <span v-else class="text-primary text-caption font-weight-bold">
                                {{ iniciais(user.name) }}
                            </span>
                        </v-avatar>

                        <span class="text-body-2">{{ user.name }}</span>

                        <v-icon end size="16">mdi-chevron-down</v-icon>
                    </v-btn>
                </template>

                <v-list density="compact" min-width="180">
                    <v-list-item
                        :href="route('profile.edit')"
                        prepend-icon="mdi-account-outline"
                        data-cy="btnUserAvatar"
                    >
                        Minha conta
                    </v-list-item>
                    <v-divider />
                    <v-list-item prepend-icon="mdi-logout" base-color="error" data-cy="btnLogout" @click="logout">
                        Sair
                    </v-list-item>
                </v-list>
            </v-menu>
        </template>
    </v-app-bar>
</template>
