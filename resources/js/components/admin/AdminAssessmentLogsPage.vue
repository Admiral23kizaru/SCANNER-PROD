<template>
  <section class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Semestral Assessment</h2>
      <form class="mt-4 grid gap-3 md:grid-cols-6" @submit.prevent="save">
        <input v-model="form.school_year" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="School year" />
        <input v-model="form.grade_level" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Grade" />
        <input v-model="form.section" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Section" />
        <input v-model.number="form.score" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Score" type="number" />
        <input v-model.number="form.total_items" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Total" type="number" />
        <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Save Log</button>
      </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-4 py-3">Learner</th>
            <th class="px-4 py-3">Subject</th>
            <th class="px-4 py-3">Grade/Section</th>
            <th class="px-4 py-3">Score</th>
            <th class="px-4 py-3">Least Mastered</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in rows" :key="row.id">
            <td class="px-4 py-3 font-semibold text-slate-900">{{ learnerName(row.student) }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.subject?.name || '-' }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.grade_level || '-' }} / {{ row.section || '-' }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.score }} / {{ row.total_items }}</td>
            <td class="px-4 py-3 text-slate-700">{{ (row.least_mastered_skills || []).join(', ') || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { createAdminAssessmentLog, fetchAdminAssessmentLogs } from '../../services/adminService';

const rows = ref([]);
const form = reactive({ school_year: '', grade_level: '', section: '', score: 0, total_items: 0 });

function learnerName(student) {
  if (!student) return '-';
  return `${student.last_name || ''}, ${student.first_name || ''}`.replace(/^,\s*/, '').trim() || '-';
}

async function load() {
  rows.value = await fetchAdminAssessmentLogs();
}

async function save() {
  await createAdminAssessmentLog({ ...form });
  form.score = 0;
  form.total_items = 0;
  await load();
}

onMounted(load);
</script>
