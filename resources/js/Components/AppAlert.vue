<script setup>
import { useAlert } from '@/Composables/useAlert';

const { isOpen, title, message, buttonText, confirm } = useAlert();
</script>

<template>
    <v-dialog v-model="isOpen" max-width="20em" persistent>
        <v-card class="pr-4 pl-4 rounded-lg">
            <v-card-title class="!font-bold !pb-0 !text-base text-center">
                {{ title }}
            </v-card-title>

            <v-card-text class="!p-2 justify-normal hyphens-auto !text-xs">
                {{ message }}
            </v-card-text>

            <v-card-actions>
                <v-btn
                    class="w-full !shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF] rounded-lg text-xs"
                    @click="confirm"
                >
                    {{ buttonText }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
