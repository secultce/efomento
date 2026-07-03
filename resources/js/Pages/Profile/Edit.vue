<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useAlert } from '@/Composables/useAlert';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const user = usePage().props.auth.user;
const { showAlert } = useAlert();

const activeTab = ref('info');

const profileForm = useForm({
    name: user.name,
    email: user.email,
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const iniciais = (name) =>
    name
        ?.split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase() ?? '?';

function saveProfile() {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showAlert({
                alertTitle: 'Sucesso',
                alertMessage: 'Suas informações foram atualizadas com sucesso.',
                confirmText: 'OK',
            });
        },
    });
}

function changePassword() {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showAlert({
                alertTitle: 'Sucesso',
                alertMessage: 'Sua senha foi alterada com sucesso.',
                confirmText: 'OK',
            });
        },
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
        },
    });
}
</script>

<template>
    <Head title="Meu Perfil" />

    <AuthenticatedLayout>
        <div class="py-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <v-container class="mb-6">
                    <v-row align="start" no-gutters>
                        <v-col cols="3" class="pa-2">
                            <v-row align="start" no-gutters>
                                <v-col cols="3">
                                    <v-avatar size="50">
                                        <v-img v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                        <span v-else class="text-primary text-caption font-weight-bold">
                                            {{ iniciais(user.name) }}
                                        </span>
                                    </v-avatar>
                                </v-col>
                                <v-col cols="9">
                                    <h3 class="text-[#008344FF] font-weight-bold mt-2">
                                        {{ user.name }}
                                    </h3>
                                </v-col>
                                <v-card class="mx-auto mt-4" width="100%" variant="flat">
                                    <v-list density="compact">
                                        <v-list-item
                                            :value="'info'"
                                            color="primary"
                                            :active="activeTab === 'info'"
                                            @click="activeTab = 'info'"
                                        >
                                            <template #prepend>
                                                <v-icon icon="mdi-account" />
                                            </template>
                                            <v-list-item-title>Minhas informações</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item
                                            :value="'password'"
                                            color="primary"
                                            :active="activeTab === 'password'"
                                            @click="activeTab = 'password'"
                                        >
                                            <template #prepend>
                                                <v-icon icon="mdi-key" />
                                            </template>
                                            <v-list-item-title>Senha</v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                </v-card>
                            </v-row>
                        </v-col>

                        <v-col cols="9">
                            <template v-if="activeTab === 'info'">
                                <v-row class="mb-2 p-2">
                                    <v-col>
                                        <h3>Minhas informações</h3>
                                    </v-col>
                                </v-row>
                                <v-card>
                                    <div class="pa-6">
                                        <v-text-field
                                            v-model="profileForm.name"
                                            label="Nome"
                                            variant="outlined"
                                            :error-messages="profileForm.errors.name"
                                            class="mb-2"
                                        />
                                        <v-text-field
                                            v-model="profileForm.email"
                                            label="E-mail"
                                            type="email"
                                            variant="outlined"
                                            :error-messages="profileForm.errors.email"
                                            class="mb-2"
                                        />
                                        <v-btn
                                            rounded="lg"
                                            variant="outlined"
                                            :loading="profileForm.processing"
                                            @click="saveProfile"
                                        >
                                            salvar
                                        </v-btn>
                                    </div>
                                </v-card>
                            </template>

                            <template v-if="activeTab === 'password'">
                                <v-row class="mb-2 p-2">
                                    <v-col>
                                        <h3>Senha</h3>
                                    </v-col>
                                </v-row>
                                <v-card>
                                    <div class="pa-6">
                                        <v-text-field
                                            v-model="passwordForm.password"
                                            label="Nova senha"
                                            type="password"
                                            variant="outlined"
                                            :error-messages="passwordForm.errors.password"
                                            class="mb-2"
                                        />
                                        <v-text-field
                                            v-model="passwordForm.password_confirmation"
                                            label="Confirme a nova senha"
                                            type="password"
                                            variant="outlined"
                                            :error-messages="passwordForm.errors.password_confirmation"
                                            class="mb-2"
                                        />
                                        <v-btn
                                            rounded="lg"
                                            variant="outlined"
                                            :loading="passwordForm.processing"
                                            @click="changePassword"
                                        >
                                            alterar senha
                                        </v-btn>
                                    </div>
                                </v-card>
                            </template>
                        </v-col>
                    </v-row>
                </v-container>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
