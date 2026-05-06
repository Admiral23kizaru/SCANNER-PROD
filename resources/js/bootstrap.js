import axios from 'axios';

window.axios = axios;

function stripTrailingSlash(s) {
    return s.replace(/\/+$/, '');
}

/** Vite omits Laravel-style ${VAR} — treat as unset. */
function resolvedViteAppUrl() {
    const v = import.meta.env.VITE_APP_URL;
    if (!v || typeof v !== 'string' || v.includes('${')) {
        return '';
    }
    return stripTrailingSlash(v.trim());
}

// Same-origin requests; interceptor fixes /api paths when the SPA lives under a subdirectory.
window.axios.defaults.baseURL = '';

window.axios.interceptors.request.use((config) => {
    const rawUrl = config.url;
    if (typeof rawUrl !== 'string' || !rawUrl.startsWith('/api')) {
        return config;
    }

    // npm run dev: Vite proxies /api → Laravel; keep path as /api/...
    if (import.meta.env.DEV) {
        return config;
    }

    const explicit = resolvedViteAppUrl();
    if (explicit) {
        try {
            const root = stripTrailingSlash(new URL(explicit).href);
            config.baseURL = root;
            config.url = rawUrl.startsWith('/') ? rawUrl.slice(1) : rawUrl;
            return config;
        } catch {
            // fall through — use BASE_URL prefix
        }
    }

    const rawBase = String(import.meta.env.BASE_URL || '/');
    const prefix = rawBase === '/' ? '' : stripTrailingSlash(rawBase);
    if (prefix && !rawUrl.startsWith(prefix)) {
        config.url = prefix + rawUrl;
    }
    return config;
});

window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
