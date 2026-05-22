<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-slate-900">Attendance</h2>
      <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700" @click="load">Refresh</button>
    </div>
    <div class="mt-6 space-y-3">
      <div v-for="row in rows" :key="row.id" class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
        <div>
          <p class="font-semibold text-slate-900">{{ learnerName(row.student) }}</p>
          <p class="text-xs text-slate-500">{{ row.student?.grade || '-' }} - {{ row.student?.section || '-' }}</p>
        </div>
        <div class="text-right">
          <p class="text-sm font-semibold text-slate-900">{{ row.status }}</p>
          <p class="text-xs text-slate-500">{{ formatDate(row.scanned_at) }}</p>
        </div>
      </div>
      <p v-if="rows.length === 0" class="py-8 text-center text-sm text-slate-500">No attendance scans today.</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { fetchAdminAttendanceToday } from '../../services/adminService';

const rows = ref([]);

function learnerName(student) {
  if (!student) return 'Learner';
  return `${student.last_name || ''}, ${student.first_name || ''}`.replace(/^,\s*/, '').trim() || 'Learner';
}

function formatDate(value) {
  return value ? new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-';
}

async function load() {
  rows.value = await fetchAdminAttendanceToday();
}

onMounted(load);
</script>
