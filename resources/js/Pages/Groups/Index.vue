<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Users from '@/Pages/Groups/Users.vue';
import {Head, router, usePage} from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';

const page = usePage();

const props = defineProps({
    modules:        { type: Array, required: true },
    actions:        { type: Array, required: true },
    allPermissions: { type: Array, required: true },
    roles:          { type: Array, required: true },
    users:          { type: Array, required: true}
});

const mainTab = ref('grupos');
const saving  = ref(false);
const flash   = ref('');

// ─── Matriz reativa: matrix[roleName][permName] = boolean ────────────────────
const matrix = reactive({});

props.roles.forEach(role => {
    matrix[role.name] = {};
    props.allPermissions.forEach(perm => {
        matrix[role.name][perm] = role.permissions.includes(perm);
    });
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

/** Verifica se a permissão existe no banco (ex: usuarios não tem view_own) */
const isValid = (moduleKey, actionKey) =>
    props.allPermissions.includes(`${moduleKey}.${actionKey}`);

/** Retorna as permissões válidas de um módulo */
const modulePerms = (moduleKey) =>
    props.allPermissions.filter(p => p.startsWith(`${moduleKey}.`));

const isAllChecked = (roleName, moduleKey) => {
    const perms = modulePerms(moduleKey);
    return perms.length > 0 && perms.every(p => matrix[roleName][p]);
};

const isSomeChecked = (roleName, moduleKey) => {
    const perms = modulePerms(moduleKey);
    return perms.some(p => matrix[roleName][p]) && !isAllChecked(roleName, moduleKey);
};

const toggleAll = (roleName, moduleKey) => {
    const perms = modulePerms(moduleKey);
    const allOn = isAllChecked(roleName, moduleKey);
    perms.forEach(p => { matrix[roleName][p] = !allOn; });
};

// ─── Salvar ──────────────────────────────────────────────────────────────────
const save = () => {
    saving.value = true;

    const rolesToSave = props.roles.map(role => ({
        name: role.name,
        permissions: Object.entries(matrix[role.name])
            .filter(([, checked]) => checked)
            .map(([perm]) => perm),
    }));

    router.put(route('groups.update'), { roles: rolesToSave }, {
        onSuccess: () => { flash.value = 'Permissões salvas com sucesso!'; },
        onError:   () => { flash.value = 'Erro ao salvar. Tente novamente.'; },
        onFinish:  () => { saving.value = false; },
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Funções e Grupos" />

    <AuthenticatedLayout>
        <div class="py-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Flash -->
                <v-alert
                    v-if="flash"
                    type="success"
                    variant="tonal"
                    closable
                    class="mb-4"
                    @click:close="flash = ''"
                >
                    {{ flash }}
                </v-alert>
                <v-container
                    class="mb-6"
                >
                    <v-row
                        align="start"
                        no-gutters
                    >
                        <v-col cols="2">
                            <v-row
                                align="start"
                                no-gutters
                            >
                                <v-col cols="3">
                                    <v-avatar
                                    >
                                        <v-img
                                            alt="Avatar"
                                            src="https://avatars0.githubusercontent.com/u/9064066?v=4&s=460"
                                        ></v-img>

                                    </v-avatar>
                                </v-col>
                                <v-col cols="9">
                                    <h3 class="text-[#008344FF] font-weight-bold mt-2"> {{ $page.props.auth.user.name }}</h3>
                                </v-col>
                            </v-row>


                        </v-col>
                        <v-col cols="10">
                            <v-row class="mb-2 p-2">
                                <v-col>
                                    <h3>
                                        Administração de funções
                                    </h3>
                                </v-col>
                                <v-col>
                                    <v-row>
                                        <v-col cols="6">
                                            <v-btn
                                                rounded="lg" variant="outlined"
                                                color="primary"
                                                block
                                            >
                                                <h6 class="text-[#008344FF]
                                                    text-md-body-2
                                                ">
                                                    cadastrar novo usuário
                                                </h6>
                                            </v-btn>
                                        </v-col>
                                        <v-col cols="6">
                                            <v-btn rounded="lg" block color="secundary">
                                               <h4 class=""> cadastrar novo grupo</h4>
                                            </v-btn>
                                        </v-col>
                                    </v-row>

                                </v-col>
                            </v-row>
                            <v-card>
                                <!-- Tabs -->
                                <v-tabs v-model="mainTab" color="primary" grow bg-color="transparent">
                                    <v-tab value="usuarios">Todos os usuários</v-tab>
                                    <v-tab value="grupos">Funções e grupos</v-tab>
                                </v-tabs>

                                <v-divider />

                                <v-tabs-window v-model="mainTab">

                                    <!-- Aba: Todos os usuários -->
                                    <v-tabs-window-item value="usuarios">
                                        <Users :users="users"/>
                                    </v-tabs-window-item>

                                    <!-- Aba: Funções e grupos -->
                                    <v-tabs-window-item value="grupos">
                                        <div class="pa-4">

                                            <v-table density="compact" fixed-header>
                                                <thead>
                                                <tr>
                                                    <th class="text-left min-w-48">Funcionalidades</th>
                                                    <th
                                                        v-for="action in actions"
                                                        :key="action.key"
                                                        class="text-center text-xs"
                                                    >
                                                        {{ action.label }}
                                                    </th>
                                                    <th class="text-center text-xs font-weight-bold">
                                                        Fazer tudo
                                                    </th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                <template v-for="module in modules" :key="module.key">

                                                    <!-- Linha de cabeçalho do módulo -->
                                                    <tr class="bg-grey-lighten-3">
                                                        <td
                                                            :colspan="actions.length + 2"
                                                            class="font-weight-bold text-grey-darken-3 py-2"
                                                        >
                                                            {{ module.label }}
                                                        </td>
                                                    </tr>

                                                    <!-- Uma linha por role -->
                                                    <tr
                                                        v-for="role in roles"
                                                        :key="`${module.key}-${role.name}`"
                                                    >
                                                        <td class="text-grey-darken-1 ps-6">
                                                            {{ role.label }}
                                                        </td>

                                                        <!-- Checkbox por ação -->
                                                        <td
                                                            v-for="action in actions"
                                                            :key="action.key"
                                                            class="text-center"
                                                        >
                                                            <v-checkbox-btn
                                                                v-if="isValid(module.key, action.key)"
                                                                v-model="matrix[role.name][`${module.key}.${action.key}`]"
                                                                color="red"
                                                                density="compact"
                                                            />
                                                        </td>

                                                        <!-- Fazer tudo -->
                                                        <td class="text-center">
                                                            <v-checkbox-btn
                                                                :model-value="isAllChecked(role.name, module.key)"
                                                                :indeterminate="isSomeChecked(role.name, module.key)"
                                                                color="primary"
                                                                density="compact"
                                                                @update:model-value="toggleAll(role.name, module.key)"
                                                            />
                                                        </td>
                                                    </tr>

                                                </template>
                                                </tbody>
                                            </v-table>

                                            <!-- Botão salvar -->
                                            <div class="d-flex justify-end mt-4">
                                                <v-btn
                                                    color="primary"
                                                    :loading="saving"
                                                    @click="save"
                                                >
                                                    Salvar permissões
                                                </v-btn>
                                            </div>

                                        </div>
                                    </v-tabs-window-item>

                                </v-tabs-window>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-container>


            </div>
        </div>
    </AuthenticatedLayout>
</template>

