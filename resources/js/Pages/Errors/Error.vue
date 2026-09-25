<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    status: {
        type: Number,
        required: true,
    },
});

const messages = {
    403: {
        title: 'Acesso negado',
        description:
            'Você não tem permissão para acessar página ou conteúdo. Volte para a página inicial e refaça o seu caminho.',
    },
    404: {
        title: 'Página não encontrada',
        description:
            'Um erro ocorreu! A página foi removida ou não existe. Tente voltar para a página inicial e refaça o seu caminho.',
    },
};

const error = computed(
    () =>
        messages[props.status] ?? {
            title: 'Ocorreu um erro',
            description: 'Não foi possível acessar esta página. Volte para a página inicial e tente novamente.',
        }
);
const page = usePage();
const authenticated = computed(() => Boolean(page.props.auth?.user));
const layout = computed(() => (authenticated.value ? AuthenticatedLayout : GuestLayout));
</script>

<template>
    <Head :title="`Erro ${status}`" />

    <component :is="layout" class="error-layout" v-bind="authenticated ? {} : { plain: true }">
        <section class="error-page" aria-labelledby="error-title">
            <h1 id="error-title">Erro {{ status }}</h1>

            <div class="error-card">
                <h2>{{ error.title }}</h2>
                <p>{{ error.description }}</p>
                <Link href="/" class="home-link">Voltar para a página inicial</Link>
            </div>
        </section>
    </component>
</template>

<style scoped>
.error-layout :deep(.v-main) {
    display: flex;
    flex-direction: column;
}

.error-layout :deep(.v-main > .bg-background) {
    display: flex;
    flex: 1;
}

.error-page {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    align-self: stretch;
    width: 100%;
    min-height: min(560px, calc(100svh - 64px));
    padding: 48px 24px;
    background: #fff;
    color: #2d353f;
    text-align: center;
}

h1 {
    margin: 0 0 40px;
    font-size: clamp(36px, 4vw, 48px);
    font-weight: 700;
    line-height: 1.2;
}

.error-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: 1000px;
    min-height: 320px;
    margin: 0 auto;
    padding: 48px 40px 32px;
    border: 1px solid #00d5c2;
    border-radius: 7px;
}

h2 {
    margin: 0;
    font-size: clamp(28px, 3.5vw, 40px);
    font-weight: 700;
    line-height: 1.25;
}

p {
    max-width: 100%;
    margin: 16px 0 32px;
    font-size: 16px;
    line-height: 1.5;
}

.home-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    max-width: 440px;
    min-height: 48px;
    margin-top: auto;
    padding: 12px 20px;
    border-radius: 7px;
    background: #fc0;
    color: #1a1a1a;
    font-size: 16px;
    font-weight: 700;
    line-height: 24px;
    text-decoration: none;
}

.home-link:hover {
    background: #f0c000;
}

.home-link:focus-visible {
    outline: 2px solid #2d353f;
    outline-offset: 3px;
}

@media (min-width: 1024px) {
    p {
        white-space: nowrap;
    }
}

@media (max-width: 480px) {
    .error-page {
        padding: 32px 16px;
    }

    h1 {
        margin-bottom: 28px;
    }

    .error-card {
        min-height: 300px;
        padding: 32px 20px 24px;
    }

    .home-link {
        font-size: 14px;
    }
}
</style>
