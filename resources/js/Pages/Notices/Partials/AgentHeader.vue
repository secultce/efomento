<script setup>
defineProps({
    notice: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <v-sheet class="pa-4" color="grey-lighten-4" border rounded>
        <v-row align="center">
            <v-col cols="12" md="7">
                <h2 class="text-h5 font-weight-bold mb-1">{{ notice.agent_name }}</h2>
                <div class="d-flex flex-wrap ga-3 align-center text-body-2 text-grey-darken-1">
                    <span>
                        <v-icon size="small" class="mr-1">mdi-file-document-outline</v-icon>
                        {{ notice.numero_processo || 'Sem processo' }}
                    </span>
                    <v-chip size="small" :color="notice.status_agente === 'Habilitado' ? 'success' : 'warning'" variant="tonal">
                        {{ notice.status_agente }}
                    </v-chip>
                    <v-chip size="small" :color="notice.status === 'em_andamento' ? 'info' : 'default'" variant="tonal">
                        {{ notice.status_label }}
                    </v-chip>
                </div>
            </v-col>
            <v-col cols="12" md="5" class="d-flex justify-end ga-2 flex-wrap">
                <v-btn variant="outlined" color="primary" size="small">
                    Iniciar para planilha
                </v-btn>
                <v-btn variant="text" color="primary" size="small">
                    Confira outros agentes da lista
                </v-btn>
            </v-col>
        </v-row>
    </v-sheet>
</template>
