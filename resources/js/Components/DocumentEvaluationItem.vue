<script setup>
import { ref } from 'vue';
import { useSnackbar } from '@/Composables/useSnackbar';

const props = defineProps({
    file: {
        type: Object,
        required: true,
    },
    project: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['status-updated']);

const { showSnackbar } = useSnackbar();

const statusOptions = [
    { title: 'De acordo', value: 'valid' },
    { title: 'Necessita de ajuste', value: 'invalid' },
];

const selectedStatus = ref(props.file.status ?? null);
const loading = ref(false);

async function onStatusChange(status) {
    if (!status) return;

    loading.value = true;
    try {
        await window.axios.put(`/projetos/${props.project.id}/analise-juridica/arquivos/${props.file.id}`, {
            status,
        });

        emit('status-updated', { fileId: props.file.id, status });
    } catch {
        showSnackbar('Erro ao salvar o status. Tente novamente.', 'error');
        selectedStatus.value = props.file.status ?? null;
    } finally {
        loading.value = false;
    }
}

function openFile() {
    if (props.file.url) {
        window.open(props.file.url, '_blank');
    }
}

function downloadFile() {
    if (props.file.url) {
        window.open(props.file.url + '?download=1', '_blank');
    }
}
</script>

<template>
    <v-card variant="outlined" class="rounded-lg px-4 py-2">
        <div class="flex items-center justify-between gap-4">
            <span class="text-sm font-semibold text-[#3b3b3c] truncate flex-1" :title="file.title">
                {{ file.title }}
            </span>

            <div class="flex items-center gap-2 flex-shrink-0">
                <v-select
                    v-model="selectedStatus"
                    :items="statusOptions"
                    item-title="title"
                    item-value="value"
                    placeholder="Selecione um status"
                    density="compact"
                    variant="outlined"
                    hide-details
                    :loading="loading"
                    :disabled="loading"
                    class="w-52"
                    @update:model-value="onStatusChange"
                />

                <v-btn icon size="small" variant="text" :disabled="!file.url" @click="openFile">
                    <v-icon color="primary" size="22">mdi-eye</v-icon>
                </v-btn>

                <v-btn icon size="small" variant="text" :disabled="!file.url" @click="downloadFile">
                    <v-icon color="primary" size="22">mdi-download</v-icon>
                </v-btn>
            </div>
        </div>
    </v-card>
</template>
