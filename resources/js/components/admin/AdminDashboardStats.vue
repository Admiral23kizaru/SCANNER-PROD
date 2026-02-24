<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">Dashboard</h1>
    <p class="text-sm text-slate-500 mb-6">Overview of your ScanUp data</p>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
      <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl font-bold">
            {{ stats.total_students ?? '—' }}
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Total Students</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.total_students ?? '—' }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xl font-bold">
            {{ stats.total_teachers ?? '—' }}
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Total Teachers</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.total_teachers ?? '—' }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold">
            {{ stats.todays_attendance ?? '—' }}
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wide">Today's Attendance</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ stats.todays_attendance ?? '—' }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchStats } from '../../services/adminService';

const stats = ref({});

onMounted(async () => {
  try {
    stats.value = await fetchStats();
  } catch {
    stats.value = {};
  }
});
</script>
