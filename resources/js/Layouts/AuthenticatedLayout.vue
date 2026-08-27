<script setup>
import { usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/AppHeader.vue';
import AppSubHeader from '@/Components/AppSubHeader.vue';
import AppSnackbar from '@/Components/AppSnackbar.vue';
import AppAlert from '@/Components/AppAlert.vue';
import AppFooter from '@/Components/AppFooter.vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth.user);
const notificationsCount = ref(page.props.allUnreadCount ?? 0);
const previousCount = ref(notificationsCount.value);

const audio = new Audio('/sounds/notification.mp3');

watch(notificationsCount, (newCount) => {
    if (newCount > previousCount.value) {
        audio.play().catch(() => {});
    }

    previousCount.value = newCount;
});

watch(
    () => page.props.allUnreadCount,
    (newCount) => {
        notificationsCount.value = newCount ?? 0;
    }
);

onMounted(() => {
    window.Echo.private(`App.Models.User.${user.value.id}`).notification(() => {
        notificationsCount.value += 1;
    });
});

onUnmounted(() => {
    window.Echo.leave(`App.Models.User.${user.value.id}`);
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
        <AppFooter />
    </v-app>
</template>
