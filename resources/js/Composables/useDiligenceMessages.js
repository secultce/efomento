import { ref } from 'vue';
import { fetchDiligenceMessages, sendDiligenceMessage } from '@/Services/diligenceService';

export function useDiligenceMessages(projectId, stage) {
    const messages = ref([]);
    const loading = ref(false);
    const sending = ref(false);

    async function fetchMessages() {
        loading.value = true;
        try {
            const { data } = await fetchDiligenceMessages(projectId, stage);
            messages.value = data.messages ?? [];
        } finally {
            loading.value = false;
        }
    }

    async function send(payload) {
        sending.value = true;
        try {
            const { data } = await sendDiligenceMessage(projectId, stage, payload);
            messages.value.push(data.message);
            return data.message;
        } finally {
            sending.value = false;
        }
    }

    return {
        messages,
        loading,
        sending,
        fetchMessages,
        send,
    };
}
