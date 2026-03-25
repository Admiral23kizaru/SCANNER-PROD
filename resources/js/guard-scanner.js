import './bootstrap';
import { createApp } from 'vue';
import GuardScanner from './components/guard/GuardScanner.vue';

const app = createApp(GuardScanner);
app.mount('#app');
