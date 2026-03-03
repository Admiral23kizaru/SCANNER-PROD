<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 flex">
    <!-- Sidebar -->
    <aside
      class="w-64 shrink-0 flex flex-col border-r border-slate-200 bg-white"
    >
      <!-- Brand -->
      <div class="px-6 py-5 border-b border-slate-200">
        <div class="flex items-center gap-3">
          <img
            :src="logoSrc"
            alt="Ozamiz Schools QR-ID System"
            class="h-10 w-auto rounded-md object-contain bg-white"
          />
          <div class="leading-tight">
            <h1 class="text-sm font-semibold tracking-tight text-slate-900">
              Ozamiz Schools QR-ID System
            </h1>
            <p class="text-[11px] text-slate-500 uppercase tracking-[0.18em]">
              Admin Panel
            </p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
        <button
          type="button"
          class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors border-l-2 border-transparent"
          :class="
            currentPage === 'dashboard'
              ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm border-l-blue-600'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          @click="currentPage = 'dashboard'"
        >
          <LayoutDashboard class="h-4 w-4" />
          <span>Dashboard</span>
        </button>

        <button
          type="button"
          class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors border-l-2 border-transparent"
          :class="
            currentPage === 'teachers'
              ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm border-l-blue-600'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          @click="currentPage = 'teachers'"
        >
          <Users class="h-4 w-4" />
          <span>Manage Teachers</span>
        </button>

        <button
          type="button"
          class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors border-l-2 border-transparent"
          :class="
            currentPage === 'students'
              ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm border-l-blue-600'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          @click="currentPage = 'students'"
        >
          <GraduationCap class="h-4 w-4" />
          <span>Students</span>
        </button>
      </nav>

      <!-- Logout -->
      <div class="px-3 pb-4 pt-2 border-t border-slate-200">
        <button
          type="button"
          class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 transition-colors"
          @click="logout"
        >
          <LogOut class="h-4 w-4" />
          <span>Log out</span>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col bg-slate-50">
      <!-- Top navbar -->
      <header
        class="h-16 flex items-center justify-between px-6 lg:px-10 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-sm"
      >
        <div>
          <p class="text-xs font-medium tracking-[0.25em] text-slate-400 uppercase">
            {{ pageTitle }}
          </p>
          <p class="text-sm font-semibold text-slate-900">
            {{ pageSubtitle }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <div class="hidden sm:flex flex-col items-end">
            <p class="text-sm font-medium text-slate-900">Admin</p>
            <p class="text-xs text-slate-500">System Administrator</p>
          </div>
          <div
            class="h-10 w-10 rounded-full bg-slate-200 flex items-center justify-center text-sm font-semibold text-slate-700 border border-slate-300"
          >
            AD
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-auto px-4 py-6 lg:px-10 lg:py-8">
        <div class="max-w-6xl mx-auto space-y-6">
          <AdminDashboardStats v-if="currentPage === 'dashboard'" />
          <AdminTeachersPage v-else-if="currentPage === 'teachers'" />
          <AdminStudentsPage v-else-if="currentPage === 'students'" />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { LayoutDashboard, Users, GraduationCap, LogOut } from 'lucide-vue-next';
import { setStoredToken, getStoredToken } from '../../router';
import AdminDashboardStats from './AdminDashboardStats.vue';
import AdminTeachersPage from './AdminTeachersPage.vue';
import AdminStudentsPage from './AdminStudentsPage.vue';

const router = useRouter();
const currentPage = ref('dashboard');
const logoSrc = '/logo/depedozamiz.png';

const pageTitle = computed(() => {
  if (currentPage.value === 'teachers') return 'TEACHERS';
  if (currentPage.value === 'students') return 'STUDENTS';
  return 'DASHBOARD';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'teachers') return 'Manage teacher accounts and profiles';
  if (currentPage.value === 'students') return 'Master list and records for students';
  return 'Overview of Ozamiz Schools QR-ID System activity';
});

async function logout() {
  const token = getStoredToken();
  if (token) {
    try {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      await axios.post('/api/logout');
    } catch (_) {}
    setStoredToken(null);
  }
  router.push('/login');
}
</script>
