import axios from 'axios';
import { router } from '@inertiajs/vue3';

export function fetchNotifications(page = 1, read = null) {
    return axios.get('/notificacoes', {
        params: {
            page,
            ...(read !== null && {
                read,
            }),
        },
    });
}

export function markNotificationAsRead(notificationId, options = {}) {
    return router.patch(`/notificacoes/${notificationId}/ler`, {}, options);
}

export function markAllNotificationsAsRead(options = {}) {
    return router.patch('/notificacoes/ler-todas', {}, options);
}
