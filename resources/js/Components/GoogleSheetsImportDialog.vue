<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useSnackbar } from '@/Composables/useSnackbar';

const props = defineProps({ modelValue: Boolean });
const emit = defineEmits(['update:modelValue']);
const page = usePage();
const { showSnackbar } = useSnackbar();
const users = ref([]);
const loadingUsers = ref(false);
const usersError = ref(false);
const outdatedRoutes = ref(false);
const form = useForm({ spreadsheet_url: '', user_id: null, fallback_notice_id: '' });
const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => {
        if (!form.processing) emit('update:modelValue', value);
    },
});

const loadUsers = async () => {
    usersError.value = false;
    outdatedRoutes.value = !route().has('spreadsheets.users') || !route().has('spreadsheets.sync');
    if (outdatedRoutes.value) return;

    loadingUsers.value = true;
    try {
        const { data } = await axios.get(route('spreadsheets.users'));
        users.value = data;
    } catch {
        usersError.value = true;
    } finally {
        loadingUsers.value = false;
    }
};

const reloadPage = () => window.location.reload();

watch(isOpen, (open) => {
    if (!open) return;
    form.reset();
    form.clearErrors();
    form.user_id = page.props.auth.user.id;
    users.value = [page.props.auth.user];
    loadUsers();
});

const submit = () => {
    if (form.processing || loadingUsers.value || usersError.value || outdatedRoutes.value) return;
    form.post(route('spreadsheets.sync'), {
        preserveScroll: true,
        onSuccess: (response) => {
            showSnackbar(response.props.flash?.success ?? 'Sincronização iniciada em segundo plano.', 'success');
            emit('update:modelValue', false);
        },
        onError: (errors) =>
            showSnackbar(Object.values(errors)[0] ?? 'Não foi possível iniciar a sincronização.', 'error'),
    });
};
</script>

<template>
    <v-dialog v-model="isOpen" max-width="640" :persistent="form.processing" scrollable>
        <v-card rounded="lg">
            <v-card-title class="pa-6 pb-2">Sincronizar Planilha</v-card-title>
            <v-card-text>
                <v-form id="google-sheets-import" @submit.prevent="submit">
                    <p class="text-body-2 mb-4">
                        Importe ou atualize os dados de uma planilha do Google Sheets. Você receberá o resultado no sino
                        de notificações.
                    </p>
                    <v-text-field
                        v-model="form.spreadsheet_url"
                        label="Link ou ID da planilha"
                        placeholder="https://docs.google.com/spreadsheets/d/.../edit"
                        hint="A planilha deve permitir que qualquer pessoa com o link possa visualizar."
                        persistent-hint
                        :error-messages="form.errors.spreadsheet_url"
                        :disabled="form.processing"
                        variant="outlined"
                        class="mb-4"
                        autofocus
                        required
                        data-cy="spreadsheetUrl"
                    />
                    <v-alert v-if="outdatedRoutes" type="warning" variant="tonal" class="mb-4">
                        A aplicação foi atualizada. Recarregue a página para usar a importação de planilhas.
                        <v-btn variant="text" size="small" @click="reloadPage">Recarregar página</v-btn>
                    </v-alert>
                    <v-alert v-else-if="usersError" type="error" variant="tonal" class="mb-4">
                        Não foi possível carregar os usuários.
                        <v-btn variant="text" size="small" @click="loadUsers">Tentar novamente</v-btn>
                    </v-alert>
                    <v-autocomplete
                        v-model="form.user_id"
                        :items="users"
                        item-title="name"
                        item-value="id"
                        label="Usuário responsável"
                        hint="Responsável pelos registros criados na importação."
                        persistent-hint
                        :loading="loadingUsers"
                        :disabled="form.processing || loadingUsers"
                        :error-messages="form.errors.user_id"
                        variant="outlined"
                        class="mb-4"
                        required
                        data-cy="spreadsheetUser"
                    >
                        <template #item="{ props: itemProps, item }">
                            <v-list-item v-bind="itemProps">
                                <template #prepend>
                                    <v-avatar size="32" color="primary" class="mr-3">
                                        <v-img v-if="item.raw.avatar" :src="item.raw.avatar" :alt="item.raw.name" />
                                        <span v-else>{{ item.raw.name?.charAt(0)?.toUpperCase() }}</span>
                                    </v-avatar>
                                </template>
                            </v-list-item>
                        </template>
                    </v-autocomplete>
                    <v-text-field
                        v-model="form.fallback_notice_id"
                        label="ID interno do edital (opcional)"
                        type="number"
                        min="1"
                        step="1"
                        hint="Deixe vazio para identificar o edital pela planilha. Preencha apenas como alternativa, usando o ID do e-fomento, não o ID do Mapas."
                        persistent-hint
                        :error-messages="form.errors.fallback_notice_id"
                        :disabled="form.processing"
                        variant="outlined"
                        class="mb-4"
                        data-cy="spreadsheetNotice"
                    />
                    <p class="text-body-2 mb-2">Abas importadas em sequência:</p>
                    <div class="d-flex flex-wrap ga-2 mb-3">
                        <v-chip
                            v-for="sheet in ['Abertura', 'Formalização', 'Orçamento', 'Pagamento']"
                            :key="sheet"
                            size="small"
                        >
                            {{ sheet }}
                        </v-chip>
                    </div>
                    <div class="d-flex flex-wrap ga-2">
                        <v-chip size="small" color="success" prepend-icon="mdi-check">Arquivos incluídos</v-chip>
                        <v-chip size="small" color="success" prepend-icon="mdi-check"
                            >Ficha de inscrição incluída</v-chip
                        >
                    </div>
                </v-form>
            </v-card-text>
            <v-card-actions class="pa-4 flex-wrap ga-2">
                <v-spacer />
                <v-btn :disabled="form.processing" @click="isOpen = false">Cancelar</v-btn>
                <v-btn
                    type="submit"
                    form="google-sheets-import"
                    color="primary"
                    variant="flat"
                    :loading="form.processing"
                    :disabled="form.processing || loadingUsers || usersError || outdatedRoutes"
                    data-cy="submitSpreadsheetImport"
                >
                    Iniciar sincronização
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
