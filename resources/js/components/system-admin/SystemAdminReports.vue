<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-950">Reports Center</h2>
        <p class="text-sm text-slate-500">
          Prepare demo-ready exports and quick summaries for division monitoring.
        </p>
      </div>
      <button
        type="button"
        class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"
        @click="$emit('download')"
      >
        Download Division Status CSV
      </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article v-for="card in summaryCards" :key="card.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ card.label }}</p>
        <p class="mt-3 text-3xl font-black text-slate-950">{{ numberValue(card.value) }}</p>
        <p class="mt-2 text-xs text-slate-500">{{ card.caption }}</p>
      </article>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
      <article
        v-for="report in reports"
        :key="report.title"
        class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em]" :class="report.kickerClass">
              {{ report.kicker }}
            </p>
            <h3 class="mt-2 text-lg font-bold text-slate-950">{{ report.title }}</h3>
            <p class="mt-2 text-sm text-slate-500">{{ report.description }}</p>
          </div>
          <span class="rounded-full px-3 py-1 text-xs font-bold" :class="report.badgeClass">{{ report.badge }}</span>
        </div>

        <dl class="mt-5 grid grid-cols-3 gap-3">
          <div v-for="metric in report.metrics" :key="metric.label" class="rounded-xl bg-slate-50 p-3">
            <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ metric.label }}</dt>
            <dd class="mt-1 text-xl font-black text-slate-950">{{ numberValue(metric.value) }}</dd>
          </div>
        </dl>

        <button
          type="button"
          class="mt-5 rounded-lg border px-4 py-2 text-sm font-semibold transition"
          :class="report.buttonClass"
          @click="handleReportAction(report)"
        >
          {{ report.actionLabel }}
        </button>
      </article>
    </div>

    <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 text-sm text-blue-900">
      <p class="font-bold">Demo guidance</p>
      <p class="mt-1">
        Use the CSV export for official status checking. Use Schools Monitor for per-school readiness,
        Live Scanners for active terminal health, and School Admins for principal/admin assignment review.
      </p>
    </article>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  overview: {
    type: Object,
    default: () => ({}),
  },
  schools: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['download', 'navigate']);

const readySchools = computed(() => props.schools.filter((school) => school.setup_status === 'ready').length);
const needsSetup = computed(() => props.schools.filter((school) => school.setup_status !== 'ready').length);
const noStudents = computed(() => props.schools.filter((school) => Number(school.students || 0) === 0).length);
const noTeachers = computed(() => props.schools.filter((school) => Number(school.teachers || 0) === 0).length);

const summaryCards = computed(() => [
  {
    label: 'Division Schools',
    value: props.overview.total_schools || props.schools.length,
    caption: 'Schools included in the System Admin list',
  },
  {
    label: 'Ready Schools',
    value: readySchools.value,
    caption: 'ScanUp school records already created',
  },
  {
    label: 'Students',
    value: props.overview.encoded_students,
    caption: 'Encoded learner records',
  },
  {
    label: 'Scans Today',
    value: props.overview.scans_today,
    caption: 'Attendance scans recorded today',
  },
]);

const reports = computed(() => [
  {
    kicker: 'Export',
    kickerClass: 'text-blue-600',
    title: 'Division School Status CSV',
    description: 'Download school setup, assigned head/admin, student count, teacher count, and scan status.',
    badge: 'Available',
    badgeClass: 'bg-emerald-50 text-emerald-700',
    action: 'download',
    actionLabel: 'Download CSV',
    buttonClass: 'border-blue-200 text-blue-700 hover:bg-blue-50',
    metrics: [
      { label: 'Schools', value: props.overview.total_schools || props.schools.length },
      { label: 'Ready', value: readySchools.value },
      { label: 'Needs Setup', value: needsSetup.value },
    ],
  },
  {
    kicker: 'Readiness',
    kickerClass: 'text-amber-600',
    title: 'School Setup Readiness',
    description: 'Review schools with missing ScanUp records, no students, or no synced teachers before demo day.',
    badge: 'Live View',
    badgeClass: 'bg-amber-50 text-amber-700',
    action: 'schools',
    actionLabel: 'Open Schools Monitor',
    buttonClass: 'border-amber-200 text-amber-700 hover:bg-amber-50',
    metrics: [
      { label: 'Needs Setup', value: needsSetup.value },
      { label: 'No Students', value: noStudents.value },
      { label: 'No Teachers', value: noTeachers.value },
    ],
  },
  {
    kicker: 'Scanner',
    kickerClass: 'text-emerald-600',
    title: 'Scanner Activity Summary',
    description: 'Check live terminals, latest heartbeat, latest scan, and scan totals without refreshing the page.',
    badge: 'AJAX',
    badgeClass: 'bg-emerald-50 text-emerald-700',
    action: 'scanners',
    actionLabel: 'Open Live Scanners',
    buttonClass: 'border-emerald-200 text-emerald-700 hover:bg-emerald-50',
    metrics: [
      { label: 'Scans', value: props.overview.scans_today },
      { label: 'Active', value: props.overview.schools_with_scans_today },
      { label: 'No Scans', value: props.overview.schools_without_scans_today },
    ],
  },
  {
    kicker: 'Directory',
    kickerClass: 'text-indigo-600',
    title: 'School Admin Directory',
    description: 'Confirm school heads and assigned admins from the trainer-provided assignment list.',
    badge: 'Review',
    badgeClass: 'bg-indigo-50 text-indigo-700',
    action: 'accounts',
    actionLabel: 'Open School Admins',
    buttonClass: 'border-indigo-200 text-indigo-700 hover:bg-indigo-50',
    metrics: [
      { label: 'Schools', value: props.schools.length },
      { label: 'Heads', value: props.schools.filter((school) => school.school_head?.name).length },
      { label: 'Admins', value: props.schools.filter((school) => school.assigned_admin?.name).length },
    ],
  },
]);

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

function handleReportAction(report) {
  if (report.action === 'download') {
    emit('download');
    return;
  }

  emit('navigate', report.action);
}
</script>
