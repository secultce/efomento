<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import dayjs from 'dayjs';
import { useDiligenceMessages } from '@/Composables/useDiligenceMessages';
import { useSnackbar } from '@/Composables/useSnackbar';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    stage: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: 'Envie mensagem ao agente cultural.',
    },
    subject: {
        type: String,
        default: '',
    },
});

const MIN_BODY_LENGTH = 20;

const stageLabels = {
    monitoramento: 'Monitoramento',
    prestacao_de_contas: 'Prestação de Contas',
};

const { messages, loading, sending, fetchMessages, send } = useDiligenceMessages(props.project.id, props.stage);
const { showSnackbar } = useSnackbar();

const body = ref('');
const threadEl = ref(null);
const stageUnavailable = ref(false);

const stageLabel = computed(() => stageLabels[props.stage] ?? props.stage);

const toEmail = computed(() => props.project.agent?.latest_snapshot?.email ?? null);

const computedSubject = computed(() => {
    if (props.subject) return props.subject;

    return `Diligência — ${stageLabel.value} — ${props.project.title_project ?? ''}`.trim();
});

const canSend = computed(() => Boolean(toEmail.value) && body.value.trim().length >= MIN_BODY_LENGTH);

const formatSentAt = (value) => (value ? dayjs(value).format('DD/MM/YYYY [às] HH:mm') : '');

async function scrollToBottom() {
    await nextTick();
    if (threadEl.value) {
        threadEl.value.scrollTop = threadEl.value.scrollHeight;
    }
}

async function submit() {
    if (!canSend.value || sending.value) return;

    try {
        await send({
            subject: computedSubject.value,
            body: body.value.trim(),
            to_email: toEmail.value,
        });
        body.value = '';
        showSnackbar('Mensagem enviada ao agente cultural.', 'success');
        scrollToBottom();
    } catch (error) {
        const errors = error.response?.data?.errors;
        const message = errors
            ? Object.values(errors).flat().join(' ')
            : 'Não foi possível enviar a mensagem. Tente novamente.';
        showSnackbar(message, 'error');
    }
}

watch(messages, scrollToBottom, { deep: true });

onMounted(async () => {
    try {
        await fetchMessages();
        scrollToBottom();
    } catch (error) {
        if (error.response?.status === 404) {
            stageUnavailable.value = true;
        } else {
            showSnackbar('Não foi possível carregar as mensagens da diligência.', 'error');
        }
    }
});
</script>

<template>
    <div v-if="stageUnavailable" class="space-y-3">
        <p class="text-sm text-gray-700">{{ description }}</p>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
            Os dados de {{ stageLabel }} deste projeto ainda não foram cadastrados. O envio de mensagens estará
            disponível assim que essa etapa for registrada.
        </div>
    </div>

    <div v-else class="space-y-3">
        <p class="text-sm text-gray-700">{{ description }}</p>

        <div ref="threadEl" class="bg-gray-100 rounded-lg p-4 max-h-96 overflow-y-auto space-y-3">
            <div v-if="loading" class="text-sm text-gray-500 text-center py-6">Carregando mensagens...</div>

            <div v-else-if="!messages.length" class="text-sm text-gray-500 text-center py-6">
                Nenhuma mensagem enviada até o momento.
            </div>

            <template v-else>
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="flex"
                    :class="message.direction === 'OUTBOUND' ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[85%] rounded-lg p-3 text-sm shadow-sm"
                        :class="message.direction === 'OUTBOUND' ? 'bg-white' : 'bg-amber-50'"
                    >
                        <p class="whitespace-pre-line text-gray-800">{{ message.body }}</p>
                        <p class="text-xs text-gray-500 text-right mt-2">
                            {{ message.direction === 'OUTBOUND' ? 'Enviada em' : 'Recebida em' }}
                            {{ formatSentAt(message.sent_at) }}
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <v-textarea
            v-model="body"
            placeholder="Escreva uma mensagem aqui..."
            variant="outlined"
            density="comfortable"
            rows="3"
            auto-grow
            hide-details
        />

        <div class="flex items-center justify-between">
            <p v-if="!toEmail" class="text-xs text-red-600">O agente cultural não possui e-mail cadastrado.</p>
            <p v-else-if="body.trim().length > 0 && body.trim().length < MIN_BODY_LENGTH" class="text-xs text-gray-500">
                A mensagem deve ter pelo menos {{ MIN_BODY_LENGTH }} caracteres.
            </p>
            <span v-else />

            <v-btn
                variant="outlined"
                color="grey-darken-2"
                append-icon="mdi-send"
                :disabled="!canSend"
                :loading="sending"
                @click="submit"
            >
                Enviar mensagem
            </v-btn>
        </div>
    </div>
</template>
