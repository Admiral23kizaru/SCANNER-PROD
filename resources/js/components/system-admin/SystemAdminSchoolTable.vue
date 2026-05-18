<template>
  <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4">
      <div>
        <h2 class="text-lg font-bold text-slate-950">School Monitoring</h2>
        <p class="text-sm text-slate-500">All rows are filtered by EHRIS department ID / ScanUp school mapping.</p>
      </div>
      <div class="grid gap-2 lg:grid-cols-[1fr_180px_180px_180px]">
        <input
          v-model="search"
          type="search"
          placeholder="Search school, head, or admin"
          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500"
        />
        <select v-model="typeFilter" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">All school types</option>
          <option value="Elementary">Elementary</option>
          <option value="Secondary">Secondary</option>
          <option value="Integrated">Integrated</option>
        </select>
        <select v-model="healthFilter" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">All health states</option>
          <option value="healthy">Active today</option>
          <option value="no_scans_today">No scans today</option>
          <option value="no_students">No students encoded</option>
          <option value="no_teachers">No teachers synced</option>
          <option value="needs_setup">Needs setup</option>
        </select>
        <select v-model="setupFilter" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">All setup status</option>
          <option value="ready">Ready</option>
          <option value="not_created">Needs setup</option>
        </select>
      </div>
    </div>

    <div class="max-h-[560px] overflow-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="sticky top-0 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-4 py-3 text-left">Department ID</th>
            <th class="px-4 py-3 text-left">School</th>
            <th class="px-4 py-3 text-left">School Head</th>
            <th class="px-4 py-3 text-left">Assigned Admin</th>
            <th class="px-4 py-3 text-right">Students</th>
            <th class="px-4 py-3 text-right">Teachers</th>
            <th class="px-4 py-3 text-right">Scans Today</th>
            <th class="px-4 py-3 text-left">Health</th>
            <th class="px-4 py-3 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="school in paginatedSchools"
            :key="school.deped_school_id"
            class="hover:bg-blue-50/60"
            :class="{ 'bg-blue-50': selectedId === school.deped_school_id }"
            @click="$emit('select', school)"
          >
            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">
              {{ school.deped_school_id }}
            </td>
            <td class="min-w-[240px] px-4 py-3 font-semibold text-slate-950">
              {{ school.school_name }}
            </td>
            <td class="min-w-[180px] px-4 py-3 text-slate-700">
              {{ school.school_head?.name || 'Not set' }}
            </td>
            <td class="min-w-[180px] px-4 py-3 text-slate-700">
              {{ school.assigned_admin?.name || 'Not set' }}
            </td>
            <td class="px-4 py-3 text-right text-slate-700">{{ numberValue(school.students) }}</td>
            <td class="px-4 py-3 text-right text-slate-700">{{ numberValue(school.teachers) }}</td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">
              {{ numberValue(school.attendance_today) }}
            </td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="healthClass(school.health?.severity)"
              >
                {{ school.health?.label || 'Unknown' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="rounded-md border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                @click.stop="$emit('view-dashboard', school)"
              >
                View Dashboard
              </button>
            </td>
          </tr>
          <tr v-if="filteredSchools.length === 0">
            <td colspan="9" class="px-4 py-10 text-center text-slate-500">
              No schools match the current search.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm text-slate-600">
        Showing {{ pageStart }}-{{ pageEnd }} of {{ filteredSchools.length }} schools
      </p>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="currentPage <= 1"
          @click="currentPage -= 1"
        >
          Previous
        </button>
        <span class="text-sm text-slate-600">{{ currentPage }} / {{ totalPages }}</span>
        <button
          type="button"
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="currentPage >= totalPages"
          @click="currentPage += 1"
        >
          Next
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  schools: {
    type: Array,
    default: () => [],
  },
  selectedId: {
    type: String,
    default: '',
  },
});

defineEmits(['select', 'view-dashboard']);

const search = ref('');
const typeFilter = ref('');
const setupFilter = ref('');
const healthFilter = ref('');
const currentPage = ref(1);
const perPage = 10;

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

const filteredSchools = computed(() => {
  const term = search.value.trim().toLowerCase();

  return props.schools.filter((school) => {
    if (typeFilter.value && school.school_type !== typeFilter.value) return false;
    if (setupFilter.value && school.setup_status !== setupFilter.value) return false;
    if (healthFilter.value && school.health?.status !== healthFilter.value) return false;
    if (!term) return true;

    const searchable = [
      school.deped_school_id,
      school.school_name,
      school.school_head?.name,
      school.assigned_admin?.name,
    ].join(' ').toLowerCase();

    return searchable.includes(term);
  });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredSchools.value.length / perPage)));

const paginatedSchools = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredSchools.value.slice(start, start + perPage);
});

const pageStart = computed(() => {
  if (filteredSchools.value.length === 0) return 0;
  return (currentPage.value - 1) * perPage + 1;
});

const pageEnd = computed(() => {
  return Math.min(currentPage.value * perPage, filteredSchools.value.length);
});

watch([search, typeFilter, setupFilter, healthFilter], () => {
  currentPage.value = 1;
});

watch(totalPages, (pages) => {
  if (currentPage.value > pages) {
    currentPage.value = pages;
  }
});

function healthClass(severity) {
  if (severity === 'success') return 'bg-emerald-50 text-emerald-700';
  if (severity === 'danger') return 'bg-red-50 text-red-700';
  if (severity === 'warning') return 'bg-amber-50 text-amber-700';
  return 'bg-slate-100 text-slate-700';
}
</script>
