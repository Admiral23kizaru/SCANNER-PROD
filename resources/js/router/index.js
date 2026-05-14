import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import Login from '../views/Login.vue';
import LandingPage from '../views/LandingPage.vue';
import GuardScanner from '../components/guard/GuardScanner.vue';
import TeacherDashboard from '../components/teacher/TeacherDashboard.vue';
import AdminLayout from '../components/layouts/AdminLayout.vue';

function normalizeRouterBase(raw) {
    const value = String(raw || '/').trim();
    if (!value || value === '/') {
        return '/';
    }

    const withLeadingSlash = value.startsWith('/') ? value : `/${value}`;
    return withLeadingSlash.endsWith('/') ? withLeadingSlash : `${withLeadingSlash}/`;
}

function resolveRouterBase() {
    if (import.meta.env.VITE_ROUTER_BASE) {
        return normalizeRouterBase(import.meta.env.VITE_ROUTER_BASE);
    }

    if (typeof window !== 'undefined' && window.location.pathname.startsWith('/qrid')) {
        return '/qrid/';
    }

    return normalizeRouterBase(import.meta.env.BASE_URL || '/');
}

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

function roleGuard(allowedRoles) {
    const allowed = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];

    return async (to, from, next) => {
        const user = await fetchCurrentUser();
        if (!user) {
            setStoredToken(null);
            next({ path: '/login', query: { redirect: to.fullPath } });
            return;
        }
        const roleName = user.role?.name || user.role_name;
        if (!allowed.includes(roleName)) {
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
        else if (roleName === 'Reporting Manager') {
            next({ path: '/admin' });
            return;
        }
        else if (roleName === 'Adviser') {
            next({ path: '/adviser' });
            return;
        }
        else if (roleName === 'Subject Teacher') {
            next({ path: '/subject-teacher' });
            return;
        }
        else next({ path: '/scanner' }); // Guard → scanner home
    } catch {
        next();
    }
}

const routes = [
    {
        path: '/',
        name: 'Home',
        component: LandingPage,
    },
    {
        path: '/login',
        name: 'Login',
        component: Login,
        beforeEnter: loginRedirectGuard,
    },
    {
        // Guard Scanner (public, no auth required)
        path: '/scanner',
        name: 'Scanner',
        component: GuardScanner,
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
        beforeEnter: roleGuard(['Admin', 'Reporting Manager']),
    },
    {
        path: '/reporting-manager',
        name: 'ReportingManager',
        component: {
            template: `
      <div style="
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        height:100vh;
        font-family:sans-serif;
        background:#f8f9fa;
      ">
        <h2 style="color:#333;margin-bottom:8px">
          Reporting Manager Dashboard
        </h2>
        <p style="color:#888;font-size:14px">
          Being prepared — coming soon
        </p>
      </div>
    `
        },
        beforeEnter: () => ({ path: '/admin' }),
    },
    {
        path: '/adviser',
        name: 'Adviser',
        component: {
            template: `
      <div style="
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        height:100vh;
        font-family:sans-serif;
        background:#f8f9fa;
      ">
        <h2 style="color:#333;margin-bottom:8px">
          Adviser Dashboard
        </h2>
        <p style="color:#888;font-size:14px">
          Being prepared — coming soon
        </p>
      </div>
    `
        },
        beforeEnter: roleGuard('Adviser'),
    },
    {
        path: '/subject-teacher',
        name: 'SubjectTeacher',
        component: {
            template: `
      <div style="
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        height:100vh;
        font-family:sans-serif;
        background:#f8f9fa;
      ">
        <h2 style="color:#333;margin-bottom:8px">
          Subject Teacher Dashboard
        </h2>
        <p style="color:#888;font-size:14px">
          Being prepared — coming soon
        </p>
      </div>
    `
        },
        beforeEnter: roleGuard('Subject Teacher'),
    },
    {
        // Keep old /guard URL working — redirect to scanner home
        path: '/guard',
        redirect: '/scanner',
    },
    {
        // Catch-all: any unknown route → scanner home
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(resolveRouterBase()),
    routes,
});

export default router;
export { getStoredToken, setStoredToken, fetchCurrentUser };
