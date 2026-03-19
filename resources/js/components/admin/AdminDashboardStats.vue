<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">Dashboard Overview</h1>
        <p class="text-sm text-slate-500 mt-1">Welcome to the Ozamiz Schools QR-ID Management System</p>
      </div>
      <div class="flex items-center gap-2">
        <button 
          @click="loadData" 
          class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-sm"
          :disabled="loading"
        >
          <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
          Refresh
        </button>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Students</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ dashboardStats.totals?.students ?? '—' }}</p>
            <p class="mt-2 text-xs text-slate-400">Enrolled learners in system</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-inner">
            <GraduationCap class="h-6 w-6" />
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Teachers</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ dashboardStats.totals?.teachers ?? '—' }}</p>
            <p class="mt-2 text-xs text-slate-400">Registered teacher accounts</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-inner">
            <Users class="h-6 w-6" />
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Today's Attendance</p>
            <div class="flex items-baseline gap-2">
              <p class="mt-2 text-3xl font-bold text-slate-900">{{ dashboardStats.totals?.attendance_today ?? '—' }}</p>
              <div v-if="dashboardStats.totals?.is_above_average" class="flex items-center text-xs font-medium text-emerald-600">
                <TrendingUp class="h-3 w-3 mr-1" />
                Above Avg
              </div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Avg: {{ dashboardStats.historical_average || '—' }} per day</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-inner">
            <CalendarDays class="h-6 w-6" />
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <!-- Attendance Trends -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <h2 class="text-lg font-semibold text-slate-900">Attendance Trends</h2>
          <div class="flex items-center gap-2">
            <select v-model="trendFilter.group_by" @change="loadTrends" class="text-xs rounded-lg border-slate-200 focus:ring-slate-900">
              <option value="day">Daily</option>
              <option value="week">Weekly</option>
              <option value="month">Monthly</option>
            </select>
            <input 
              v-model="trendFilter.grade" 
              @change="loadTrends" 
              placeholder="Grade" 
              class="text-xs w-16 rounded-lg border-slate-200 focus:ring-slate-900" 
            />
          </div>
        </div>
        
        <div class="h-[300px] relative">
          <Line v-if="trendData.labels.length" :data="trendData" :options="lineOptions" />
          <div v-else-if="!loading" class="absolute inset-0 flex items-center justify-center text-slate-400 text-sm italic">
            No trend data available for selected filters.
          </div>
        </div>
      </div>

      <!-- Attendance per Grade -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900 mb-6">Attendance by Grade (Today)</h2>
        <div class="h-[300px] relative">
          <Bar v-if="gradeData.labels.length" :data="gradeData" :options="barOptions" />
          <div v-else-if="!loading" class="absolute inset-0 flex items-center justify-center text-slate-400 text-sm italic">
            No attendance recorded today yet.
          </div>
        </div>
      </div>
    </div>

    <!-- Lower panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Recent Activity</h2>
          <button type="button" class="text-slate-400 hover:text-slate-600 transition" @click="loadOverview">
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
          </button>
        </div>

        <div class="mt-6 space-y-4">
          <div
            v-for="(a, i) in recentActivity"
            :key="a.time + '-' + i"
            class="group flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition"
          >
            <div class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-slate-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors shadow-sm">
              <component :is="iconFor(a.type)" class="h-5 w-5" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-900">{{ a.title }}</p>
              <p class="text-xs text-slate-500 truncate mt-0.5">
                {{ a.subtitle }}
              </p>
            </div>
            <div class="text-right shrink-0">
               <p class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">{{ formatTime(a.time) }}</p>
               <p class="text-[10px] text-slate-300">{{ formatDate(a.time) }}</p>
            </div>
          </div>

          <div v-if="!loading && recentActivity.length === 0" class="py-12 text-center">
            <p class="text-sm text-slate-400 italic">No recent activity detected.</p>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Quick Actions</h2>

        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-100 bg-slate-50/50 p-5 hover:bg-white hover:border-slate-200 hover:shadow-lg transition-all duration-300 border border-transparent cursor-pointer"
            @click="quickAddTeacher"
          >
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                <UserPlus class="h-6 w-6" />
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">Add Teacher</p>
                <p class="text-xs text-slate-500 mt-1">Register a new educator account</p>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-100 bg-slate-50/50 p-5 hover:bg-white hover:border-slate-200 hover:shadow-lg transition-all duration-300 border border-transparent cursor-pointer"
            @click="quickAddStudent"
          >
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                <QrCode class="h-6 w-6" />
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">Add Student</p>
                <p class="text-xs text-slate-500 mt-1">Enroll a new learner in waitlist</p>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-100 bg-slate-50/50 p-5 hover:bg-white hover:border-slate-200 hover:shadow-lg transition-all duration-300 border border-transparent cursor-pointer"
            @click="quickPrintReports"
          >
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                <FileText class="h-6 w-6" />
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">Print Reports</p>
                <p class="text-xs text-slate-500 mt-1">Export PDF summary report</p>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-100 bg-slate-50/50 p-5 hover:bg-white hover:border-slate-200 hover:shadow-lg transition-all duration-300 border border-transparent cursor-pointer"
            @click="quickGoStudents"
          >
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                <Settings class="h-6 w-6" />
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">Master List</p>
                <p class="text-xs text-slate-500 mt-1">Manage all system students</p>
              </div>
            </div>
          </button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import { fetchDashboardOverview, fetchSummaryReportPdfBlob, fetchDashboardStats, fetchAttendanceTrends } from '../../services/adminService';
