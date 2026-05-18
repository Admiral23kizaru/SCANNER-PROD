<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4">
    <div class="max-h-[92vh] w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-2xl">
      <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-600">
            Read-only school dashboard
          </p>
          <h2 class="mt-1 text-xl font-bold text-slate-950">{{ dashboard?.school_name || 'Loading school' }}</h2>
          <p class="text-sm text-slate-500">Department ID: {{ dashboard?.deped_school_id || '...' }}</p>
        </div>
        <button
          type="button"
          class="rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          @click="$emit('close')"
        >
          Close
        </button>
      </header>

      <main class="max-h-[calc(92vh-86px)] overflow-auto p-6">
        <div v-if="loading" class="py-16 text-center text-slate-500">Loading dashboard...</div>
        <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
          {{ error }}
        </div>

        <div v-else-if="dashboard" class="space-y-6">
          <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article v-for="card in cards" :key="card.label" class="rounded-lg border border-slate-200 p-4">
              <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ card.label }}</p>
              <p class="mt-2 text-2xl font-bold text-slate-950">{{ card.value }}</p>
            </article>
          </section>

          <section class="rounded-lg border border-slate-200 p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="font-bold text-slate-950">Setup Health</h3>
                <p class="text-sm text-slate-500">Checks if this school is ready for live QR attendance use.</p>
              </div>
              <span class="w-fit rounded-full px-3 py-1 text-sm font-semibold" :class="healthClass(dashboard.health?.severity)">
                {{ dashboard.health?.label || 'Unknown' }}
              </span>
            </div>
          </section>

          <section class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 p-4">
              <h3 class="font-bold text-slate-950">Attendance by Grade Today</h3>
              <div class="mt-4 space-y-3">
                <div v-for="row in dashboard.attendance_by_grade" :key="row.grade || 'none'" class="flex items-center gap-3">
                  <span class="w-24 text-sm text-slate-600">{{ row.grade || 'No grade' }}</span>
                  <div class="h-3 flex-1 rounded-full bg-slate-100">
                    <div class="h-3 rounded-full bg-blue-600" :style="{ width: barWidth(row.count, maxGradeCount) }"></div>
                  </div>
                  <span class="w-10 text-right text-sm font-semibold">{{ row.count }}</span>
                </div>
                <p v-if="!dashboard.attendance_by_grade?.length" class="py-8 text-center text-sm text-slate-500">
                  No attendance recorded today.
                </p>
              </div>
            </div>

            <div class="rounded-lg border border-slate-200 p-4">
              <h3 class="font-bold text-slate-950">Last 14 Days Trend</h3>
              <div class="mt-4 space-y-3">
                <div v-for="row in dashboard.attendance_trends" :key="row.label" class="flex items-center gap-3">
                  <span class="w-24 text-sm text-slate-600">{{ row.label }}</span>
                  <div class="h-3 flex-1 rounded-full bg-slate-100">
                    <div class="h-3 rounded-full bg-emerald-600" :style="{ width: barWidth(row.count, maxTrendCount) }"></div>
                  </div>
                  <span class="w-10 text-right text-sm font-semibold">{{ row.count }}</span>
                </div>
                <p v-if="!dashboard.attendance_trends?.length" class="py-8 text-center text-sm text-slate-500">
                  No trend data yet.
                </p>
              </div>
            </div>
          </section>

          <section class="rounded-lg border border-slate-200 p-4">
            <h3 class="font-bold text-slate-950">Recent Activity</h3>
            <div class="mt-4 divide-y divide-slate-100">
              <div v-for="item in dashboard.recent_activity" :key="`${item.time}-${item.subtitle}`" class="py-3">
                <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
                <p class="text-sm text-slate-500">{{ item.subtitle }}</p>
                <p class="text-xs text-slate-400">{{ formatDate(item.time) }}</p>
              </div>
              <p v-if="!dashboard.recent_activity?.length" class="py-8 text-center text-sm text-slate-500">
                No recent scan activity yet.
              </p>
            </div>
          </section>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  dashboard: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
});

defineEmits(['close']);

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

function barWidth(value, max) {
  if (!max) return '0%';
  return `${Math.max(5, Math.round((Number(value || 0) / max) * 100))}%`;
}

function formatDate(value) {
  if (!value) return '';
  return new Date(value).toLocaleString();
}

function healthClass(severity) {
  if (severity === 'success') return 'bg-emerald-50 text-emerald-700';
  if (severity === 'danger') return 'bg-red-50 text-red-700';
  if (severity === 'warning') return 'bg-amber-50 text-amber-700';
  return 'bg-slate-100 text-slate-700';
}

const cards = computed(() => {
  const stats = props.dashboard?.stats || {};
  return [
    { label: 'Students', value: numberValue(stats.students) },
    { label: 'Teachers', value: numberValue(stats.teachers) },
    { label: 'Sections', value: numberValue(props.dashboard?.sections) },
    { label: 'Subjects', value: numberValue(props.dashboard?.subjects) },
    { label: 'Present Today', value: numberValue(stats.attendance_today) },
  ];
});

const maxGradeCount = computed(() => {
  return Math.max(0, ...(props.dashboard?.attendance_by_grade || []).map((row) => Number(row.count || 0)));
});

const maxTrendCount = computed(() => {
  return Math.max(0, ...(props.dashboard?.attendance_trends || []).map((row) => Number(row.count || 0)));
});
</script>
