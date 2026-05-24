<template>
  <div class="space-y-5">
    <section class="grid grid-cols-1 gap-3 md:grid-cols-4">
      <div v-for="card in cards" :key="card.label" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-2 text-2xl font-black text-slate-950">{{ numberValue(card.value) }}</p>
      </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="flex flex-col gap-3 border-b border-slate-200 p-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <h2 class="text-lg font-bold text-slate-950">Attendance Today</h2>
          <p class="text-sm text-slate-500">Live attendance summaries from tbl_scanup_attendance.</p>
        </div>
        <input
          v-model="search"
          type="search"
          placeholder="Search school or learner"
          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 lg:w-96"
        />
      </div>

      <div v-if="error" class="m-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>

      <div class="grid gap-5 p-4 lg:grid-cols-2">
        <div class="overflow-auto rounded-lg border border-slate-200">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">School</th>
                <th class="px-4 py-3 text-right">Scans</th>
                <th class="px-4 py-3 text-right">Learners</th>
                <th class="px-4 py-3 text-right">Late</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="row in filteredSummary" :key="row.school_id" class="hover:bg-blue-50/60">
                <td class="px-4 py-3">
                  <p class="font-semibold text-slate-950">{{ row.school_name }}</p>
                  <p class="text-xs text-slate-500">{{ row.deped_school_id }}</p>
                </td>
                <td class="px-4 py-3 text-right font-semibold">{{ numberValue(row.scans_today) }}</td>
                <td class="px-4 py-3 text-right">{{ numberValue(row.learners_scanned) }}</td>
                <td class="px-4 py-3 text-right">{{ numberValue(row.late_count) }}</td>
              </tr>
              <tr v-if="!loading && filteredSummary.length === 0">
                <td colspan="4" class="px-4 py-10 text-center text-slate-500">No attendance scans today.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-auto rounded-lg border border-slate-200">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">Recent Scan</th>
                <th class="px-4 py-3 text-left">School</th>
                <th class="px-4 py-3 text-left">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="row in filteredRecent" :key="row.id" class="hover:bg-blue-50/60">
                <td class="px-4 py-3">
                  <p class="font-semibold text-slate-950">{{ row.learner_name }}</p>
                  <p class="text-xs text-slate-500">{{ row.grade }} {{ row.section }} · {{ formatDate(row.scanned_at) }}</p>
                </td>
                <td class="px-4 py-3 text-slate-700">{{ row.school_name }}</td>
                <td class="px-4 py-3">
                  <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="row.status === 'late' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'">
                    {{ row.status || 'on_time' }}
                  </span>
                </td>
              </tr>
              <tr v-if="!loading && filteredRecent.length === 0">
                <td colspan="3" class="px-4 py-10 text-center text-slate-500">No recent scans found.</td>
              </tr>
              <tr v-if="loading">
                <td colspan="3" class="px-4 py-10 text-center text-slate-500">Loading attendance...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { fetchSystemAdminAttendance } from '../../services/systemAdminService';

const summary = ref([]);
const recent = ref([]);
const loading = ref(false);
const error = ref('');
const search = ref('');

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleString();
}

const cards = computed(() => [
  { label: 'Schools with scans', value: summary.value.length },
  { label: 'Total scans today', value: summary.value.reduce((sum, row) => sum + Number(row.scans_today || 0), 0) },
  { label: 'Learners scanned', value: summary.value.reduce((sum, row) => sum + Number(row.learners_scanned || 0), 0) },
  { label: 'Late scans', value: summary.value.reduce((sum, row) => sum + Number(row.late_count || 0), 0) },
]);

const filteredSummary = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) return summary.value;
  return summary.value.filter((row) => [row.school_name, row.deped_school_id].join(' ').toLowerCase().includes(term));
});

const filteredRecent = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) return recent.value;
  return recent.value.filter((row) => [
    row.learner_name,
    row.student_number,
    row.school_name,
    row.deped_school_id,
    row.grade,
    row.section,
  ].join(' ').toLowerCase().includes(term));
});

async function loadAttendance() {
  loading.value = true;
  error.value = '';

  try {
    const data = await fetchSystemAdminAttendance();
    summary.value = data.summary || [];
    recent.value = data.recent || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load attendance records.';
  } finally {
    loading.value = false;
  }
}

onMounted(loadAttendance);
</script>
