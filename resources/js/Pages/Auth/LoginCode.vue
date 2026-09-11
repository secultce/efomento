<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    status: { type: String, default: null },
    codeTtlMinutes: { type: Number, required: true },
    codeLength: { type: Number, required: true },
    trustedDeviceDays: { type: Number, required: true },
    resendAvailableAt: { type: Number, default: null },
});
const form = useForm({ code: '', trust_device: false });
const resendForm = useForm({});
const now = ref(Math.floor(Date.now() / 1000));
const resendSeconds = computed(() => Math.max(0, (props.resendAvailableAt ?? 0) - now.value));
let timer;
onMounted(() => {
    timer = setInterval(() => {
        now.value = Math.floor(Date.now() / 1000);
    }, 1000);
});
onUnmounted(() => clearInterval(timer));
const submit = () => form.post(route('two-factor.verify'), { onFinish: () => form.reset('code') });
const resend = () => {
    if (resendSeconds.value === 0) resendForm.post(route('two-factor.resend'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirme seu acesso" />
        <div class="w-full max-w-md bg-white p-8 shadow-md rounded-lg">
            <h1 class="text-lg font-bold">Confirme seu acesso</h1>
            <p class="mt-3 text-sm text-gray-600">
                Enviamos um código de {{ codeLength }} dígitos ao email da sua conta. Digite o código abaixo para
                entrar. Ele é válido por {{ codeTtlMinutes }} minutos.
            </p>
            <p v-if="status" role="status" class="mt-4 text-sm text-green-700">{{ status }}</p>
            <form class="mt-6" @submit.prevent="submit">
                <InputLabel for="code" value="Código de verificação" />
                <TextInput
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    :pattern="`[0-9]{${codeLength}}`"
                    :maxlength="codeLength"
                    class="mt-2 block w-full"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.code" />
                <label class="mt-5 flex items-start gap-3 text-sm text-gray-700">
                    <input
                        v-model="form.trust_device"
                        type="checkbox"
                        name="trust_device"
                        class="mt-1 rounded border-gray-300 text-green-700 focus:ring-green-700"
                    />
                    <span>Confiar neste dispositivo por {{ trustedDeviceDays }} dias</span>
                </label>
                <p class="mt-2 text-xs text-gray-600">Use esta opção apenas em um dispositivo pessoal.</p>
                <InputError class="mt-2" :message="form.errors.trust_device" />
                <PrimaryButton class="mt-6 w-full" :disabled="form.processing || resendForm.processing">
                    Confirmar e entrar
                </PrimaryButton>
            </form>
            <form class="mt-5" @submit.prevent="resend">
                <button
                    type="submit"
                    class="text-sm underline disabled:opacity-50"
                    :disabled="resendSeconds > 0 || resendForm.processing || form.processing"
                >
                    {{ resendSeconds > 0 ? `Reenviar código em ${resendSeconds}s` : 'Reenviar código' }}
                </button>
                <p class="mt-1 text-xs text-gray-600">Confira também a pasta de spam.</p>
                <InputError class="mt-2" :message="resendForm.errors.email" />
            </form>
            <Link :href="route('two-factor.cancel')" method="post" as="button" class="mt-5 text-sm underline">
                Voltar ao login
            </Link>
        </div>
    </GuestLayout>
</template>
