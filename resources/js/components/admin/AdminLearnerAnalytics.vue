<template>
  <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4">
      <div>
        <h2 class="text-lg font-bold text-slate-950">Learner Analytics</h2>
        <p class="text-sm text-slate-500">School-scoped learner distribution by grade, section, gender, or adviser.</p>
      </div>
      <div class="grid gap-3 md:grid-cols-4">
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          Search
          <input v-model="search" type="search" placeholder="Learner, LRN, section" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-normal normal-case tracking-normal text-slate-900 outline-none focus:border-blue-500" />
        </label>
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          Grade
          <select v-model="filters.grade" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-normal normal-case tracking-normal text-slate-900">
            <option value="">All grades</option>
            <option v-for="grade in gradeOptions" :key="grade" :value="grade">{{ grade }}</option>
          </select>
        </label>
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          Section
          <select v-model="filters.section" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-normal normal-case tracking-normal text-slate-900">
            <option value="">All sections</option>
            <option v-for="section in sectionOptions" :key="section" :value="section">{{ section }}</option>
          </select>
        </label>
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          Analyze chart by
          <select v-model="groupBy" class="mt-1 w-full rounded-md border border-blue-300 bg-blue-50 px-3 py-2 text-sm font-semibold normal-case tracking-normal text-blue-950">
            <option value="grade">Grade level</option>
            <option value="section">Section</option>
            <option value="gender">Gender</option>
          </select>
        </label>
      </div>
    </div>

    <div v-if="error" class="m-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>

    <div class="grid gap-5 p-4 xl:grid-cols-[520px_1fr]">
      <div class="rounded-lg border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-4">
        <div class="mb-2 flex items-center justify-between gap-3">
          <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Distribution {{ groupLabel }}</p>
          <p class="rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">{{ chartRows.length }} group(s)</p>
        </div>
        <div class="relative h-80">
          <Doughnut v-if="chartRows.length" :data="chartData" :options="chartOptions" />
          <div v-if="chartRows.length" class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <div class="text-center">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Learners</p>
              <p class="text-3xl font-black text-slate-950">{{ chartTotal }}</p>
              <p class="text-xs text-slate-500">{{ groupLabel }}</p>
            </div>
          </div>
          <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No learner data found.</div>
        </div>
      </div>

      <div class="grid content-start gap-2 sm:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Learners</p>
          <p class="mt-1 text-2xl font-black text-slate-950">{{ filteredRows.length }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sections</p>
          <p class="mt-1 text-2xl font-black text-slate-950">{{ activeSections }}</p>
        </div>
        <button v-for="(row, index) in chartRows" :key="row.label" type="button" class="flex items-center justify-between rounded-lg border px-3 py-2 text-left text-sm transition hover:-translate-y-0.5 hover:shadow-sm" :class="selectedLabel === row.label ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white'" @click="selectedLabel = selectedLabel === row.label ? '' : row.label">
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
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Top Sections by Learners</p>
        <div class="mt-3 h-80">
          <Bar v-if="sectionChartRows.length" :data="sectionBarData" :options="barOptions" />
          <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No section distribution found.</div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Chart as ChartJS, ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import { fetchAdminStudents } from '../../services/adminService';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const rows = ref([]);
const loading = ref(false);
const error = ref('');
const search = ref('');
const selectedLabel = ref('');
const groupBy = ref('grade');
const filters = reactive({ grade: '', section: '' });
const colors = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#ea580c', '#475569', '#65a30d', '#db2777'];

const filteredRows = computed(() => {
  const term = search.value.trim().toLowerCase();
  return rows.value.filter((row) => {
    const matchesSearch = !term || [row.name, row.student_number, row.grade, row.section, row.gender].join(' ').toLowerCase().includes(term);
    const matchesGrade = !filters.grade || gradeLabel(row) === filters.grade;
    const matchesSection = !filters.section || (row.section || 'Unspecified') === filters.section;
    return matchesSearch && matchesGrade && matchesSection;
  });
});

const gradeOptions = computed(() => uniqueSorted(rows.value.map((row) => gradeLabel(row))));
const sectionOptions = computed(() => uniqueSorted(rows.value.map((row) => row.section || 'Unspecified')));
const activeSections = computed(() => new Set(filteredRows.value.map((row) => `${row.grade}:${row.section}`)).size);

const chartRows = computed(() => groupedRows(filteredRows.value, learnerGroupLabel).sort((a, b) => b.value - a.value || a.label.localeCompare(b.label)));
const chartTotal = computed(() => chartRows.value.reduce((sum, row) => sum + row.value, 0));
const groupLabel = computed(() => (({ grade: 'by grade', section: 'by section', gender: 'by gender' })[groupBy.value] || 'current filter'));
const sectionChartRows = computed(() => groupedRows(filteredRows.value, (row) => `${gradeLabel(row)} - ${row.section || 'Unspecified'}`).sort((a, b) => b.value - a.value).slice(0, 10));

const chartData = computed(() => ({
  labels: chartRows.value.map((row) => row.label),
  datasets: [{ data: chartRows.value.map((row) => row.value), backgroundColor: chartRows.value.map((_, index) => colors[index % colors.length]), borderColor: '#ffffff', borderWidth: 4, hoverOffset: 16, spacing: 2 }],
}));

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '64%',
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: (context) => ` ${context.label}: ${context.raw} learner(s), ${chartTotal.value ? Math.round((Number(context.raw) / chartTotal.value) * 100) : 0}%` } } },
}));

const sectionBarData = computed(() => ({
  labels: sectionChartRows.value.map((row) => row.label),
  datasets: [{ label: 'Learners', data: sectionChartRows.value.map((row) => row.value), backgroundColor: '#2563eb', borderRadius: 8, maxBarThickness: 36 }],
}));

const barOptions = computed(() => ({ responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } }));

function groupedRows(source, labelForRow) {
  const groups = source.reduce((acc, row) => {
    const label = labelForRow(row);
    acc[label] = (acc[label] || 0) + 1;
    return acc;
  }, {});
  return Object.entries(groups).map(([label, value]) => ({ label, value }));
}

function gradeLabel(row) {
  const grade = String(row.grade || '').trim();
  if (!grade) return 'Unspecified';
  return grade.toLowerCase().startsWith('grade') ? grade : `Grade ${grade}`;
}

function learnerGroupLabel(row) {
  if (groupBy.value === 'section') return row.section || 'Unspecified section';
  if (groupBy.value === 'gender') return row.gender || 'Unspecified gender';
  return gradeLabel(row);
}

function uniqueSorted(values) {
  return [...new Set(values.filter(Boolean))].sort((a, b) => String(a).localeCompare(String(b), undefined, { numeric: true }));
}

async function loadRows() {
  loading.value = true;
  error.value = '';
  try {
    const first = await fetchAdminStudents({ page: 1, per_page: 100 });
    const all = [...(first.data || [])];
    for (let page = 2; page <= Number(first.last_page || 1); page += 1) {
      const next = await fetchAdminStudents({ page, per_page: 100 });
      all.push(...(next.data || []));
    }
    rows.value = all;
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load learner analytics.';
  } finally {
    loading.value = false;
  }
}

onMounted(loadRows);
</script>
