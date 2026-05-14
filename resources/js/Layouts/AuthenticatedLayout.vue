<script setup>
import { router, usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import AppSubHeader from '@/Components/AppSubHeader.vue';
import AppSnackbar from '@/Components/AppSnackbar.vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth.user);
const notifications = computed(() => page.props.notifications);
const notificationsCount = computed(() => page.props.allUnreadCount);
const previousCount = ref(notificationsCount.value);

const audio = new Audio('/sounds/notification.mp3');

watch(notificationsCount, (newCount) => {
    if (newCount > previousCount.value) {
        audio.play();
    }

    previousCount.value = newCount;
});

let interval = null;

onMounted(() => {
    interval = setInterval(() => {
        router.reload({
            only: ['notifications', 'allUnreadCount'],
            preserveState: true,
            preserveScroll: true,
        });
    }, 5000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <v-app theme="efomento">
        <AppHeader :user="user" :notifications="notifications" :notifications-count="notificationsCount" />
        <v-main>
            <AppSnackbar />
            <AppSubHeader v-if="$slots.subheader">
                <slot name="subheader" />
            </AppSubHeader>

            <div class="bg-background">
                <slot />
            </div>
        </v-main>
    </v-app>
</template>
