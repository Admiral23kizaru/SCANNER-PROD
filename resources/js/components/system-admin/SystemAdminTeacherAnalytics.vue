<template>
  <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4">
      <div>
        <h2 class="text-lg font-bold text-slate-950">Teacher Analytics</h2>
        <p class="text-sm text-slate-500">Pie chart analysis of teacher distribution by school.</p>
      </div>
      <div class="grid gap-2 md:grid-cols-3">
        <input
          v-model="search"
          type="search"
          placeholder="Search school, school head, admin"
          class="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500"
        />
        <select v-model="filters.schoolType" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">All school types</option>
          <option v-for="type in schoolTypeOptions" :key="type" :value="type">{{ type }}</option>
        </select>
        <select v-model="filters.setup" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">All setup status</option>
          <option value="ready">Ready</option>
          <option value="not_created">School not created</option>
        </select>
      </div>
    </div>

    <div v-if="error" class="m-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>

    <div class="grid gap-5 p-4 xl:grid-cols-[520px_1fr]">
      <div class="rounded-lg border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-4">
        <div class="relative h-80">
          <Doughnut v-if="chartRows.length" :data="chartData" :options="chartOptions" />
          <div v-if="chartRows.length" class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <div class="text-center">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Teachers</p>
              <p class="text-3xl font-black text-slate-950">{{ chartTotal }}</p>
              <p class="text-xs text-slate-500">current filter</p>
            </div>
          </div>
          <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No teacher data found.</div>
        </div>
      </div>

      <div class="grid content-start gap-2 sm:grid-cols-2 xl:grid-cols-3">
        <div class="grid grid-cols-2 gap-2 sm:col-span-2 xl:col-span-3">
          <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Schools</p>
            <p class="mt-1 text-2xl font-black text-slate-950">{{ searchRows.length }}</p>
          </div>
          <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Learners</p>
            <p class="mt-1 text-2xl font-black text-slate-950">{{ learnerTotal }}</p>
          </div>
        </div>
        <button
          v-for="(row, index) in chartRows"
          :key="row.label"
          type="button"
          class="flex items-center justify-between rounded-lg border px-3 py-2 text-left text-sm transition hover:-translate-y-0.5 hover:shadow-sm"
          :class="selectedLabel === row.label ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white'"
          @click="row.filterable && (selectedLabel = selectedLabel === row.label ? '' : row.label)"
        >
          <span class="flex min-w-0 items-center gap-2">
            <span class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: colors[index % colors.length] }"></span>
            <span class="truncate font-semibold text-slate-700">{{ row.label }}</span>
          </span>
          <span class="font-black text-slate-950">{{ row.value }}</span>
        </button>
      </div>
    </div>

    <div class="border-t border-slate-200 p-4">
      <div class="rounded-lg border border-slate-200 bg-white p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Top Schools by Teacher Count</p>
        <div class="mt-3 h-80">
          <Bar v-if="barRows.length" :data="barData" :options="barOptions" />
          <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No teacher distribution found.</div>
        </div>
      </div>
    </div>

    <div class="border-t border-slate-200 p-4">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-4 py-3 text-left">School</th>
            <th class="px-4 py-3 text-right">Teachers</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in chartRows" :key="row.label" class="hover:bg-blue-50/60">
            <td class="px-4 py-3 font-semibold text-slate-800">{{ row.label }}</td>
            <td class="px-4 py-3 text-right font-semibold text-slate-950">{{ row.value }}</td>
          </tr>
          <tr v-if="!loading && chartRows.length === 0"><td colspan="2" class="px-4 py-10 text-center text-slate-500">No teacher data found.</td></tr>
          <tr v-if="loading"><td colspan="2" class="px-4 py-10 text-center text-slate-500">Loading teacher analytics...</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Chart as ChartJS, ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import { fetchSystemAdminTeachers } from '../../services/systemAdminService';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const rows = ref([]);
const loading = ref(false);
const error = ref('');
const search = ref('');
const selectedLabel = ref('');
const filters = ref({ schoolType: '', setup: '' });
const colors = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#ea580c', '#475569', '#65a30d', '#db2777'];

const searchRows = computed(() => {
  const term = search.value.trim().toLowerCase();
  return rows.value.filter((row) => {
    const matchesSearch = !term || [row.deped_school_id, row.school_name, row.school_head?.name, row.assigned_admin?.name].join(' ').toLowerCase().includes(term);
    const matchesType = !filters.value.schoolType || row.school_type === filters.value.schoolType;
    const matchesSetup = !filters.value.setup || row.setup_status === filters.value.setup;
    return matchesSearch && matchesType && matchesSetup;
  });
});

const schoolTypeOptions = computed(() => [...new Set(rows.value.map((row) => row.school_type).filter(Boolean))].sort());

const chartRows = computed(() => {
  const sorted = searchRows.value
    .map((row) => ({ label: chartLabel(row), value: Number(row.teacher_count || 0), filterable: true }))
    .filter((row) => row.value > 0)
    .sort((a, b) => b.value - a.value || a.label.localeCompare(b.label));

  const topRows = sorted.slice(0, 8);
  const otherTotal = sorted.slice(8).reduce((sum, row) => sum + row.value, 0);

  return otherTotal > 0 ? [...topRows, { label: 'Other schools', value: otherTotal, filterable: false }] : topRows;
});

const chartTotal = computed(() => chartRows.value.reduce((sum, row) => sum + row.value, 0));
const learnerTotal = computed(() => searchRows.value.reduce((sum, row) => sum + Number(row.learner_count || row.students || 0), 0));
const barRows = computed(() => chartRows.value.filter((row) => row.label !== 'Other schools').slice(0, 10));

const chartData = computed(() => ({
  labels: chartRows.value.map((row) => row.label),
  datasets: [{
    data: chartRows.value.map((row) => row.value),
    backgroundColor: chartRows.value.map((_, index) => colors[index % colors.length]),
    borderColor: '#ffffff',
    borderWidth: 4,
    hoverOffset: 16,
    spacing: 2,
    offset: chartRows.value.map((row) => (selectedLabel.value === row.label ? 10 : 0)),
  }],
}));

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '64%',
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label(context) {
          return ` ${context.label}: ${context.raw} teacher(s)`;
        },
      },
    },
  },
  onHover(event, elements) {
    if (event?.native?.target) {
      event.native.target.style.cursor = elements?.length ? 'pointer' : 'default';
    }
  },
  onClick(_event, elements) {
    const row = chartRows.value[elements?.[0]?.index];
    if (row?.filterable) selectedLabel.value = selectedLabel.value === row.label ? '' : row.label;
  },
}));

const barData = computed(() => ({
  labels: barRows.value.map((row) => row.label),
  datasets: [{
    label: 'Teachers',
    data: barRows.value.map((row) => row.value),
    backgroundColor: '#7c3aed',
    borderRadius: 8,
    maxBarThickness: 36,
  }],
}));

const barOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: (context) => ` ${context.raw} teacher(s)` } },
  },
  scales: {
    x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e2e8f0' } },
    y: { grid: { display: false } },
  },
}));

function chartLabel(row) {
  return row.school_name || row.deped_school_id || 'Unspecified school';
}

async function loadRows() {
  loading.value = true;
  error.value = '';
  try {
    rows.value = await fetchSystemAdminTeachers();
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load teacher analytics.';
  } finally {
    loading.value = false;
  }
}

onMounted(loadRows);
</script>
