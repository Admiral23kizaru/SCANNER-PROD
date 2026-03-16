import './bootstrap';
import { createApp } from 'vue';
import GuardScanner from './components/GuardScanner.vue';

const token = localStorage.getItem('scan_up_token');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

const app = createApp(GuardScanner);
app.mount('#guard-app');
