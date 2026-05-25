<template>
  <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-950">Teachers</h2>
        <p class="text-sm text-slate-500">School heads and teacher assignments from ScanUp/EHRIS-linked records.</p>
      </div>
      <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
        <input
          v-model="search"
          type="search"
          placeholder="Search school, school head, admin"
          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 lg:w-96"
        />
        <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700" @click="loadRows">
          Refresh
        </button>
      </div>
    </div>

    <div v-if="error" class="m-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>

    <div class="max-h-[620px] overflow-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="sticky top-0 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-4 py-3 text-left">Department ID</th>
            <th class="px-4 py-3 text-left">School</th>
            <th class="px-4 py-3 text-left">School Head</th>
            <th class="px-4 py-3 text-left">Assigned Admin</th>
            <th class="px-4 py-3 text-right">Teachers</th>
            <th class="px-4 py-3 text-right">Learners</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="school in filteredRows" :key="school.deped_school_id" class="cursor-pointer hover:bg-blue-50/60" @click="selectedSchool = school">
            <td class="px-4 py-3 font-semibold text-slate-700">{{ school.deped_school_id }}</td>
            <td class="px-4 py-3 font-semibold text-slate-950">{{ school.school_name }}</td>
            <td class="px-4 py-3 text-slate-700">{{ school.school_head?.name || 'Not set' }}</td>
            <td class="px-4 py-3 text-slate-700">{{ school.assigned_admin?.name || 'Not set' }}</td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ school.teacher_count }}</td>
            <td class="px-4 py-3 text-right text-slate-700">{{ school.learner_count }}</td>
          </tr>
          <tr v-if="!loading && filteredRows.length === 0">
            <td colspan="6" class="px-4 py-10 text-center text-slate-500">No teacher records found.</td>
          </tr>
          <tr v-if="loading">
            <td colspan="6" class="px-4 py-10 text-center text-slate-500">Loading teachers...</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="selectedSchool" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" @click.self="selectedSchool = null">
      <div class="max-h-[88vh] w-full max-w-5xl overflow-hidden rounded-lg bg-white shadow-xl">
        <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-600">School Head</p>
            <h3 class="text-lg font-bold text-slate-950">{{ selectedSchool.school_head?.name || 'Not set' }}</h3>
            <p class="text-sm text-slate-500">{{ selectedSchool.deped_school_id }} - {{ selectedSchool.school_name }}</p>
          </div>
          <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold" @click="selectedSchool = null">Close</button>
        </header>

        <div class="max-h-[68vh] overflow-auto p-5">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-3 py-3 text-left">Teacher</th>
                <th class="px-3 py-3 text-left">HRID</th>
                <th class="px-3 py-3 text-left">Role/Class</th>
                <th class="px-3 py-3 text-left">Subject</th>
                <th class="px-3 py-3 text-right">Learners</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="teacher in selectedSchool.teacher_rows" :key="teacher.id || teacher.email">
                <td class="px-3 py-3"><p class="font-semibold text-slate-950">{{ teacher.name }}</p><p class="text-xs text-slate-500">{{ teacher.email }}</p></td>
                <td class="px-3 py-3 text-slate-700">{{ teacher.hrid || '-' }}</td>
                <td class="px-3 py-3 text-slate-700">{{ teacher.role }} <span class="text-xs text-slate-400">{{ teacher.grade_level }} {{ teacher.section }}</span></td>
                <td class="max-w-[320px] px-3 py-3 text-slate-700">
                  <p>{{ teacher.subjects || 'No subject assignment found' }}</p>
                  <p v-if="teacher.subjects_source === 'teacher_assignment'" class="mt-1 text-[11px] uppercase tracking-wide text-emerald-600">Teacher assignment</p>
                </td>
                <td class="px-3 py-3 text-right font-semibold text-slate-900">{{ teacher.learner_count }}</td>
              </tr>
              <tr v-if="!selectedSchool.teacher_rows?.length">
                <td colspan="5" class="px-3 py-10 text-center text-slate-500">No synced teachers for this school.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { fetchSystemAdminTeachers } from '../../services/systemAdminService';

const rows = ref([]);
const loading = ref(false);
const error = ref('');
const search = ref('');
const selectedSchool = ref(null);
let refreshTimer = null;

const filteredRows = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) return rows.value;

  return rows.value.filter((row) => [
    row.deped_school_id,
    row.school_name,
    row.school_head?.name,
    row.assigned_admin?.name,
  ].join(' ').toLowerCase().includes(term));
});

async function loadRows() {
  loading.value = true;
  error.value = '';
  try {
    const nextRows = await fetchSystemAdminTeachers();
    rows.value = nextRows;
    if (selectedSchool.value) {
      selectedSchool.value = nextRows.find((row) => row.deped_school_id === selectedSchool.value.deped_school_id) || selectedSchool.value;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load teacher records.';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadRows();
  refreshTimer = window.setInterval(loadRows, 30000);
});

onUnmounted(() => {
  if (refreshTimer) {
    window.clearInterval(refreshTimer);
  }
});
</script>
