<template>
  <section class="space-y-5">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-lg font-bold text-slate-950">Learning Areas</h2>
          <p class="text-sm text-slate-500">
            Read-only list of learning areas created by school admins.
          </p>
        </div>

        <div class="grid gap-2 sm:grid-cols-3">
          <div class="rounded-lg bg-slate-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Learning Areas</p>
            <p class="text-2xl font-black text-slate-950">{{ subjects.length }}</p>
          </div>
          <div class="rounded-lg bg-slate-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Schools With Areas</p>
            <p class="text-2xl font-black text-slate-950">{{ schoolsWithSubjects }}</p>
          </div>
          <div class="rounded-lg bg-slate-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Enrollments</p>
            <p class="text-2xl font-black text-slate-950">{{ totalEnrollments }}</p>
          </div>
        </div>
      </div>

      <div class="mt-5 grid gap-3 lg:grid-cols-[1fr_220px]">
        <input
          v-model="search"
          type="search"
          class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          placeholder="Search learning area, school, or department ID"
        />
        <select
          v-model="schoolFilter"
          class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
        >
          <option value="">All schools</option>
          <option v-for="school in schoolOptions" :key="school.id" :value="school.id">
            {{ school.name }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
      {{ error }}
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-sm">
          <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
            <tr>
              <th class="border-b border-slate-200 px-4 py-3">Learning Area</th>
              <th class="border-b border-slate-200 px-4 py-3">School</th>
              <th class="border-b border-slate-200 px-4 py-3">Department ID</th>
              <th class="border-b border-slate-200 px-4 py-3 text-right">Students Enrolled</th>
              <th class="border-b border-slate-200 px-4 py-3">Created</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="subject in paginatedSubjects"
              :key="subject.id"
              class="border-b border-slate-100 hover:bg-blue-50/40"
            >
              <td class="px-4 py-3 font-bold text-slate-950">{{ subject.name }}</td>
              <td class="px-4 py-3 text-slate-700">{{ subject.school_name }}</td>
              <td class="px-4 py-3 font-mono text-xs text-slate-500">
                {{ subject.deped_school_id || '-' }}
              </td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900">
                {{ numberValue(subject.enrolled_students) }}
              </td>
              <td class="px-4 py-3 text-xs text-slate-500">{{ formatDate(subject.created_at) }}</td>
            </tr>

            <tr v-if="!loading && filteredSubjects.length === 0">
              <td colspan="5" class="px-4 py-12 text-center text-slate-400">
                No learning areas found for the selected filter.
              </td>
            </tr>

            <tr v-if="loading && subjects.length === 0">
              <td colspan="5" class="px-4 py-12 text-center text-slate-500">Loading learning areas...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="filteredSubjects.length > 0"
        class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <p class="text-sm text-slate-500">
          Showing {{ pageStart }}-{{ pageEnd }} of {{ filteredSubjects.length }} learning areas
        </p>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 disabled:opacity-50"
            :disabled="currentPage <= 1"
            @click="currentPage -= 1"
          >
            Previous
          </button>
          <span class="text-sm text-slate-500">{{ currentPage }} / {{ totalPages }}</span>
          <button
            type="button"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 disabled:opacity-50"
            :disabled="currentPage >= totalPages"
            @click="currentPage += 1"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { fetchSystemAdminSubjects } from '../../services/systemAdminService';

const subjects = ref([]);
const loading = ref(false);
const error = ref('');
const search = ref('');
const schoolFilter = ref('');
const currentPage = ref(1);
const perPage = 10;

const filteredSubjects = computed(() => {
  const needle = search.value.trim().toLowerCase();

  return subjects.value.filter((subject) => {
    const matchesSchool = !schoolFilter.value || String(subject.school_id || '') === schoolFilter.value;
    const haystack = [
      subject.name,
      subject.school_name,
      subject.deped_school_id,
    ].join(' ').toLowerCase();

    return matchesSchool && (!needle || haystack.includes(needle));
  });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredSubjects.value.length / perPage)));

const paginatedSubjects = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredSubjects.value.slice(start, start + perPage);
});

const pageStart = computed(() => {
  if (filteredSubjects.value.length === 0) return 0;
  return (currentPage.value - 1) * perPage + 1;
});

const pageEnd = computed(() => Math.min(currentPage.value * perPage, filteredSubjects.value.length));

const schoolOptions = computed(() => {
  const seen = new Map();
  subjects.value.forEach((subject) => {
    if (!subject.school_id || seen.has(subject.school_id)) return;
    seen.set(subject.school_id, {
      id: String(subject.school_id),
      name: subject.school_name,
    });
  });
  return Array.from(seen.values()).sort((a, b) => a.name.localeCompare(b.name));
});

const schoolsWithSubjects = computed(() => schoolOptions.value.length);

const totalEnrollments = computed(() => (
  subjects.value.reduce((sum, subject) => sum + Number(subject.enrolled_students || 0), 0)
));

watch([search, schoolFilter], () => {
  currentPage.value = 1;
});

watch(totalPages, (pages) => {
  if (currentPage.value > pages) currentPage.value = pages;
});

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

function formatDate(value) {
  if (!value) return '-';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '-' : date.toLocaleDateString();
}

async function loadSubjects() {
  loading.value = true;
  error.value = '';

  try {
    subjects.value = await fetchSystemAdminSubjects();
  } catch (err) {
    subjects.value = [];
    error.value = err.response?.data?.message || 'Unable to load learning areas.';
  } finally {
    loading.value = false;
  }
}

onMounted(loadSubjects);
</script>
