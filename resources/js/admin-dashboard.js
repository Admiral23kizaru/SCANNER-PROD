import './bootstrap';
import { createApp } from 'vue';
import AdminLayout from './components/admin/AdminLayout.vue';

const app = createApp(AdminLayout);
app.mount('#app');
