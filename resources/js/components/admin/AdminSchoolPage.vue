<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">School</h2>
        <p class="mt-1 text-sm text-slate-500">Grade and section list with learner count and adviser.</p>
      </div>
      <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700" @click="load">Refresh</button>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-4 py-3">Grade</th>
            <th class="px-4 py-3">Section</th>
            <th class="px-4 py-3 text-right">Learners</th>
            <th class="px-4 py-3">Teacher In Charge / Adviser</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in rows" :key="row.id">
            <td class="px-4 py-3 font-semibold text-slate-900">{{ row.grade_level }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.section }}</td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ row.learners_count }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.adviser }}</td>
          </tr>
          <tr v-if="!loading && rows.length === 0">
            <td colspan="4" class="px-4 py-8 text-center text-slate-500">No classes/sections found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { fetchAdminSchoolOverview } from '../../services/adminService';

const rows = ref([]);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    rows.value = await fetchAdminSchoolOverview();
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
