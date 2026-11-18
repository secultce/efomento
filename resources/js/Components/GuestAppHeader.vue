<script setup>
import AppBrand from '@/Components/AppBrand.vue';
</script>

<template>
    <header class="bg-primary">
        <div class="flex h-16 items-center justify-between gap-2 px-4 sm:px-8">
            <a href="/" class="flex items-center no-underline">
                <AppBrand />
            </a>
            <a
                href="https://suporte.secult.ce.gov.br/"
                class="shrink-0 cursor-pointer rounded-md no-underline px-4 py-2 text-sm font-medium text-black bg-white transition hover:bg-gray-500"
                target="_blank"
                rel="noopener noreferrer"
            >
                Solicitar acesso
            </a>
        </div>
    </header>
</template>
