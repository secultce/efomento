<script setup>
import { useAlert } from '@/Composables/useAlert';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: { type: Array, required: true },
    roles: { type: Array, required: true },
});

const { showAlert } = useAlert();

const initial = (name) => name?.trim().charAt(0).toUpperCase() || '?';

const dialog = ref(false);
const selectedUser = ref(null);
const selectedRole = ref(null);
const saving = ref(false);

const openDialog = (user) => {
    selectedUser.value = user;
    selectedRole.value = null;
    dialog.value = true;
};

const closeDialog = () => {
    dialog.value = false;
    selectedUser.value = null;
    selectedRole.value = null;
};

const confirm = () => {
    if (!selectedRole.value) return;

    saving.value = true;

    router.post(
        route('users.assign-role', { user: selectedUser.value.id, role: selectedRole.value }),
        {},
        {
            onSuccess: () => {
                closeDialog();
                showAlert({
                    alertTitle: 'Função atribuída',
                    alertMessage: 'A função foi atribuída ao usuário com sucesso.',
                    confirmText: 'Entendi',
                });
            },
            onError: (errors) => {
                showAlert({
                    alertTitle: 'Erro',
                    alertMessage: errors.role ?? 'Não foi possível atribuir a função. Tente novamente.',
                    confirmText: 'Entendi',
                });
            },
            onFinish: () => {
                saving.value = false;
            },
            preserveScroll: true,
        }
    );
};

const roleItems = props.roles.map((r) => ({ title: r.label, value: r.name }));

const removeRole = (user, roleName) => {
    router.delete(route('users.remove-role', { user: user.id, role: roleName }), {
        onSuccess: () => {
            showAlert({
                alertTitle: 'Função removida',
                alertMessage: 'A função foi removida do usuário com sucesso.',
                confirmText: 'Entendi',
            });
        },
        onError: (errors) => {
            showAlert({
                alertTitle: 'Erro',
                alertMessage: errors.role ?? 'Não foi possível remover a função. Tente novamente.',
                confirmText: 'Entendi',
            });
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <div v-for="user in users" :key="user.id">
        <v-sheet class="pa-2" border>
            <v-row align="center" no-gutters>
                <v-col cols="1">
                    <v-checkbox-btn></v-checkbox-btn>
                </v-col>
                <v-col cols="1">
                    <v-avatar size="25" color="primary">
                        <v-img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name"></v-img>
                        <span v-else class="text-caption font-weight-bold text-white">{{ initial(user.name) }}</span>
                    </v-avatar>
                </v-col>
                <v-col cols="2">
                    <h4 class="font-weight-bold">{{ user.name }}</h4>
                </v-col>
                <v-col cols="3">
                    <span class="text-body-2">{{ user.email }}</span>
                </v-col>
                <v-col cols="2">
                    <v-chip v-if="user.roles.length === 0" size="small" color="grey" variant="tonal" class="mr-1">
                        sem atribuição
                    </v-chip>
                    <v-chip
                        v-for="role in user.roles"
                        :key="role.name"
                        size="small"
                        color="primary"
                        variant="tonal"
                        class="mr-1"
                        closable
                        @click:close="removeRole(user, role.name)"
                    >
                        {{ role.label }}
                    </v-chip>
                </v-col>
                <v-col cols="2" class="d-flex justify-end">
                    <v-btn variant="outlined" color="primary" rounded="lg" height="25" @click="openDialog(user)">
                        atribuir função
                    </v-btn>
                </v-col>
            </v-row>
        </v-sheet>
    </div>

    <v-dialog v-model="dialog" max-width="574" persistent>
        <v-card class="rounded-xl pa-4">
            <v-card-title class="text-h6 font-weight-bold pb-2">
                Altere ou remova uma função de um usuário
            </v-card-title>

            <v-card-text class="pt-2">
                <p class="text-body-2 text-grey-darken-1 mb-2">Funções</p>
                <v-select
                    v-model="selectedRole"
                    :items="roleItems"
                    item-title="title"
                    item-value="value"
                    placeholder="Selecione uma função"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                />
            </v-card-text>

            <v-card-actions class="justify-end gap-2 pt-2">
                <v-btn variant="outlined" rounded="lg" class="px-6" @click="closeDialog">Cancelar</v-btn>
                <v-btn
                    rounded="lg"
                    class="px-6 !bg-[#ffcc05FF] !text-[#2d353fFF] font-weight-bold"
                    :loading="saving"
                    :disabled="!selectedRole"
                    @click="confirm"
                >
                    Confirmar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
