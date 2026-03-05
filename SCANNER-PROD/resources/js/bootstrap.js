import axios from 'axios';
window.axios = axios;

window.axios.defaults.baseURL = import.meta.env.VITE_APP_URL || 'http://127.0.0.1:8000';
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
