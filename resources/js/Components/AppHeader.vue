<script setup>
import { router } from '@inertiajs/vue3'

defineProps({
    usuario: {
        type: Object,
        required: true,
    },
    notificacoes: {
        type: Number,
        default: 0,
    },
})

const iniciais = (name) =>
    name
        ?.split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase() ?? '?'

const logout = () => router.post(route('logout'))
</script>

<template>
    <v-app-bar color="primary" elevation="0" height="64">
        <v-app-bar-title>
            <a :href="route('notices.index')" class="text-white font-weight-bold text-h6 text-decoration-none">
                e-fomento
            </a>
        </v-app-bar-title>

        <template #append>
            <v-btn variant="text" color="white" href="/editais">
                Editais
            </v-btn>

            <v-btn variant="text" color="white">
                Indicadores
            </v-btn>

            <v-btn icon variant="text" color="white" class="mx-1">
                <v-badge
                    :content="notificacoes"
                    color="error"
                    :model-value="notificacoes > 0"
                >
                    <v-icon>mdi-bell-outline</v-icon>
                </v-badge>
            </v-btn>

            <v-menu location="bottom end">
                <template #activator="{ props: menuProps }">
                    <v-btn
                        v-bind="menuProps"
                        variant="text"
                        color="white"
                        class="px-3"
                        data-cy="btnUserAvatar"
                    >
                        <v-avatar size="32" color="white" class="mr-2">
                            <v-img
                                v-if="usuario.avatar"
                                :src="usuario.avatar"
                                :alt="usuario.name"
                            />
                            <span
                                v-else
                                class="text-primary text-caption font-weight-bold"
                            >
                                {{ iniciais(usuario.name) }}
                            </span>
                        </v-avatar>

                        <span class="text-body-2">{{ usuario.name }}</span>

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
                    <v-list-item
                        prepend-icon="mdi-logout"
                        base-color="error"
                        @click="logout"
                        data-cy="btnLogout"
                    >
                        Sair
                    </v-list-item>
                </v-list>
            </v-menu>
        </template>
    </v-app-bar>
</template>
