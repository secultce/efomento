
<template>
    <header class="bg-primary">
        <div class="flex h-16 items-center justify-between px-8">
            <div href="/" class="flex items-center space-x-2">
                <v-app-bar-title>
                    <a :href="route('editais.index')" class="text-white font-weight-bold text-h6 text-decoration-none">
                        e-fomento
                    </a>
                </v-app-bar-title>
            </div>
            <a
                href="https://suporte.secult.ce.gov.br/"
                class="cursor-pointer rounded-md no-underline px-4 py-2 text-sm font-medium text-black bg-white transition hover:bg-gray-500"
                target="_blank"
                rel="noopener noreferrer"
            >
                Solicitar acesso
            </a>
            
        </div>
    </header>
</template>