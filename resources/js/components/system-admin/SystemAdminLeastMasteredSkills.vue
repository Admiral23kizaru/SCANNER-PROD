
<template>
  <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-4">
      <h2 class="text-lg font-bold text-slate-950">Least Mastered Skills</h2>
      <p class="text-sm text-slate-500">Pie chart from saved Learning Assessment analysis files.</p>
      <div class="mt-3 grid gap-2 md:grid-cols-5">
        <select v-model="filters.school_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm" @change="loadData"><option value="">All schools</option><option v-for="s in options.schools" :key="s.id" :value="s.id">{{ s.name }}</option></select>
        <select v-model="filters.school_year" class="rounded-md border border-slate-300 px-3 py-2 text-sm" @change="loadData"><option value="">All SY</option><option v-for="sy in options.school_years" :key="sy" :value="sy">{{ sy }}</option></select>
        <select v-model="filters.subject_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm" @change="loadData"><option value="">All subjects</option><option v-for="s in options.subjects" :key="s.id" :value="s.id">{{ s.name }}</option></select>
        <select v-model="filters.grade_level" class="rounded-md border border-slate-300 px-3 py-2 text-sm" @change="loadData"><option value="">All grades</option><option v-for="g in options.grades" :key="g" :value="g">{{ g }}</option></select>
        <select v-model="filters.section" class="rounded-md border border-slate-300 px-3 py-2 text-sm" @change="loadData"><option value="">All sections</option><option v-for="s in options.sections" :key="s" :value="s">{{ s }}</option></select>
      </div>
    </div>

    <div v-if="error" class="m-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>

    <div class="grid gap-5 p-4 xl:grid-cols-[520px_1fr]">
      <div class="rounded-lg border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-4">
        <div class="relative h-80">
          <Doughnut v-if="chartRows.length" :data="chartData" :options="chartOptions" />
          <div v-if="chartRows.length" class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <div class="text-center">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Total</p>
              <p class="text-3xl font-black text-slate-950">{{ chartTotal }}</p>
              <p class="text-xs text-slate-500">least mastered hits</p>
            </div>
          </div>
          <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No Learning Assessment analysis yet.</div>
        </div>

        <div v-if="chartRows.length" class="mt-4 grid gap-2 sm:grid-cols-2">
          <button
            v-for="(row, index) in chartRows"
            :key="row.skill"
            type="button"
            class="flex items-center justify-between rounded-lg border px-3 py-2 text-left text-sm transition hover:-translate-y-0.5 hover:shadow-sm"
            :class="selectedSkill?.skill === row.skill ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white'"
            @click="selectedSkill = row"
          >
            <span class="flex min-w-0 items-center gap-2">
              <span class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: colors[index % colors.length] }"></span>
              <span class="truncate font-semibold text-slate-700">{{ row.skill }}</span>
            </span>
            <span class="font-black text-slate-950">{{ row.count }}</span>
          </button>
        </div>
      </div>

      <div class="overflow-auto rounded-lg border border-slate-200">
        <div v-if="selectedSkill" class="border-b border-slate-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
          Selected: <strong>{{ selectedSkill.skill }}</strong> - {{ selectedSkill.count }} occurrence(s)
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left">Least Mastered Item</th>
              <th class="px-4 py-3 text-right">Count</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="row in chartRows"
              :key="row.skill"
              class="cursor-pointer hover:bg-blue-50/70"
              :class="selectedSkill?.skill === row.skill ? 'bg-blue-50' : ''"
              @click="selectedSkill = row"
            >
              <td class="px-4 py-3 font-semibold text-slate-800">{{ row.skill }}</td>
              <td class="px-4 py-3 text-right">{{ row.count }}</td>
            </tr>
            <tr v-if="!loading && chartRows.length === 0"><td colspan="2" class="px-4 py-10 text-center text-slate-500">No least mastered data found.</td></tr>
            <tr v-if="loading"><td colspan="2" class="px-4 py-10 text-center text-slate-500">Loading chart...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="border-t border-slate-200 p-4">
      <h3 class="text-sm font-bold text-slate-950">Quick Analysis</h3>
      <div class="mt-3 grid gap-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
          <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Item Difficulty</p>
          <div class="relative mt-2 h-72">
            <Doughnut v-if="difficultyRows.length" :data="difficultyData" :options="analysisOptions" />
            <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No item difficulty data yet.</div>
          </div>
          <div class="mt-3 flex flex-wrap justify-center gap-3 text-xs text-slate-600">
            <span v-for="(row, index) in difficultyRows" :key="row.label" class="inline-flex items-center gap-1">
              <span class="h-2.5 w-2.5 rounded-sm" :style="{ backgroundColor: difficultyColors[index] }"></span>
              {{ row.label }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Doughnut } from 'vue-chartjs';
import { fetchSystemAdminLeastMasteredSkills } from '../../services/systemAdminService';

ChartJS.register(ArcElement, Tooltip, Legend);

const filters = reactive({ school_id: '', school_year: '', subject_id: '', grade_level: '', section: '' });
const options = reactive({ schools: [], school_years: [], subjects: [], grades: [], sections: [] });
const chartRows = ref([]);
const quickAnalysis = ref({ mastery_levels: [], item_difficulty: [] });
const selectedSkill = ref(null);
const loading = ref(false);
const error = ref('');
const colors = ['#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#f97316', '#64748b', '#84cc16', '#ec4899', '#06b6d4', '#a855f7'];
const difficultyColors = ['#3b82f6', '#a855f7', '#ef4444'];

const chartTotal = computed(() => chartRows.value.reduce((sum, row) => sum + Number(row.count || 0), 0));
const difficultyRows = computed(() => (quickAnalysis.value.item_difficulty || []).filter((row) => Number(row.count || 0) > 0));

const chartData = computed(() => ({
  labels: chartRows.value.map((row) => row.skill),
  datasets: [{
    data: chartRows.value.map((row) => row.count),
    backgroundColor: chartRows.value.map((_, index) => colors[index % colors.length]),
    borderColor: '#ffffff',
    borderWidth: 4,
    hoverOffset: 18,
    spacing: 2,
    offset: chartRows.value.map((row) => (selectedSkill.value?.skill === row.skill ? 10 : 0)),
  }],
}));

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '62%',
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label(context) {
          const value = Number(context.raw || 0);
          const pct = chartTotal.value ? Math.round((value / chartTotal.value) * 100) : 0;
          return ` ${context.label}: ${value} (${pct}%)`;
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
    const index = elements?.[0]?.index;
    if (index !== undefined && chartRows.value[index]) {
      selectedSkill.value = chartRows.value[index];
    }
  },
}));

const difficultyData = computed(() => ({
  labels: difficultyRows.value.map((row) => row.label),
  datasets: [{
    data: difficultyRows.value.map((row) => row.count),
    backgroundColor: difficultyRows.value.map((_, index) => difficultyColors[index]),
    borderColor: '#ffffff',
    borderWidth: 3,
    hoverOffset: 12,
  }],
}));

const analysisOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '0%',
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label(context) {
          return ` ${context.label}: ${context.raw}`;
        },
      },
    },
  },
}));

async function loadData() {
  loading.value = true;
  error.value = '';
  try {
    const res = await fetchSystemAdminLeastMasteredSkills({ ...filters });
    Object.assign(options, res.filters || {});
    chartRows.value = res.data || [];
    quickAnalysis.value = res.quick_analysis || { mastery_levels: [], item_difficulty: [] };
    selectedSkill.value = chartRows.value[0] || null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load least mastered skills.';
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);
</script>
