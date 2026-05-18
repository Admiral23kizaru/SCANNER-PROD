<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4">
    <div class="max-h-[94vh] w-full max-w-7xl overflow-hidden rounded-xl bg-[#f4f7fb] shadow-2xl">
      <header class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-600">Read-only school admin view</p>
          <h2 class="mt-1 text-xl font-bold text-slate-950">{{ dashboard?.school_name || 'Loading school' }}</h2>
          <p class="text-sm text-slate-500">Department ID: {{ dashboard?.deped_school_id || '...' }}</p>
        </div>
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          @click="$emit('close')"
        >
          Close
        </button>
      </header>

      <main class="max-h-[calc(94vh-86px)] overflow-auto p-6">
        <div v-if="loading" class="py-16 text-center text-slate-500">Loading dashboard...</div>
        <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
          {{ error }}
        </div>

        <div v-else-if="dashboard" class="space-y-6">
          <section class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h3 class="text-2xl font-semibold text-slate-900">Dashboard Overview</h3>
              <p class="text-sm text-slate-500">Preview of the selected school's QR-ID management dashboard</p>
            </div>
          </section>

          <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <p class="text-sm font-medium uppercase tracking-wider text-slate-500">Total Teachers</p>
                  <p class="mt-2 text-3xl font-bold text-slate-900">{{ numberValue(stats.teachers) }}</p>
                  <p class="mt-2 text-xs text-slate-400">Registered teacher accounts</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 shadow-inner">
                  <span class="text-lg font-black">T</span>
                </div>
              </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <p class="text-sm font-medium uppercase tracking-wider text-slate-500">Total Students</p>
                  <p class="mt-2 text-3xl font-bold text-slate-900">{{ numberValue(stats.students) }}</p>
                  <p class="mt-2 text-xs text-slate-400">Encoded learners in system</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 shadow-inner">
                  <span class="text-lg font-black">S</span>
                </div>
              </div>
            </article>

            <article class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div class="mb-2 flex items-start justify-between gap-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Student Status Today</p>
                <p class="text-lg font-bold text-slate-900">
                  {{ activeStatusLabel }}: {{ numberValue(activeStatusCount) }}
                </p>
              </div>

              <div class="relative mt-4 flex h-14 items-center rounded-xl border border-slate-100 bg-slate-50 p-1">
                <div
                  class="absolute h-10 rounded-lg border border-slate-200/70 bg-white shadow-sm transition-all duration-300"
                  :style="sliderStyle"
                />
                <button
                  v-for="item in statusOptions"
                  :key="item.key"
                  type="button"
                  class="relative z-10 flex-1 text-center text-[10px] font-bold uppercase tracking-widest transition"
                  :class="activeStatusKey === item.key ? item.activeClass : 'text-slate-400 hover:text-slate-600'"
                  @click="activeStatusKey = item.key"
                >
                  {{ item.label }}
                </button>
              </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <p class="text-sm font-medium uppercase tracking-wider text-slate-500">Today's Attendance</p>
                  <p class="mt-2 text-3xl font-bold text-slate-900">{{ numberValue(stats.attendance_today) }}</p>
                  <p class="mt-2 text-xs text-slate-400">Scanned learners today</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 shadow-inner">
                  <span class="text-lg font-black">A</span>
                </div>
              </div>
            </article>
          </section>

          <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Attendance Trends</h3>
                <div class="flex flex-wrap gap-2 text-xs">
                  <select
                    v-model="trendFilter.group_by"
                    class="rounded-lg border-slate-200 text-xs focus:ring-slate-900"
                    @change="loadFilteredDashboard"
                  >
                    <option value="day">Daily</option>
                    <option value="week">Weekly</option>
                    <option value="month">Monthly</option>
                  </select>
                  <select
                    v-model="trendFilter.grade"
                    class="rounded-lg border-slate-200 text-xs focus:ring-slate-900"
                    @change="onGradeFilterChange"
                  >
                    <option value="">All grades</option>
                    <option v-for="grade in gradeOptions" :key="grade" :value="grade">{{ grade }}</option>
                  </select>
                  <select
                    v-model="trendFilter.section"
                    class="rounded-lg border-slate-200 text-xs focus:ring-slate-900"
                    @change="loadFilteredDashboard"
                  >
                    <option value="">All sections</option>
                    <option v-for="section in sectionOptions" :key="section" :value="section">{{ section }}</option>
                  </select>
                </div>
              </div>
              <div class="relative h-[300px]">
                <div v-if="trendLoading" class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/70 text-sm text-slate-500">
                  Updating filters...
                </div>
                <div v-if="dashboard.attendance_trends?.length" class="flex h-full items-end gap-2 border-b border-l border-slate-100 px-2 pb-2">
                  <div
                    v-for="row in dashboard.attendance_trends"
                    :key="row.label"
                    class="flex flex-1 flex-col items-center gap-2"
                  >
                    <div
                      class="w-full rounded-t-lg bg-indigo-500/80"
                      :style="{ height: barHeight(row.count, maxTrendCount) }"
                      :title="`${row.label}: ${row.count}`"
                    />
                    <span class="max-w-16 truncate text-[10px] text-slate-400">{{ shortLabel(row.label) }}</span>
                  </div>
                </div>
                <div v-else class="absolute inset-0 flex items-center justify-center text-sm italic text-slate-400">
                  No trend data available for selected filters.
                </div>
              </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 class="mb-6 text-lg font-semibold text-slate-900">Attendance by Grade (Today)</h3>
              <div class="relative h-[300px]">
                <div v-if="dashboard.attendance_by_grade?.length" class="space-y-4">
                  <div v-for="row in dashboard.attendance_by_grade" :key="row.grade || 'none'" class="flex items-center gap-3">
                    <span class="w-24 text-xs text-slate-500">{{ row.grade || 'No grade' }}</span>
                    <div class="h-4 flex-1 overflow-hidden rounded-full bg-slate-100">
                      <div class="h-full rounded-full bg-emerald-500" :style="{ width: barWidth(row.count, maxGradeCount) }" />
                    </div>
                    <span class="w-10 text-right text-sm font-semibold text-slate-900">{{ row.count }}</span>
                  </div>
                </div>
                <div v-else class="absolute inset-0 flex items-center justify-center text-sm italic text-slate-400">
                  No attendance recorded today yet.
                </div>
              </div>
            </article>
          </section>

          <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Recent Activity</h3>
                <span class="text-xs text-slate-400">Read-only</span>
              </div>
              <div class="mt-6 space-y-4">
                <div
                  v-for="(item, index) in dashboard.recent_activity"
                  :key="`${item.time}-${index}`"
                  class="flex items-center gap-4 rounded-xl p-3 hover:bg-slate-50"
                >
                  <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-100 bg-white text-slate-500 shadow-sm">
                    <span class="text-xs font-black">QR</span>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
                    <p class="truncate text-xs text-slate-500">{{ item.subtitle }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">{{ formatTime(item.time) }}</p>
                    <p class="text-[10px] text-slate-300">{{ formatDate(item.time) }}</p>
                  </div>
                </div>
                <p v-if="!dashboard.recent_activity?.length" class="py-12 text-center text-sm italic text-slate-400">
                  No recent activity detected.
                </p>
              </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 class="text-lg font-semibold text-slate-900">Quick Actions</h3>
              <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div
                  v-for="action in quickActions"
                  :key="action.title"
                  class="rounded-2xl border border-slate-100 bg-slate-50/70 p-5"
                >
                  <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl" :class="action.iconClass">
                      <span class="text-sm font-black">{{ action.short }}</span>
                    </div>
                    <div>
                      <p class="text-sm font-semibold text-slate-900">{{ action.title }}</p>
                      <p class="mt-1 text-xs text-slate-500">{{ action.subtitle }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          </section>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { fetchSystemAdminSchoolDashboard } from '../../services/systemAdminService';

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

const localDashboard = ref(null);
const trendLoading = ref(false);
const activeStatusKey = ref('male');
const trendFilter = reactive({
  group_by: 'day',
  grade: '',
  section: '',
});

const statusOptions = [
  { key: 'male', label: 'Male', activeClass: 'text-blue-600' },
  { key: 'female', label: 'Female', activeClass: 'text-pink-600' },
  { key: 'absent', label: 'Absent', activeClass: 'text-orange-600' },
];

const quickActions = [
  { title: 'Add Teacher', subtitle: 'Register educator account', short: 'T', iconClass: 'bg-blue-100 text-blue-600' },
  { title: 'Add Student', subtitle: 'Enroll a new learner', short: 'QR', iconClass: 'bg-indigo-100 text-indigo-600' },
  { title: 'Print Reports', subtitle: 'Export PDF summary', short: 'R', iconClass: 'bg-emerald-100 text-emerald-600' },
  { title: 'Master List', subtitle: 'Manage all students', short: 'M', iconClass: 'bg-amber-100 text-amber-600' },
];

const dashboard = computed(() => localDashboard.value || props.dashboard);
const stats = computed(() => dashboard.value?.stats || {});
const studentStatus = computed(() => dashboard.value?.student_status_today || {});

const gradeOptions = computed(() => dashboard.value?.filter_options?.grades || []);

const sectionOptions = computed(() => {
  const sections = dashboard.value?.filter_options?.sections || [];
  return [...new Set(
    sections
      .filter((row) => !trendFilter.grade || row.grade === trendFilter.grade)
      .map((row) => row.section)
      .filter(Boolean)
  )].sort((a, b) => String(a).localeCompare(String(b), undefined, { numeric: true }));
});

const activeStatusLabel = computed(() => {
  return statusOptions.find((item) => item.key === activeStatusKey.value)?.label || 'Status';
});

const activeStatusCount = computed(() => {
  return studentStatus.value[activeStatusKey.value] || 0;
});

// Matches the school admin status slider behavior while keeping this preview read-only.
const sliderStyle = computed(() => {
  const index = statusOptions.findIndex((item) => item.key === activeStatusKey.value);
  return {
    left: `${Math.max(0, index) * 33.33}%`,
    width: '33.33%',
  };
});

const maxGradeCount = computed(() => {
  return Math.max(0, ...(dashboard.value?.attendance_by_grade || []).map((row) => Number(row.count || 0)));
});

const maxTrendCount = computed(() => {
  return Math.max(0, ...(dashboard.value?.attendance_trends || []).map((row) => Number(row.count || 0)));
});

watch(
  () => props.dashboard,
  (value) => {
    localDashboard.value = value;
    trendFilter.group_by = 'day';
    trendFilter.grade = '';
    trendFilter.section = '';
  },
  { immediate: true }
);

function onGradeFilterChange() {
  if (trendFilter.section && !sectionOptions.value.includes(trendFilter.section)) {
    trendFilter.section = '';
  }

  loadFilteredDashboard();
}

async function loadFilteredDashboard() {
  const depedSchoolId = dashboard.value?.deped_school_id;
  if (!depedSchoolId) return;

  trendLoading.value = true;

  try {
    localDashboard.value = await fetchSystemAdminSchoolDashboard(depedSchoolId, {
      group_by: trendFilter.group_by,
      grade: trendFilter.grade || undefined,
      section: trendFilter.section || undefined,
    });
  } finally {
    trendLoading.value = false;
  }
}

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

function barWidth(value, max) {
  if (!max) return '0%';
  return `${Math.max(5, Math.round((Number(value || 0) / max) * 100))}%`;
}

function barHeight(value, max) {
  if (!max) return '4px';
  return `${Math.max(8, Math.round((Number(value || 0) / max) * 100))}%`;
}

function shortLabel(value) {
  if (!value) return '';
  return String(value).slice(5);
}

function formatDate(value) {
  if (!value) return '';
  return new Date(value).toLocaleDateString();
}

function formatTime(value) {
  if (!value) return '';
  return new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
</script>
