<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Least Mastered Skills</h2>
        <p class="mt-1 text-sm text-slate-500">Assessment results filtered by school year, subject, grade, and section.</p>
      </div>
      <div class="grid gap-2 sm:grid-cols-4">
        <select v-model="filters.school_year" class="rounded-lg border border-slate-200 px-3 py-2 text-xs" @change="load">
          <option value="">All years</option>
          <option v-for="year in options.school_years" :key="year" :value="year">{{ year }}</option>
        </select>
        <select v-model="filters.subject_id" class="rounded-lg border border-slate-200 px-3 py-2 text-xs" @change="load">
          <option value="">All subjects</option>
          <option v-for="subject in options.subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
        </select>
        <select v-model="filters.grade_level" class="rounded-lg border border-slate-200 px-3 py-2 text-xs" @change="load">
          <option value="">All grades</option>
          <option v-for="grade in options.grades" :key="grade" :value="grade">{{ grade }}</option>
        </select>
        <select v-model="filters.section" class="rounded-lg border border-slate-200 px-3 py-2 text-xs" @change="load">
          <option value="">All sections</option>
          <option v-for="section in options.sections" :key="section" :value="section">{{ section }}</option>
        </select>
      </div>
    </div>
    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_220px]">
      <div class="h-[340px]">
        <Doughnut v-if="chartData.labels.length" :data="chartData" :options="chartOptions" />
        <div v-else class="flex h-full items-center justify-center text-sm text-slate-500">No assessment results yet.</div>
      </div>
      <div class="space-y-2">
        <div v-for="(label, index) in chartData.labels" :key="label" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
          <span class="truncate text-slate-600">{{ label }}</span>
          <span class="font-bold text-slate-900">{{ chartData.datasets[0].data[index] }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { ArcElement, Chart as ChartJS, Legend, Tooltip } from 'chart.js';
import { Doughnut } from 'vue-chartjs';
import { fetchLeastMasteredSkills } from '../../services/adminService';

ChartJS.register(ArcElement, Tooltip, Legend);

const rows = ref([]);
const options = reactive({ school_years: [], subjects: [], grades: [], sections: [] });
const filters = reactive({ school_year: '', subject_id: '', grade_level: '', section: '' });
const colors = ['#14b8a6', '#8b5cf6', '#f59e0b', '#3b82f6', '#ef4444', '#22c55e', '#06b6d4', '#ec4899'];

const chartData = computed(() => ({
  labels: rows.value.map(row => row.skill),
  datasets: [{ data: rows.value.map(row => row.count), backgroundColor: rows.value.map((_, index) => colors[index % colors.length]), borderColor: '#fff', borderWidth: 4 }]
}));

const chartOptions = { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { display: false } } };

async function load() {
  const response = await fetchLeastMasteredSkills({ ...filters });
  rows.value = response.data || [];
  Object.assign(options, response.filters || {});
}

onMounted(load);
</script>
