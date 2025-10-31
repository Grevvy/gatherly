import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Read the CSRF token from the head meta tag or fallback to the hidden input
// used in this project's layout (`input#csrf-token` or input[name="_token"]).
let csrfToken = null;
const tokenMeta = document.head ? document.head.querySelector('meta[name="csrf-token"]') : null;
if (tokenMeta) {
    csrfToken = tokenMeta.content;
} else {
    const tokenInput = document.getElementById('csrf-token') || document.querySelector('input[name="_token"]');
    if (tokenInput) csrfToken = tokenInput.value;
}
if (!csrfToken) {
    console.debug('CSRF token not found in meta or hidden input. Private channel auth may fail.');
}
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

// Log broadcasting auth requests/responses to make private channel auth visible
if (window.axios) {
    window.axios.interceptors.request.use((config) => {
        try {
            if (config && config.url && config.url.includes('/broadcasting/auth')) {
                console.debug('[Broadcasting] auth request:', config.method, config.url, config.headers);
            }
        } catch (e) {}
        return config;
    }, (error) => Promise.reject(error));

    window.axios.interceptors.response.use((response) => {
        try {
            const config = response.config || {};
            if (config.url && config.url.includes('/broadcasting/auth')) {
                console.debug('[Broadcasting] auth response:', response.status, response.data);
            }
        } catch (e) {}
        return response;
    }, (error) => {
        try {
            const config = error.config || {};
            if (config.url && config.url.includes('/broadcasting/auth')) {
                console.error('[Broadcasting] auth error:', error.response ? error.response.status : null, error.response ? error.response.data : error.message);
            }
        } catch (e) {}
        return Promise.reject(error);
    });
}

import Echo from 'laravel-echo';
 
import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Enable pusher-js debug logging in development to help diagnose connection/auth issues
if (import.meta.env.DEV) {
    try {
        window.Pusher.logToConsole = true;
    } catch (e) {
        // ignore if not supported
    }
}
 
// Configure Echo to use the Reverb (Pusher-compatible) driver and include
// auth headers so Laravel can authorize private channels (used by MessageSent).
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Send CSRF token with the auth request for private channels. Echo uses
    // the `auth.headers` when making the POST to /broadcasting/auth.
    auth: {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
    },
});

// Debug WebSocket connection and auth
if (import.meta.env.DEV || window.location.hostname !== 'localhost') {
    console.log('[Echo] Configuration:', {
        key: import.meta.env.VITE_REVERB_APP_KEY,
        host: import.meta.env.VITE_REVERB_HOST,
        port: import.meta.env.VITE_REVERB_PORT,
        scheme: import.meta.env.VITE_REVERB_SCHEME,
        csrf: csrfToken ? 'present' : 'missing'
    });
}

export default window.Echo;