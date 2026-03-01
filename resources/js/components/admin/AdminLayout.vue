<template>
  <div class="min-h-screen bg-stone-50 text-stone-800 flex">
    <aside class="w-60 bg-blue-900 text-white shrink-0 flex flex-col shadow-lg border-r border-blue-800">
      <div class="p-5 border-b border-blue-800">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-blue-800 flex items-center justify-center text-lg font-bold shadow-sm">
            S
          </div>
          <div>
            <h1 class="text-lg font-semibold tracking-tight">ScanUp</h1>
            <p class="text-xs text-blue-200">Admin Panel</p>
          </div>
        </div>
      </div>
      <nav class="flex-1 p-3 space-y-1">
        <button
          type="button"
          class="w-full text-left rounded-lg px-4 py-3 text-sm font-medium transition flex items-center gap-3"
          :class="currentPage === 'dashboard' ? 'bg-blue-800 text-white shadow-sm' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'"
          @click="currentPage = 'dashboard'"
        >
          <span class="w-5 text-center opacity-90">📊</span>
          Dashboard
        </button>
        <button
          type="button"
          class="w-full text-left rounded-lg px-4 py-3 text-sm font-medium transition flex items-center gap-3"
          :class="currentPage === 'teachers' ? 'bg-blue-800 text-white shadow-sm' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'"
          @click="currentPage = 'teachers'"
        >
          <span class="w-5 text-center opacity-90">👤</span>
          Manage Teachers
        </button>
        <button
          type="button"
          class="w-full text-left rounded-lg px-4 py-3 text-sm font-medium transition flex items-center gap-3"
          :class="currentPage === 'students' ? 'bg-blue-800 text-white shadow-sm' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white'"
          @click="currentPage = 'students'"
        >
          <span class="w-5 text-center opacity-90">📋</span>
          Students
        </button>
      </nav>
      <div class="p-3 border-t border-blue-800">
        <button
          type="button"
          class="w-full rounded-lg px-4 py-3 text-sm font-medium text-blue-100 hover:bg-red-900/30 hover:text-red-200 transition flex items-center justify-center gap-2"
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

