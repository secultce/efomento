<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
    },
    backLabel: {
        type: String,
        default: 'Voltar',
    },
    backRoute: {
        type: String,
        default: null,
    },
    showBack: {
        type: Boolean,
        default: false,
    },
});

function goBack() {
    const fallbackRoute = '/editais';
    const destination = props.backRoute || fallbackRoute;

    try {
        const targetUrl = new URL(destination, window.location.origin);

        if (!['http:', 'https:'].includes(targetUrl.protocol)) {
            console.error('Protocolo não permitido:', targetUrl.protocol);
            router.visit(fallbackRoute);
            return;
        }

        if (targetUrl.origin !== window.location.origin) {
            window.location.assign(targetUrl.href);
            return;
        }

        const relativeUrl = `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`;

        router.visit(relativeUrl, {
            method: 'get',
            preserveState: false,
            preserveScroll: true,
            replace: true,
        });
    } catch (error) {
        console.error('Rota de retorno inválida:', destination, error);

        router.visit(fallbackRoute);
    }
}
</script>

<template>
    <div :class="variant === 'large' ? 'bg-subheader w-full py-4' : 'bg-subheader px-6 py-4'">
        <v-row v-if="showBack" align="center" no-gutters>
            <v-col cols="auto" class="mr-4">
                <v-btn type="button" color="white" data-cy="btnVoltar" @click="goBack">
                    <v-icon>ms:arrow_left_alt</v-icon>

                    <span class="ml-2">
                        {{ backLabel }}
                    </span>
                </v-btn>
            </v-col>

            <v-col>
                <slot />
            </v-col>
        </v-row>

        <slot v-else />
    </div>
</template>
