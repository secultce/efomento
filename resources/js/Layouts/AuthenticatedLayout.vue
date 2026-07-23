<script setup>
import { router, usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import AppSubHeader from '@/Components/AppSubHeader.vue';
import AppSnackbar from '@/Components/AppSnackbar.vue';
import AppAlert from '@/Components/AppAlert.vue';
import AppFooter from '@/Components/AppFooter.vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth.user);
const notificationsCount = computed(() => page.props.allUnreadCount);
const appVersion = computed(() => page.props.appVersion);
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
            only: ['allUnreadCount'],
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
        <AppHeader :user="user" :notifications-count="notificationsCount" />
        <v-main>
            <AppSnackbar />
            <AppAlert />
            <AppSubHeader v-if="$slots.subheader">
                <slot name="subheader" />
            </AppSubHeader>

            <div class="bg-background">
                <slot />
            </div>
        </v-main>
        <AppFooter :version="appVersion" />
    </v-app>
</template>
