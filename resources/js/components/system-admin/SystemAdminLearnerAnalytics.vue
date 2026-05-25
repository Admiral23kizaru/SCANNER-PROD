<template>
  <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4">
      <div>
        <h2 class="text-lg font-bold text-slate-950">Learner Analytics</h2>
        <p class="text-sm text-slate-500">Pie chart analysis of learner distribution by grade level.</p>
      </div>
      <div class="grid gap-2 md:grid-cols-4">
        <input
          v-model="search"
          type="search"
          placeholder="Search school, learner, adviser"
          class="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500"
        />
        <select v-model="filters.school" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">All schools</option>
          <option v-for="school in schoolOptions" :key="school" :value="school">{{ school }}</option>
        </select>
        <select v-model="filters.grade" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">All grades</option>
          <option v-for="grade in gradeOptions" :key="grade" :value="grade">{{ grade }}</option>
        </select>
        <select v-model="filters.section" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
          <option value="">All sections</option>
          <option v-for="section in sectionOptions" :key="section" :value="section">{{ section }}</option>
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
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Learners</p>
              <p class="text-3xl font-black text-slate-950">{{ chartTotal }}</p>
              <p class="text-xs text-slate-500">current filter</p>
            </div>
          </div>
          <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No learner data found.</div>
        </div>
      </div>

      <div class="grid content-start gap-2 sm:grid-cols-2 xl:grid-cols-3">
        <div class="grid grid-cols-2 gap-2 sm:col-span-2 xl:col-span-3">
          <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Schools</p>
            <p class="mt-1 text-2xl font-black text-slate-950">{{ activeSchools }}</p>
          </div>
          <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sections</p>
            <p class="mt-1 text-2xl font-black text-slate-950">{{ activeSections }}</p>
          </div>
        </div>
        <button
          v-for="(row, index) in chartRows"
          :key="row.label"
          type="button"
          class="flex items-center justify-between rounded-lg border px-3 py-2 text-left text-sm transition hover:-translate-y-0.5 hover:shadow-sm"
          :class="selectedLabel === row.label ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white'"
          @click="selectedLabel = selectedLabel === row.label ? '' : row.label"
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
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Top Schools by Learners</p>
        <div class="mt-3 h-80">
          <Bar v-if="schoolChartRows.length" :data="schoolBarData" :options="barOptions" />
          <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No school distribution found.</div>
        </div>
      </div>
    </div>

    <div class="border-t border-slate-200 p-4">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-4 py-3 text-left">Grade Level</th>
            <th class="px-4 py-3 text-right">Learners</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in chartRows" :key="row.label" class="hover:bg-blue-50/60">
            <td class="px-4 py-3 font-semibold text-slate-800">{{ row.label }}</td>
            <td class="px-4 py-3 text-right font-semibold text-slate-950">{{ row.value }}</td>
          </tr>
          <tr v-if="!loading && chartRows.length === 0"><td colspan="2" class="px-4 py-10 text-center text-slate-500">No learner data found.</td></tr>
          <tr v-if="loading"><td colspan="2" class="px-4 py-10 text-center text-slate-500">Loading learner analytics...</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Chart as ChartJS, ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import { fetchSystemAdminLearners } from '../../services/systemAdminService';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const rows = ref([]);
const loading = ref(false);
const error = ref('');
const search = ref('');
const selectedLabel = ref('');
const filters = ref({ school: '', grade: '', section: '' });
const colors = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#ea580c', '#475569', '#65a30d', '#db2777'];

const searchRows = computed(() => {
  const term = search.value.trim().toLowerCase();
  return rows.value.filter((row) => {
    const matchesSearch = !term || [row.name, row.student_number, row.school_name, row.deped_school_id, row.grade, row.section, row.adviser_name].join(' ').toLowerCase().includes(term);
    const matchesSchool = !filters.value.school || row.school_name === filters.value.school;
    const matchesGrade = !filters.value.grade || gradeLabel(row) === filters.value.grade;
    const matchesSection = !filters.value.section || (row.section || 'Unspecified') === filters.value.section;
    return matchesSearch && matchesSchool && matchesGrade && matchesSection;
  });
});

const schoolOptions = computed(() => uniqueSorted(rows.value.map((row) => row.school_name).filter(Boolean)));
const gradeOptions = computed(() => uniqueSorted(rows.value.map((row) => gradeLabel(row))));
const sectionOptions = computed(() => uniqueSorted(rows.value.map((row) => row.section || 'Unspecified')));

const chartRows = computed(() => {
  const groups = searchRows.value.reduce((acc, row) => {
    const key = gradeLabel(row);
    acc[key] = (acc[key] || 0) + 1;
    return acc;
  }, {});

  return Object.entries(groups)
    .map(([label, value]) => ({ label, value }))
    .sort((a, b) => b.value - a.value || a.label.localeCompare(b.label));
});

const chartTotal = computed(() => chartRows.value.reduce((sum, row) => sum + row.value, 0));
const activeSchools = computed(() => new Set(searchRows.value.map((row) => row.school_name).filter(Boolean)).size);
const activeSections = computed(() => new Set(searchRows.value.map((row) => `${row.school_id}:${row.grade}:${row.section}`).filter(Boolean)).size);

const schoolChartRows = computed(() => {
  const groups = searchRows.value.reduce((acc, row) => {
    const key = row.school_name || 'Unspecified school';
    acc[key] = (acc[key] || 0) + 1;
    return acc;
  }, {});

  return Object.entries(groups)
    .map(([label, value]) => ({ label, value }))
    .sort((a, b) => b.value - a.value || a.label.localeCompare(b.label))
    .slice(0, 10);
});

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
          return ` ${context.label}: ${context.raw} learner(s)`;
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
    if (row) selectedLabel.value = selectedLabel.value === row.label ? '' : row.label;
  },
}));

const schoolBarData = computed(() => ({
  labels: schoolChartRows.value.map((row) => row.label),
  datasets: [{
    label: 'Learners',
    data: schoolChartRows.value.map((row) => row.value),
    backgroundColor: '#2563eb',
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
    tooltip: { callbacks: { label: (context) => ` ${context.raw} learner(s)` } },
  },
  scales: {
    x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e2e8f0' } },
    y: { grid: { display: false } },
  },
}));

function gradeLabel(row) {
  const grade = String(row.grade || '').trim();
  if (!grade) return 'Unspecified';
  return grade.toLowerCase().startsWith('grade') ? grade : `Grade ${grade}`;
}

function uniqueSorted(values) {
  return [...new Set(values)].sort((a, b) => String(a).localeCompare(String(b), undefined, { numeric: true }));
}

async function loadRows() {
  loading.value = true;
  error.value = '';
  try {
    rows.value = await fetchSystemAdminLearners();
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load learner analytics.';
  } finally {
    loading.value = false;
  }
}

onMounted(loadRows);
</script>
