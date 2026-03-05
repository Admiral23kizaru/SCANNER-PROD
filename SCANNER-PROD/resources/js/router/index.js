import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import Login from '../views/Login.vue';
import TeacherDashboard from '../components/TeacherDashboard.vue';
import AdminLayout from '../components/admin/AdminLayout.vue';

function getStoredToken() {
    return localStorage.getItem('scan_up_token');
}

function setStoredToken(token) {
    if (token) {
        localStorage.setItem('scan_up_token', token);
    } else {
        localStorage.removeItem('scan_up_token');
    }
}

async function fetchCurrentUser() {
    const token = getStoredToken();
    if (!token) return null;
    try {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        const { data } = await axios.get('/api/user', { headers: { Accept: 'application/json' } });
        return data;
    } catch {
        setStoredToken(null);
        return null;
    }
}

function roleGuard(allowedRole) {
    return async (to, from, next) => {
        const user = await fetchCurrentUser();
        if (!user) {
            setStoredToken(null);
            next({ path: '/login', query: { redirect: to.fullPath } });
            return;
        }
        const roleName = user.role?.name || user.role_name;
        if (roleName !== allowedRole) {
            next({ path: '/login' });
            return;
        }
        next();
    };
}

async function loginRedirectGuard(to, from, next) {
    const token = getStoredToken();
    if (!token) {
        next();
        return;
    }
    try {
        const user = await fetchCurrentUser();
        if (!user) {
            next();
            return;
        }
        const roleName = user.role?.name || user.role_name;
        if (roleName === 'Admin') next({ path: '/admin' });
        else if (roleName === 'Teacher') next({ path: '/teacher' });
        else if (roleName === 'Guard') {
            window.location.href = '/guard';
            next(false);
            return;
        }
        else next();
    } catch {
        next();
    }
}

const routes = [
    {
        path: '/',
        redirect: '/login',
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
        beforeEnter: loginRedirectGuard,
    },
    {
        path: '/teacher',
        name: 'Teacher',
        component: TeacherDashboard,
        beforeEnter: roleGuard('Teacher'),
    },
    {
        path: '/admin',
        name: 'Admin',
        component: AdminLayout,
        beforeEnter: roleGuard('Admin'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
export { getStoredToken, setStoredToken, fetchCurrentUser };
