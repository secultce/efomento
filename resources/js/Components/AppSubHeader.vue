<script setup>
import { router } from '@inertiajs/vue3'

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
})

function goBack() {
    const referrer = document.referrer
    const cameFromLogin = referrer !== '' && new URL(referrer).pathname === '/login'

    if (!cameFromLogin && window.history.length > 1) {
        window.history.back()
    } else if (props.backRoute) {
        router.visit(props.backRoute)
    }
}
</script>
<template>
    <div :class="variant === 'large' ? 'bg-subheader !w-full py-4' : 'px-6 py-4 bg-subheader'">
        <v-row v-if="showBack" :style="showBack ? '' : 'visibility: hidden'">
            <v-col cols="auto" >
                <v-btn color="white" data-cy="btnVoltar" @click="goBack">
                    <v-icon>ms:arrow_left_alt</v-icon>
                    <span class="ml-2">{{ backLabel }}</span>
                </v-btn>
            </v-col>
            <v-col>
                <slot />
            </v-col>
        </v-row>
        <slot v-else />
    </div>
</template>
