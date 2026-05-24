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
    <div class="grid gap-5 p-4 lg:grid-cols-[420px_1fr]">
      <div class="h-80 rounded-lg border border-slate-200 bg-white p-3">
        <Pie v-if="chartRows.length" :data="chartData" :options="chartOptions" />
        <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No Learning Assessment analysis yet.</div>
      </div>
      <div class="overflow-auto rounded-lg border border-slate-200">
        <div v-if="selectedSkill" class="border-b border-slate-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
          Selected: <strong>{{ selectedSkill.skill }}</strong> · {{ selectedSkill.count }} occurrence(s)
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3 text-left">Least Mastered Item</th><th class="px-4 py-3 text-right">Count</th></tr></thead>
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
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Pie } from 'vue-chartjs';
import { fetchSystemAdminLeastMasteredSkills } from '../../services/systemAdminService';

ChartJS.register(ArcElement, Tooltip, Legend);

const filters = reactive({ school_id: '', school_year: '', subject_id: '', grade_level: '', section: '' });
const options = reactive({ schools: [], school_years: [], subjects: [], grades: [], sections: [] });
const chartRows = ref([]);
const selectedSkill = ref(null);
const loading = ref(false);
const error = ref('');
const colors = ['#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#f97316', '#64748b', '#84cc16', '#ec4899', '#06b6d4', '#a855f7'];
const chartData = computed(() => ({ labels: chartRows.value.map((r) => r.skill), datasets: [{ data: chartRows.value.map((r) => r.count), backgroundColor: colors }] }));
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
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
async function loadData() {
  loading.value = true;
  error.value = '';
  try {
    const res = await fetchSystemAdminLeastMasteredSkills({ ...filters });
    Object.assign(options, res.filters || {});
    chartRows.value = res.data || [];
    selectedSkill.value = chartRows.value[0] || null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load least mastered skills.';
  } finally {
    loading.value = false;
  }
}
onMounted(loadData);
</script>
