<template>
  <section class="grid gap-5 xl:grid-cols-3">
    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-bold text-slate-950">Setup Health Summary</h2>
      <div class="mt-5 space-y-3">
        <div v-for="row in healthRows" :key="row.label" class="space-y-1">
          <div class="flex justify-between text-sm">
            <span class="text-slate-600">{{ row.label }}</span>
            <span class="font-semibold text-slate-950">{{ row.count }}</span>
          </div>
          <div class="h-3 rounded-full bg-slate-100">
            <div class="h-3 rounded-full" :class="row.color" :style="{ width: percent(row.count) }"></div>
          </div>
        </div>
      </div>
    </article>

    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-bold text-slate-950">Top Schools by Scans Today</h2>
      <div class="mt-5 space-y-3">
        <div v-for="school in topSchools" :key="school.deped_school_id" class="space-y-1">
          <div class="flex justify-between gap-3 text-sm">
            <span class="truncate text-slate-600">{{ school.school_name }}</span>
            <span class="font-semibold text-slate-950">{{ school.attendance_today }}</span>
          </div>
          <div class="h-3 rounded-full bg-slate-100">
            <div class="h-3 rounded-full bg-blue-600" :style="{ width: scanPercent(school.attendance_today) }"></div>
          </div>
        </div>
        <p v-if="topSchools.length === 0" class="py-10 text-center text-sm text-slate-500">
          No scan activity today.
        </p>
      </div>
    </article>

    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-bold text-slate-950">School Type Coverage</h2>
      <div class="mt-5 grid grid-cols-3 gap-3">
        <div v-for="row in typeRows" :key="row.label" class="rounded-md bg-slate-50 p-4 text-center">
          <p class="text-2xl font-bold text-slate-950">{{ row.count }}</p>
          <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ row.label }}</p>
        </div>
      </div>
      <p class="mt-5 text-sm text-slate-500">
        Use Schools Monitor for search, filtering, and read-only school dashboard viewing.
      </p>
    </article>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  schools: {
    type: Array,
    default: () => [],
  },
});

const total = computed(() => Math.max(1, props.schools.length));

const healthRows = computed(() => [
  { label: 'Active today', count: countHealth('healthy'), color: 'bg-emerald-600' },
  { label: 'No scans today', count: countHealth('no_scans_today'), color: 'bg-slate-500' },
  { label: 'No students encoded', count: countHealth('no_students'), color: 'bg-amber-500' },
  { label: 'No teachers synced', count: countHealth('no_teachers'), color: 'bg-orange-500' },
  { label: 'School not created', count: countHealth('needs_setup'), color: 'bg-red-500' },
]);

const topSchools = computed(() => {
  return [...props.schools]
    .filter((school) => Number(school.attendance_today || 0) > 0)
    .sort((a, b) => Number(b.attendance_today || 0) - Number(a.attendance_today || 0))
    .slice(0, 8);
});

const maxScans = computed(() => {
  return Math.max(1, ...topSchools.value.map((school) => Number(school.attendance_today || 0)));
});

const typeRows = computed(() => ['Elementary', 'Secondary', 'Integrated'].map((label) => ({
  label,
  count: props.schools.filter((school) => school.school_type === label).length,
})));

function countHealth(status) {
  return props.schools.filter((school) => school.health?.status === status).length;
}

function percent(count) {
  return `${Math.round((Number(count || 0) / total.value) * 100)}%`;
}

function scanPercent(count) {
  return `${Math.max(5, Math.round((Number(count || 0) / maxScans.value) * 100))}%`;
}
</script>
