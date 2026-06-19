<script setup>
import { computed, ref } from 'vue';
import SplitScreenTab from '@/Components/SplitScreenTab.vue';
</script>
<template>
    <split-screen-tab value="payment">
        <template #left-content>
            <div class="space-y-6">
                <div>
                    <p class="font-bold text-lg">Dados disponíveis para consulta</p>
                    <p class="text-sm text-gray-600">Utilize os filtros abaixo para navegar entre os dados</p>
                </div>
            </div>
        </template>
        <template #right-content>
            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-bold text-lg">Campos para você inserir ou editar dados</p>
                            <p class="font-bold text-md mt-2 text-black">Links auxiliares</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </split-screen-tab>
</template>
