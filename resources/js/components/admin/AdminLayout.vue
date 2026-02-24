<template>
  <div class="min-h-screen bg-slate-100 text-slate-800 flex">
    <aside class="w-60 bg-slate-800 text-white shrink-0 flex flex-col shadow-xl">
      <div class="p-5 border-b border-slate-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-indigo-500 flex items-center justify-center text-lg font-bold shadow">
            S
          </div>
          <div>
            <h1 class="text-lg font-semibold tracking-tight">ScanUp</h1>
            <p class="text-xs text-slate-400">Admin Panel</p>
          </div>
        </div>
      </div>
      <nav class="flex-1 p-3 space-y-1">
        <button
          type="button"
          class="w-full text-left rounded-lg px-4 py-3 text-sm font-medium transition flex items-center gap-3"
          :class="currentPage === 'dashboard' ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-700/60 hover:text-white'"
          @click="currentPage = 'dashboard'"
        >
          <span class="w-5 text-center opacity-80">📊</span>
          Dashboard
        </button>
        <button
          type="button"
          class="w-full text-left rounded-lg px-4 py-3 text-sm font-medium transition flex items-center gap-3"
          :class="currentPage === 'teachers' ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-700/60 hover:text-white'"
          @click="currentPage = 'teachers'"
        >
          <span class="w-5 text-center opacity-80">👤</span>
          Manage Teachers
        </button>
        <button
          type="button"
          class="w-full text-left rounded-lg px-4 py-3 text-sm font-medium transition flex items-center gap-3"
          :class="currentPage === 'students' ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-700/60 hover:text-white'"
          @click="currentPage = 'students'"
        >
          <span class="w-5 text-center opacity-80">📋</span>
          Students
        </button>
      </nav>
      <div class="p-3 border-t border-slate-700">
        <button
          type="button"
          class="w-full rounded-lg px-4 py-3 text-sm font-medium text-slate-300 hover:bg-red-600/20 hover:text-red-300 transition flex items-center justify-center gap-2"
          @click="logout"
        >
          <span aria-hidden="true">⎋</span>
          Log out
        </button>
      </div>
    </aside>
    <main class="flex-1 overflow-auto p-6 lg:p-8">
      <div class="max-w-6xl mx-auto">
        <AdminDashboardStats v-if="currentPage === 'dashboard'" />
        <AdminTeachersPage v-else-if="currentPage === 'teachers'" />
        <AdminStudentsPage v-else-if="currentPage === 'students'" />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { setStoredToken, getStoredToken } from '../../router';
import AdminDashboardStats from './AdminDashboardStats.vue';
import AdminTeachersPage from './AdminTeachersPage.vue';
import AdminStudentsPage from './AdminStudentsPage.vue';

const router = useRouter();
const currentPage = ref('dashboard');

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

