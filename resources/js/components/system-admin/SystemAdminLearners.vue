<template>
  <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-950">Learners</h2>
        <p class="text-sm text-slate-500">Live learner records from tbl_scanup_students across mapped schools.</p>
      </div>
      <input v-model="search" type="search" placeholder="Search learner, LRN, school, grade, section"
        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 lg:w-96" />
    </div>
    <div v-if="error" class="m-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>
    <div class="max-h-[620px] overflow-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="sticky top-0 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-4 py-3 text-left">Learner</th>
            <th class="px-4 py-3 text-left">School</th>
            <th class="px-4 py-3 text-left">Grade/Section</th>
            <th class="px-4 py-3 text-left">Adviser</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in filteredRows" :key="row.id" class="hover:bg-blue-50/60">
            <td class="px-4 py-3"><p class="font-semibold text-slate-950">{{ row.name }}</p><p class="text-xs text-slate-500">{{ row.student_number || 'No LRN' }}</p></td>
            <td class="px-4 py-3"><p class="text-slate-700">{{ row.school_name }}</p><p class="text-xs text-slate-500">{{ row.deped_school_id }}</p></td>
            <td class="px-4 py-3 text-slate-700">{{ row.grade || '-' }} / {{ row.section || '-' }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.adviser_name }}</td>
          </tr>
          <tr v-if="!loading && filteredRows.length === 0"><td colspan="4" class="px-4 py-10 text-center text-slate-500">No learners found.</td></tr>
          <tr v-if="loading"><td colspan="4" class="px-4 py-10 text-center text-slate-500">Loading learners...</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { fetchSystemAdminLearners } from '../../services/systemAdminService';

const rows = ref([]);
const loading = ref(false);
const error = ref('');
const search = ref('');

const filteredRows = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) return rows.value;
  return rows.value.filter((row) => [row.name, row.student_number, row.school_name, row.deped_school_id, row.grade, row.section, row.adviser_name].join(' ').toLowerCase().includes(term));
});

async function loadRows() {
  loading.value = true;
  error.value = '';
  try {
    rows.value = await fetchSystemAdminLearners();
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load learners.';
  } finally {
    loading.value = false;
  }
}

onMounted(loadRows);
</script>
