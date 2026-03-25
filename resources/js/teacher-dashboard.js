import './bootstrap';
import { createApp } from 'vue';
import TeacherDashboard from './components/teacher/TeacherDashboard.vue';

const app = createApp(TeacherDashboard);
app.mount('#app');
