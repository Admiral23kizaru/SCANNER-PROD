<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-2xl md:text-3xl font-semibold text-slate-900">Dashboard</h1>
      <p class="text-sm text-slate-600 mt-1">
        Overview of your Ozamiz Schools QR-ID System data
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
      <!-- Total Students -->
      <div
        class="bg-white/95 rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow"
      >
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-semibold text-slate-500 tracking-[0.15em] uppercase">
              Total Students
            </p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">
              {{ stats.total_students ?? '—' }}
            </p>
          </div>
          <div
            class="w-12 h-12 rounded-xl bg-gradient-to-tr from-sky-500 to-emerald-400 text-slate-950 flex items-center justify-center text-xl font-bold shadow-md shadow-sky-900/30"
          >
            {{ stats.total_students ?? '—' }}
          </div>
        </div>
      </div>

      <!-- Total Teachers -->
      <div
        class="bg-white/95 rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow"
      >
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-semibold text-slate-500 tracking-[0.15em] uppercase">
              Total Teachers
            </p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">
              {{ stats.total_teachers ?? '—' }}
            </p>
          </div>
          <div
            class="w-12 h-12 rounded-xl bg-slate-900 text-sky-400 flex items-center justify-center text-xl font-bold border border-slate-700"
          >
            {{ stats.total_teachers ?? '—' }}
          </div>
        </div>
      </div>

      <!-- Today's Attendance -->
      <div
        class="bg-white/95 rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow"
      >
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-semibold text-slate-500 tracking-[0.15em] uppercase">
              Today's Attendance
            </p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">
              {{ stats.todays_attendance ?? '—' }}
            </p>
          </div>
          <div
            class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-xl font-bold border border-emerald-300/60"
          >
            {{ stats.todays_attendance ?? '—' }}
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
