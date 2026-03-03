<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <header class="bg-primary">
        <div class="flex h-16 items-center justify-between px-8">
            <div href="/" class="flex items-center space-x-2">
                <span class="text-white font-semibold text-lg">
                    e-fomento
                </span>
            </div>
            <span
                @click="$inertia.visit(route('register'))"
                class="cursor-pointer rounded-md px-4 py-2 text-sm font-medium text-black bg-white transition hover:bg-gray-500"
            >
                Solicitar acesso
            </span>
            
        </div>
    </header>
</template>