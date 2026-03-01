<template>
  <div>
    <h1 class="text-2xl font-bold text-stone-800 mb-2">Dashboard</h1>
    <p class="text-sm text-stone-500 mb-6">Overview of your ScanUp data</p>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
      <div class="bg-white rounded-lg border border-stone-200 p-6 shadow-sm hover:shadow-md transition">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center text-xl font-bold">
            {{ stats.total_students ?? '—' }}
          </div>
          <div>
            <p class="text-sm font-medium text-stone-500 uppercase tracking-wide">Total Students</p>
            <p class="text-2xl font-bold text-stone-800 mt-0.5">{{ stats.total_students ?? '—' }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg border border-stone-200 p-6 shadow-sm hover:shadow-md transition">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-stone-100 text-stone-600 flex items-center justify-center text-xl font-bold">
            {{ stats.total_teachers ?? '—' }}
          </div>
          <div>
            <p class="text-sm font-medium text-stone-500 uppercase tracking-wide">Total Teachers</p>
            <p class="text-2xl font-bold text-stone-800 mt-0.5">{{ stats.total_teachers ?? '—' }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg border border-stone-200 p-6 shadow-sm hover:shadow-md transition">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-green-100 text-green-800 flex items-center justify-center text-xl font-bold">
            {{ stats.todays_attendance ?? '—' }}
          </div>
          <div>
            <p class="text-sm font-medium text-stone-500 uppercase tracking-wide">Today's Attendance</p>
            <p class="text-2xl font-bold text-stone-800 mt-0.5">{{ stats.todays_attendance ?? '—' }}</p>
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