import {
  GraduationCap,
  Users,
  CalendarDays,
  MoreHorizontal,
  Check,
  UserPlus,
  AlertTriangle,
  QrCode,
  FileText,
  Settings,
  RefreshCw,
  TrendingUp,
  TrendingDown,
} from 'lucide-vue-next';

// Chart.js imports
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler,
} from 'chart.js';
import { Bar, Line } from 'vue-chartjs';

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler
);

const emit = defineEmits(['navigate']);

const dashboardStats = ref({});
const recentActivity = ref([]);
const loading = ref(false);

const trendFilter = reactive({
  group_by: 'day',
  grade: '',
  section: ''
});

const trendResponse = ref([]);

const trendData = computed(() => ({
  labels: trendResponse.value.map(item => item.label),
  datasets: [
    {
      label: 'Attendance Count',
      data: trendResponse.value.map(item => item.count),
      borderColor: '#4f46e5',
      backgroundColor: 'rgba(79, 70, 229, 0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 4,
      pointBackgroundColor: '#fff',
      pointBorderColor: '#4f46e5',
      pointBorderWidth: 2,
    }
  ]
}));

const gradeData = computed(() => {
  const data = dashboardStats.value.attendance_by_grade || [];
  return {
    labels: data.map(item => `Grade ${item.grade}`),
    datasets: [
      {
        label: 'Today\'s Attendance',
        data: data.map(item => item.count),
        backgroundColor: '#10b981',
        borderRadius: 8,
      }
    ]
  };
});

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: '#fff',
      titleColor: '#1e293b',
      bodyColor: '#64748b',
      borderColor: '#e2e8f0',
      borderWidth: 1,
      padding: 12,
      displayColors: false,
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: '#f1f5f9' },
      ticks: { font: { size: 10 }, color: '#94a3b8' }
    },
    x: {
      grid: { display: false },
      ticks: { font: { size: 10 }, color: '#94a3b8' }
    }
  }
};

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#fff',
      titleColor: '#1e293b',
      bodyColor: '#64748b',
      borderColor: '#e2e8f0',
      borderWidth: 1,
      padding: 12,
      displayColors: false,
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: '#f1f5f9' },
      ticks: { font: { size: 10 }, color: '#94a3b8' }
    },
    x: {
      grid: { display: false },
      ticks: { font: { size: 10 }, color: '#94a3b8' }
    }
  }
};

function iconFor(type) {
  if (type === 'attendance') return Check;
  if (type === 'registration') return UserPlus;
  if (type === 'alert') return AlertTriangle;
  return Check;
}

function formatTime(iso) {
  try {
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
}

function formatDate(iso) {
  try {
    return new Date(iso).toLocaleDateString([], { month: 'short', day: 'numeric' });
  } catch {
    return '';
  }
}

function quickAddTeacher() {
  sessionStorage.setItem('admin_open_create_teacher', '1');
  emit('navigate', 'teachers');
}

function quickAddStudent() {
  sessionStorage.setItem('admin_open_create_student', '1');
  emit('navigate', 'students');
}

async function quickPrintReports() {
  try {
    const blob = await fetchSummaryReportPdfBlob();
    const url = URL.createObjectURL(blob);
    window.open(url, '_blank', 'noopener');
    setTimeout(() => URL.revokeObjectURL(url), 60_000);
  } catch (_) {}
}

function quickGoStudents() {
  emit('navigate', 'students');
}

async function loadOverview() {
  try {
    const data = await fetchDashboardOverview();
    recentActivity.value = data.recent_activity || [];
  } catch {
    recentActivity.value = [];
  }
}

async function loadData() {
  loading.value = true;
  await Promise.all([
    (async () => {
      try {
        dashboardStats.value = await fetchDashboardStats();
      } catch {
        dashboardStats.value = {};
      }
    })(),
    loadOverview(),
    loadTrends()
  ]);
  loading.value = false;
}

async function loadTrends() {
  try {
    trendResponse.value = await fetchAttendanceTrends({
      group_by: trendFilter.group_by,
      grade: trendFilter.grade || undefined,
      section: trendFilter.section || undefined
    });
  } catch {
    trendResponse.value = [];
  }
}

onMounted(() => {
  loadData();
});
</script>
