<template>
  <aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div v-if="!school" class="text-sm text-slate-500">
      Select a school to view its monitoring details.
    </div>

    <div v-else class="space-y-5">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
          Selected School
        </p>
        <h2 class="mt-2 text-xl font-bold text-slate-950">{{ school.school_name }}</h2>
        <p class="text-sm text-slate-500">Department ID: {{ school.deped_school_id }}</p>
      </div>

      <dl class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-md bg-slate-50 p-3">
          <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">School Head</dt>
          <dd class="mt-1 text-sm font-semibold text-slate-900">
            {{ school.school_head?.name || 'Not set' }}
          </dd>
          <dd class="text-xs text-slate-500">{{ school.school_head?.job_title || '' }}</dd>
        </div>
        <div class="rounded-md bg-slate-50 p-3">
          <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Assigned Admin</dt>
          <dd class="mt-1 text-sm font-semibold text-slate-900">
            {{ school.assigned_admin?.name || 'Not set' }}
          </dd>
          <dd class="text-xs text-slate-500">{{ school.assigned_admin?.job_title || '' }}</dd>
        </div>
      </dl>

      <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div v-for="stat in stats" :key="stat.label" class="rounded-md border border-slate-100 p-3">
          <dt class="text-xs text-slate-500">{{ stat.label }}</dt>
          <dd class="mt-1 text-2xl font-bold text-slate-950">{{ stat.value }}</dd>
        </div>
      </dl>

      <div class="rounded-md border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
        System Admin view is read-only. School records are still controlled by each school admin or principal account.
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  school: {
    type: Object,
    default: null,
  },
});

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

const stats = computed(() => {
  const raw = props.school?.stats || {};
  return [
    { label: 'Students', value: numberValue(raw.students) },
    { label: 'Teachers', value: numberValue(raw.teachers) },
    { label: 'Attendance Today', value: numberValue(raw.attendance_today) },
    { label: 'Late Today', value: numberValue(raw.late_today) },
  ];
});
</script>
