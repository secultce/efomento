<script setup>
import { computed } from 'vue';

const props = defineProps({
    version: {
        type: String,
        default: '0.0.1',
    },
    supportUrl: {
        type: String,
        default: 'https://suporte.secult.ce.gov.br/',
    },
});

const currentYear = new Date().getFullYear();
const copyright = computed(
    () => `Todos os direitos reservados. Copyright \u00a9 ${currentYear}. Vers\u00e3o: ${props.version}`
);
</script>

<template>
    <footer class="w-full flex-none bg-[#008344] px-6 pb-3.5 pt-6 text-white" aria-label="Rodapé">
        <div
            class="mx-auto mb-6 grid w-full max-w-[1200px] grid-cols-1 items-center justify-items-center gap-6 text-center sm:grid-cols-2 sm:justify-items-start sm:text-left lg:grid-cols-4 xl:grid-cols-[217px_425px_155px_313px] xl:gap-[30px]"
        >
            <a
                :href="route('notices.index')"
                class="text-2xl mt-[-1em] font-bold leading-none text-white no-underline hover:underline"
                aria-label="Ir para o início"
            >
                e-fomento
            </a>

            <nav class="flex flex-col items-center gap-2.5 text-sm sm:items-start" aria-label="Links do rodapé">
                <a class="text-white no-underline hover:underline" href="/editais">Editais</a>
                <span>Indicadores</span>
            </nav>

            <img
                class="block h-[70px] w-[155px] max-w-full object-contain object-left"
                src="/images/logos/ceara-white.png"
                alt="Governo do Estado do Ceará - Secretaria da Cultura"
            />

            <p class="m-0 text-left text-[0.78rem] leading-[1.35] sm:text-left">
                Encontrou algum problema ou precisa de ajuda?<br />
                Acesse
                <a
                    :href="supportUrl"
                    class="font-bold text-white no-underline hover:underline"
                    target="_blank"
                    rel="noopener noreferrer"
                    >{{ supportUrl }}</a
                >
                <br />e abra um chamado
            </p>
        </div>

        <p class="m-0 text-center text-xs leading-[1.4]">{{ copyright }}</p>
    </footer>
</template>
