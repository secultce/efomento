<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    status: { type: String, default: null },
    codeTtlMinutes: { type: Number, required: true },
    codeLength: { type: Number, required: true },
    trustedDeviceDays: { type: Number, required: true },
    resendAvailableAt: { type: Number, default: null },
});

const form = useForm({ code: '', trust_device: false });
const resendForm = useForm({});
const cancelForm = useForm({});
const codeInput = ref(null);
const now = ref(Math.floor(Date.now() / 1000));
const busy = computed(() => form.processing || resendForm.processing || cancelForm.processing);
const resendSeconds = computed(() => Math.max(0, (props.resendAvailableAt ?? 0) - now.value));
const resendCountdown = computed(() => {
    const minutes = Math.floor(resendSeconds.value / 60);
    const seconds = String(resendSeconds.value % 60).padStart(2, '0');
    return `${minutes}:${seconds}`;
});

let timer;
onMounted(() => {
    timer = setInterval(() => {
        now.value = Math.floor(Date.now() / 1000);
    }, 1000);
});
onUnmounted(() => clearInterval(timer));

const focusCode = () => nextTick(() => codeInput.value?.focus());
const submit = () => {
    if (busy.value) return;

    form.post(route('two-factor.verify'), {
        onError: focusCode,
        onFinish: () => form.reset('code'),
    });
};
const resend = () => {
    if (busy.value || resendSeconds.value > 0) return;

    resendForm.post(route('two-factor.resend'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('code');
            form.clearErrors('code');
            focusCode();
        },
    });
};
const cancel = () => {
    if (!busy.value) cancelForm.post(route('two-factor.cancel'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirme seu acesso" />

        <div
            class="min-h-screen flex flex-col lg:flex-row items-center justify-center px-6 py-10 md:gap-4 lg:gap-[15em]"
        >
            <!-- left side -->
            <div
                class="flex-col items-center lg:items-start sm:text-center lg:text-left max-w-xl lg:mt-[-20em] hidden xl:block"
            >
                <img
                    src="/images/logos/ceara.png"
                    alt="Governo do Ceará"
                    class="w-[20em] md:w-[20em] mb-6 lg:block hidden"
                />
                <div class="hidden lg:block">
                    <p class="text-7xl font-bold text-[#2d353f]">e-fomento</p>

                    <p class="text-base md:text-[1.3em] text-left w-3/6 text-[#2d353f] mt-4 leading-relaxed">
                        o sistema para te ajudar a acompanhar os processos dos editais em andamento
                    </p>
                </div>
            </div>

            <v-card
                tag="section"
                aria-labelledby="challenge-title"
                class="w-full max-w-md px-6 py-8 sm:px-8"
                color="white"
                rounded="lg"
                elevation="2"
            >
                <div class="mb-5 flex items-center gap-3">
                    <v-avatar color="primary" variant="tonal" rounded="lg" size="48" aria-hidden="true">
                        <v-icon icon="mdi-email-lock-outline" size="26" />
                    </v-avatar>
                    <p class="text-sm font-bold text-primary">Verificação de acesso</p>
                </div>

                <h1 id="challenge-title" class="text-2xl font-bold leading-tight text-[#1A1A1A]">Confira seu email</h1>
                <p id="code-help" class="mt-3 text-sm leading-relaxed text-gray-600">
                    Enviamos um código de {{ codeLength }} dígitos ao email da sua conta. Informe o código para entrar
                    no e-fomento.
                </p>
                <p class="mt-3 flex items-center gap-2 text-sm text-gray-600">
                    <v-icon icon="mdi-clock-outline" size="18" aria-hidden="true" />
                    O código é válido por {{ codeTtlMinutes }} minutos.
                </p>

                <v-alert
                    v-if="status"
                    class="mt-5"
                    type="success"
                    variant="tonal"
                    density="compact"
                    rounded="lg"
                    role="status"
                >
                    {{ status }}
                </v-alert>

                <form class="mt-7" :aria-busy="busy" @submit.prevent="submit">
                    <label for="code" class="mb-2 block text-sm font-bold text-[#1A1A1A]">
                        Código de verificação
                    </label>
                    <v-text-field
                        id="code"
                        ref="codeInput"
                        v-model="form.code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        aria-describedby="code-help"
                        :pattern="`[0-9]{${codeLength}}`"
                        :maxlength="codeLength"
                        :placeholder="'0'.repeat(codeLength)"
                        :error-messages="form.errors.code"
                        :readonly="busy"
                        variant="outlined"
                        color="primary"
                        rounded="lg"
                        hide-details="auto"
                        class="verification-code"
                        required
                        autofocus
                    />

                    <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                        <v-checkbox
                            v-model="form.trust_device"
                            name="trust_device"
                            color="primary"
                            density="compact"
                            :disabled="busy"
                            :error-messages="form.errors.trust_device"
                            aria-describedby="trust-help"
                            hide-details="auto"
                        >
                            <template #label>
                                <span class="text-sm text-[#1A1A1A]">
                                    Confiar neste dispositivo por {{ trustedDeviceDays }} dias
                                </span>
                            </template>
                        </v-checkbox>
                        <p id="trust-help" class="pb-2 pl-10 text-xs leading-relaxed text-gray-600">
                            Use apenas em um dispositivo pessoal. Durante esse período, você ainda precisará da senha
                            para entrar.
                        </p>
                    </div>

                    <v-btn
                        type="submit"
                        block
                        color="secondary"
                        rounded="lg"
                        size="large"
                        elevation="0"
                        class="mt-6 font-weight-bold text-black"
                        :loading="form.processing"
                        :disabled="busy"
                    >
                        Confirmar e entrar
                        <v-icon icon="mdi-arrow-right" end aria-hidden="true" />
                    </v-btn>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">Não recebeu o código? Confira também a pasta de spam.</p>
                    <v-btn
                        variant="text"
                        color="primary"
                        class="mt-1"
                        :loading="resendForm.processing"
                        :disabled="resendSeconds > 0 || busy"
                        @click="resend"
                    >
                        {{ resendSeconds > 0 ? `Reenviar código em ${resendCountdown}` : 'Reenviar código' }}
                    </v-btn>
                    <p v-if="resendForm.errors.email" role="alert" class="mt-2 text-sm text-error">
                        {{ resendForm.errors.email }}
                    </p>
                </div>

                <v-divider class="my-5" />

                <v-btn
                    block
                    variant="text"
                    color="outlineSecondary"
                    rounded="lg"
                    prepend-icon="mdi-arrow-left"
                    :loading="cancelForm.processing"
                    :disabled="busy"
                    @click="cancel"
                >
                    Voltar ao login
                </v-btn>
            </v-card>
        </div>
    </GuestLayout>
</template>

<style scoped>
.verification-code :deep(input) {
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.3em;
}
</style>
