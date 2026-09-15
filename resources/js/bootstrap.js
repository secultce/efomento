import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.Pusher = Pusher;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: document.querySelector('meta[name="reverb-key"]').content,
    wsHost: window.location.hostname,
    wsPort: Number(window.location.port || 80),
    wssPort: Number(window.location.port || 443),
    forceTLS: window.location.protocol === 'https:',
    enabledTransports: ['ws', 'wss'],
});
