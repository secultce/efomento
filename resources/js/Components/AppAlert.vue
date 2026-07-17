<script setup>
import { useAlert } from '@/Composables/useAlert';

const { isOpen, title, message, buttonText, cancelText, confirm, cancel } = useAlert();
</script>

<template>
    <v-dialog v-model="isOpen" max-width="22rem" persistent>
        <v-card :class="['rounded-lg', cancelText ? '!px-5 !pt-4 !pb-4' : '!px-4 !pt-3 !pb-2']">
            <v-card-title
                :class="['!p-0 !font-bold text-center whitespace-normal', cancelText ? '!text-base' : '!text-sm']"
            >
                {{ title }}
            </v-card-title>

            <v-card-text
                :class="[
                    'text-center hyphens-auto',
                    cancelText ? '!px-0 !pt-3 !pb-4 !text-sm !leading-5' : '!px-2 !pt-2 !pb-3 !text-xs !leading-4',
                ]"
            >
                {{ message }}
            </v-card-text>

            <v-card-actions :class="['!p-0 flex', cancelText ? 'justify-end gap-4' : 'w-full gap-2']">
                <v-btn
                    v-if="cancelText"
                    variant="outlined"
                    color="#08783e"
                    size="small"
                    class="!min-w-20 !px-4 !shadow-none !font-normal rounded-lg !text-xs"
                    @click="cancel"
                >
                    {{ cancelText }}
                </v-btn>
                <v-btn
                    :size="cancelText ? 'small' : undefined"
                    :class="[
                        '!shadow-none !font-bold !bg-[#ffcc05FF] !text-[#2d353fFF]',
                        cancelText ? '!min-w-24 !px-4 rounded-lg !text-xs' : 'flex-1 !px-4 rounded-lg !text-xs',
                    ]"
                    @click="confirm"
                >
                    {{ buttonText }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
