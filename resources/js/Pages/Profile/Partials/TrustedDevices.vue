<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ devices: { type: Array, required: true } });
const form = useForm({});
const dialog = ref(false);
const selected = ref(null);
const status = ref('');

const deviceInfo = (agent = '') => {
    agent ??= '';
    const browser = /Edg(A|iOS)?\//.test(agent)
        ? 'Microsoft Edge'
        : /OPR\//.test(agent)
          ? 'Opera'
          : /Firefox\/|FxiOS\//.test(agent)
            ? 'Firefox'
            : /Chrome\/|CriOS\//.test(agent)
              ? 'Chrome'
              : /Safari\//.test(agent)
                ? 'Safari'
                : 'Navegador não identificado';
    const platform = /Android/.test(agent)
        ? 'Android'
        : /iPhone|iPad|iPod/.test(agent)
          ? 'iOS'
          : /Windows/.test(agent)
            ? 'Windows'
            : /Macintosh|Mac OS X/.test(agent)
              ? 'macOS'
              : /Linux/.test(agent)
                ? 'Linux'
                : null;
    return {
        name: platform ? `${browser} · ${platform}` : browser,
        icon: /Android|iPhone|iPad|iPod/.test(agent) ? 'mdi-cellphone' : 'mdi-monitor',
    };
};
const formatDate = (value) =>
    value ? new Date(value).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : 'Não informado';
const confirmRemoval = (device = null) => {
    selected.value = device;
    form.clearErrors();
    dialog.value = true;
};
const remove = () => {
    if (form.processing) return;
    const url = selected.value
        ? route('profile.trusted-devices.destroy', selected.value.id)
        : route('profile.trusted-devices.destroy-all');
    form.delete(url, {
        preserveScroll: true,
        onSuccess: () => {
            status.value = selected.value ? 'Dispositivo removido da lista.' : 'Todos os dispositivos foram removidos.';
            dialog.value = false;
        },
    });
};
</script>

<template>
    <section aria-labelledby="devices-title">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 id="devices-title" class="text-h5 font-weight-bold">Dispositivos confiáveis</h3>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600">
                    Navegadores nos quais você escolheu entrar sem receber um código por email a cada acesso. Confira os
                    dispositivos e remova os que não reconhece ou não usa mais.
                </p>
            </div>
            <v-btn
                v-if="devices.length"
                variant="outlined"
                color="outlineSecondary"
                rounded="lg"
                :disabled="form.processing"
                @click="confirmRemoval()"
            >
                Remover todos
            </v-btn>
        </div>

        <v-alert v-if="status" type="success" variant="tonal" rounded="lg" class="mb-4" role="status">
            {{ status }}
        </v-alert>
        <v-alert variant="tonal" color="primary" icon="mdi-information-outline" rounded="lg" class="mb-5">
            Esta lista não representa todas as sessões ou o histórico completo de acessos. Remover a confiança exige um
            novo código no próximo login, mas não encerra sessões já abertas.
        </v-alert>

        <v-card v-if="!devices.length" variant="outlined" rounded="lg" class="pa-8 text-center">
            <v-icon icon="mdi-shield-check-outline" size="48" color="primary" aria-hidden="true" />
            <h4 class="mt-4 text-lg font-bold">Nenhum dispositivo confiável</h4>
            <p class="mx-auto mt-2 max-w-md text-sm text-gray-600">
                Ao confirmar um código de acesso, você pode escolher confiar no dispositivo. Ele aparecerá aqui para
                você consultar ou remover.
            </p>
        </v-card>

        <div v-else class="space-y-4">
            <v-card v-for="device in devices" :key="device.id" variant="outlined" rounded="lg" class="pa-5">
                <div class="flex flex-wrap items-start gap-4">
                    <v-avatar color="primary" variant="tonal" rounded="lg" size="48" aria-hidden="true">
                        <v-icon :icon="deviceInfo(device.user_agent).icon" />
                    </v-avatar>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-bold">{{ deviceInfo(device.user_agent).name }}</h4>
                            <v-chip v-if="device.is_current" size="small" color="primary" variant="tonal">
                                Este navegador
                            </v-chip>
                            <v-chip v-if="device.expired" size="small" color="grey" variant="tonal">
                                Confiança expirada
                            </v-chip>
                        </div>
                        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-gray-600">Último login com este dispositivo</dt>
                                <dd class="mt-1 font-semibold">{{ formatDate(device.last_used_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-600">IP registrado no último login</dt>
                                <dd class="mt-1 break-all font-semibold">{{ device.ip_address || 'Não informado' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-600">Adicionado em</dt>
                                <dd class="mt-1">{{ formatDate(device.created_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-600">
                                    {{ device.expired ? 'Confiança expirou em' : 'Confiança válida até' }}
                                </dt>
                                <dd class="mt-1">{{ formatDate(device.expires_at) }}</dd>
                            </div>
                        </dl>
                    </div>
                    <v-btn
                        variant="text"
                        color="error"
                        rounded="lg"
                        :disabled="form.processing"
                        :aria-label="`Remover ${deviceInfo(device.user_agent).name}`"
                        @click="confirmRemoval(device)"
                    >
                        Remover
                    </v-btn>
                </div>
            </v-card>
        </div>
        <p class="mt-4 text-xs text-gray-600">
            Os nomes são identificados a partir das informações do navegador. As datas aparecem no fuso horário deste
            dispositivo.
        </p>

        <v-dialog v-model="dialog" max-width="480" :persistent="form.processing" aria-labelledby="revoke-title">
            <v-card rounded="lg" class="pa-6">
                <h4 id="revoke-title" class="text-h6 font-weight-bold">
                    {{ selected ? 'Remover dispositivo confiável?' : 'Remover todos os dispositivos?' }}
                </h4>
                <p v-if="selected" class="mt-3 font-semibold">{{ deviceInfo(selected.user_agent).name }}</p>
                <p class="mt-3 text-sm leading-relaxed text-gray-600">
                    {{ selected ? 'Este dispositivo precisará' : 'Todos os dispositivos precisarão' }} de um código por
                    email no próximo login. As sessões já abertas continuarão ativas.
                </p>
                <v-alert v-if="Object.keys(form.errors).length" type="error" variant="tonal" class="mt-4">
                    {{ Object.values(form.errors)[0] }}
                </v-alert>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <v-btn variant="text" :disabled="form.processing" @click="dialog = false">Cancelar</v-btn>
                    <v-btn
                        color="error"
                        variant="flat"
                        :loading="form.processing"
                        :disabled="form.processing"
                        @click="remove"
                    >
                        {{ selected ? 'Remover dispositivo' : 'Remover todos' }}
                    </v-btn>
                </div>
            </v-card>
        </v-dialog>
    </section>
</template>
