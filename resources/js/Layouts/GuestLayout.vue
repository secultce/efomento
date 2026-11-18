<script setup>
import GuestHeader from '@/Components/GuestAppHeader.vue';

defineProps({
    plain: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="min-h-screen relative" :class="plain ? 'bg-white' : 'bg-[#f5f5f5]'">
        <GuestHeader />

        <div class="flex min-h-[calc(100vh-64px)] relative">
            <!-- Triangle (hidden on small screens automatically) -->
            <div
                v-if="!plain"
                class="hidden md:block absolute bottom-0 right-0 w-full h-full bg-white [clip-path:polygon(100%_100%,35%_100%,100%_0)]"
            ></div>

            <main
                class="flex flex-1 relative z-10"
                :class="plain ? 'items-start' : 'items-center justify-center px-6 sm:px-10 md:px-16 lg:px-24'"
            >
                <slot />
            </main>
        </div>
    </div>
</template>
