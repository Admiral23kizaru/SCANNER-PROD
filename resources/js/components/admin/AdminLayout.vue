<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 flex">
    <!-- Sidebar -->
    <aside
      class="w-64 shrink-0 flex flex-col border-r border-slate-200 bg-white fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:static lg:transform-none"
      :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <!-- Brand -->
      <div class="px-6 py-3 border-b border-slate-700" style="background-color: #050517;">
        <div class="flex items-center gap-3">
          <img
            :src="logoSrc"
            alt="Ozamiz Schools QR-ID System"
            class="h-10 w-auto rounded-md object-contain bg-white"
          />
          <div class="leading-tight">
            <h1 class="text-sm font-semibold tracking-tight text-white">
              
            </h1>
            <p class="text-[17px] text-white uppercase tracking-[0.18em]">
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
          @click="currentPage = 'dashboard'; isSidebarOpen = false"
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
          @click="currentPage = 'teachers'; isSidebarOpen = false"
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
          @click="currentPage = 'students'; isSidebarOpen = false"
        >
          <GraduationCap class="h-4 w-4" />
          <span>Students</span>
        </button>

        <button
          type="button"
          class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors border-l-2 border-transparent"
          :class="
            currentPage === 'locator'
              ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm border-l-blue-600'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          @click="currentPage = 'locator'; isSidebarOpen = false"
        >
          <FileText class="h-4 w-4" />
          <span>Locator Slips</span>
        </button>
      </nav>
    </aside>

    <!-- Mobile sidebar overlay -->
    <div
      v-if="isSidebarOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="isSidebarOpen = false"
    ></div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col bg-slate-50">
      <!-- Top navbar -->
      <header class="sticky top-0 z-10" style="background-color: #050517;">
        <div class="h-16 flex items-center justify-between px-4 lg:px-10 border-b border-slate-700/80">
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="lg:hidden p-2 text-white hover:bg-white/10 rounded-lg transition"
              @click="isSidebarOpen = true"
            >
              <Menu class="h-5 w-5" />
            </button>
            <div>
              <p class="text-xs font-medium tracking-[0.25em] text-slate-400 uppercase">
                {{ pageTitle }}
              </p>
              <p class="text-sm font-semibold text-white">
                {{ pageSubtitle }}
              </p>
            </div>
          </div>
          <button
            type="button"
            class="grid grid-flow-col items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition-colors border border-white/20"
            @click="logout"
          >
            <LogOut class="h-4 w-4" />
            <span class="hidden sm:inline">Log out</span>
          </button>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-auto px-4 py-6 lg:px-10 lg:py-8">
        <div class="max-w-6xl mx-auto space-y-6">
          <AdminDashboardStats v-if="currentPage === 'dashboard'" />
          <AdminTeachersPage v-else-if="currentPage === 'teachers'" />
          <AdminStudentsPage v-else-if="currentPage === 'students'" />
          <AdminLocatorSlipsPage v-else-if="currentPage === 'locator'" />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { LayoutDashboard, Users, GraduationCap, LogOut, FileText, Menu } from 'lucide-vue-next';
import { setStoredToken, getStoredToken } from '../../router';
import AdminDashboardStats from './AdminDashboardStats.vue';
import AdminTeachersPage from './AdminTeachersPage.vue';
import AdminStudentsPage from './AdminStudentsPage.vue';
import AdminLocatorSlipsPage from './AdminLocatorSlipsPage.vue';

const router = useRouter();
const currentPage = ref('dashboard');
const isSidebarOpen = ref(false);
const logoSrc = '/logo/depedozamiz.png';

const pageTitle = computed(() => {
  if (currentPage.value === 'teachers') return 'TEACHERS';
  if (currentPage.value === 'students') return 'STUDENTS';
  if (currentPage.value === 'locator') return 'LOCATOR SLIPS';
  return 'DASHBOARD';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'teachers') return 'Manage teacher accounts and profiles';
  if (currentPage.value === 'students') return 'Master list and records for students';
  if (currentPage.value === 'locator') return 'Review and manage filed travel slips';
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
