<template>
  <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-950">School Monitoring</h2>
        <p class="text-sm text-slate-500">All rows are filtered by EHRIS department ID / ScanUp school mapping.</p>
      </div>
      <input
        v-model="search"
        type="search"
        placeholder="Search school, head, or admin"
        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 lg:max-w-xs"
      />
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
            <th class="px-4 py-3 text-left">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="school in filteredSchools"
            :key="school.deped_school_id"
            class="cursor-pointer hover:bg-blue-50/60"
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
                :class="school.setup_status === 'ready' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
              >
                {{ school.setup_status === 'ready' ? 'Ready' : 'Needs setup' }}
              </span>
            </td>
          </tr>
          <tr v-if="filteredSchools.length === 0">
            <td colspan="8" class="px-4 py-10 text-center text-slate-500">
              No schools match the current search.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';

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

defineEmits(['select']);

const search = ref('');

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

const filteredSchools = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) return props.schools;

  return props.schools.filter((school) => {
    const searchable = [
      school.deped_school_id,
      school.school_name,
      school.school_head?.name,
      school.assigned_admin?.name,
    ].join(' ').toLowerCase();

    return searchable.includes(term);
  });
});
</script>
