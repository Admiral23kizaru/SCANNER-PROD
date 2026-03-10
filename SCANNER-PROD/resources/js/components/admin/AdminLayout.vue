<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 flex">
    <!-- Sidebar -->
    <aside
      class="w-64 shrink-0 flex flex-col border-r border-slate-200 bg-white"
    >
      <!-- Brand -->
      <div class="px-6 py-5 border-b border-slate-200">
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-lg bg-blue-800 flex items-center justify-center text-white text-lg font-bold shadow-sm shrink-0"
            aria-hidden="true"
          >
            A
          </div>
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

    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col bg-stone-50 text-stone-800">
      <!-- Top navbar styled similar to TeacherDashboard -->
      <header class="sticky top-0 z-10" style="background-color: #050517;">
        <div class="px-6 lg:px-10 py-4">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-xs font-medium tracking-[0.25em] text-stone-400 uppercase">
                {{ pageTitle }}
              </p>
              <p class="text-sm font-semibold text-white">
                {{ pageSubtitle }}
              </p>
            </div>
            <button
              type="button"
              class="rounded-lg px-4 py-2 text-sm font-medium text-white/90 hover:bg-white/10 border border-white/20 hover:border-white/40 transition flex items-center gap-2"
              @click="logout"
            >
              <LogOut class="h-4 w-4" />
              Log out
            </button>
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
import { LayoutDashboard, Users, GraduationCap, LogOut } from 'lucide-vue-next';
import { useLogout } from '../../composables/useLogout';
import AdminDashboardStats from './AdminDashboardStats.vue';
import AdminTeachersPage from './AdminTeachersPage.vue';
import AdminStudentsPage from './AdminStudentsPage.vue';

const currentPage = ref('dashboard');
const { logout } = useLogout();

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

</script>
