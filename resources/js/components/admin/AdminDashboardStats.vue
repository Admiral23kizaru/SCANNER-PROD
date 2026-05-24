<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">Project TEA - Tracking Engagement and Assessment</h1>
        <p class="text-sm text-slate-500 mt-1">Dashboard overview for engagement, attendance, and assessment activity</p>
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

    <!-- Statistics Grid: 3 top cards, 1 bottom card (wraps under Students) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Total Teachers (Now 1st) -->
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

      <!-- Total Learners (Now 2nd) -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Learners</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ dashboardStats.totals?.students ?? '—' }}</p>
            <p class="mt-2 text-xs text-slate-400">Enrolled learners in system</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-inner">
            <GraduationCap class="h-6 w-6" />
          </div>
        </div>
      </div>

      <!-- Animated Status Card (3rd) -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm transition-all hover:shadow-md overflow-hidden relative group">
        <div class="flex justify-between items-start mb-2">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Learner Status Today</p>
            <transition name="slide-fade" mode="out-in">
              <p :key="activeStatusKey" 
                 class="text-lg font-bold text-slate-900 cursor-pointer hover:text-indigo-600 hover:underline transition-color"
                 @click="openPopulationModal(activeStatusKey)"
                 title="Click to view detailed list"
              >
                {{ activeStatusLabel || 'Attendance' }}: {{ activeStatusCount || 0 }}
              </p>
            </transition>
        </div>

        <!-- Sliding Selector UI -->
        <div class="relative h-[56px] w-full flex items-center bg-slate-50 rounded-xl p-1 mt-4 border border-slate-100">
          <!-- The Sliding Highlight -->
          <div 
            class="absolute h-10 bg-white rounded-lg shadow-sm border border-slate-200/50 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
            :style="sliderStyle"
          ></div>

          <button 
            @click="toggleStatus('Male')"
            class="relative z-10 flex-1 flex items-center justify-center text-[10px] font-bold tracking-widest transition-colors duration-300 cursor-pointer"
            :class="activeStatusKey === 'Male' ? 'text-blue-600' : 'text-slate-400 hover:text-slate-500'"
          >
            MALE
          </button>
          <button 
            @click="toggleStatus('Female')"
            class="relative z-10 flex-1 flex items-center justify-center text-[10px] font-bold tracking-widest transition-colors duration-300 cursor-pointer"
            :class="activeStatusKey === 'Female' ? 'text-pink-600' : 'text-slate-400 hover:text-slate-500'"
          >
            FEMALE
          </button>
          <button 
            @click="toggleStatus('Absent')"
            class="relative z-10 flex-1 flex items-center justify-center text-[10px] font-bold tracking-widest transition-colors duration-300 cursor-pointer"
            :class="activeStatusKey === 'Absent' ? 'text-orange-600' : 'text-slate-400 hover:text-slate-500'"
          >
            ABSENT
          </button>
        </div>
      </div>

      <!-- Today's Attendance (Row 2, Column 1 - directly under Teachers) -->
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

    <!-- Attendance snapshot -->
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)] gap-6">
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <h2 class="text-lg font-semibold text-slate-900">Attendance Pie Chart</h2>
          <select
            v-model="attendancePieView"
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100 sm:w-36"
          >
            <option value="status">Status</option>
            <option value="gender">Gender</option>
            <option value="grade">Grade</option>
          </select>
        </div>
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_180px] lg:items-center">
          <div class="h-[300px] relative">
            <Doughnut v-if="attendancePieData.labels.length" :data="attendancePieData" :options="doughnutOptions" />
            <div v-else-if="!loading" class="absolute inset-0 flex items-center justify-center text-slate-400 text-sm italic">
              No attendance data available yet.
            </div>
          </div>
          <div class="space-y-3">
            <div
              v-for="(label, index) in attendancePieData.labels"
              :key="label"
              class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2"
            >
              <div class="flex min-w-0 items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: attendancePieData.datasets[0].backgroundColor[index] }"></span>
                <span class="truncate text-xs font-medium text-slate-600">{{ label }}</span>
              </div>
              <span class="text-sm font-bold text-slate-900">{{ attendancePieData.datasets[0].data[index] }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-lg font-semibold text-slate-900">Calendar</h2>
          <p class="rounded-lg bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">{{ calendarMonthLabel }}</p>
        </div>
        <div class="mt-6 grid grid-cols-7 gap-2 text-center text-xs font-semibold text-slate-400">
          <span v-for="day in weekDays" :key="day">{{ day }}</span>
        </div>
        <div class="mt-3 grid grid-cols-7 gap-2">
          <div
            v-for="(day, index) in calendarDays"
            :key="index"
            class="relative flex aspect-square items-center justify-center rounded-lg text-sm font-medium"
            :class="[
              day.inMonth ? 'text-slate-700' : 'text-slate-300',
              selectedCalendarDate === day.iso ? 'ring-2 ring-teal-500 ring-offset-2' : '',
              day.isToday ? 'bg-teal-500 text-white shadow-lg shadow-teal-200' : 'hover:bg-slate-50'
            ]"
            @click="selectCalendarDate(day.iso)"
          >
            {{ day.date }}
            <span v-if="eventsForDate(day.iso).length" class="absolute bottom-1 h-1.5 w-1.5 rounded-full" :class="day.isToday ? 'bg-white' : 'bg-teal-500'"></span>
          </div>
        </div>
        <form class="mt-5 flex gap-2" @submit.prevent="saveCalendarEvent">
          <input
            v-model="newCalendarEventTitle"
            class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
            :placeholder="`Add event for ${selectedCalendarDate}`"
          />
          <button class="rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white">Add</button>
        </form>
        <div class="mt-3 space-y-2">
          <div v-for="event in eventsForDate(selectedCalendarDate)" :key="event.id" class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
            <span class="truncate text-sm font-medium text-slate-700">{{ event.title }}</span>
            <button class="text-xs font-semibold text-red-600 hover:text-red-700" @click="removeCalendarEvent(event.id)">Delete</button>
          </div>
          <p v-if="eventsForDate(selectedCalendarDate).length === 0" class="text-xs text-slate-400">No events on this date.</p>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <h2 class="text-lg font-semibold text-slate-900">Attendance Trends</h2>
          <div class="flex flex-wrap items-center gap-2">
            <select v-model="trendFilter.group_by" @change="loadTrends" class="text-xs rounded-lg border-slate-200 focus:ring-slate-900">
              <option value="day">Daily</option>
              <option value="week">Weekly</option>
              <option value="month">Monthly</option>
            </select>
            <select
              v-if="gradeOptions.length"
              v-model="trendFilter.grade"
              @change="onGradeFilterChange"
              class="text-xs rounded-lg border-slate-200 focus:ring-slate-900"
            >
              <option value="">All grades</option>
              <option v-for="grade in gradeOptions" :key="grade" :value="grade">{{ grade }}</option>
            </select>
            <select
              v-if="sectionOptions.length"
              v-model="trendFilter.section"
              @change="loadTrends"
              class="text-xs rounded-lg border-slate-200 focus:ring-slate-900"
            >
              <option value="">All sections</option>
              <option v-for="section in sectionOptions" :key="section" :value="section">{{ section }}</option>
            </select>
          </div>
        </div>
        <div class="h-[300px] relative">
          <Line v-if="trendData.labels.length" :data="trendData" :options="lineOptions" />
          <div v-else-if="!loading" class="absolute inset-0 flex items-center justify-center text-slate-400 text-sm italic">
            No trend data available for selected filters.
          </div>
        </div>
      </div>

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
              <p class="text-xs text-slate-500 truncate mt-0.5">{{ a.subtitle }}</p>
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
            class="group text-left rounded-2xl border border-slate-100 bg-slate-50/50 p-5 hover:bg-white hover:shadow-lg transition-all duration-300 cursor-pointer"
            @click="quickAddTeacher"
          >
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                <UserPlus class="h-6 w-6" />
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">Add Teacher</p>
                <p class="text-xs text-slate-500 mt-1">Register educator account</p>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-100 bg-slate-50/50 p-5 hover:bg-white hover:shadow-lg transition-all duration-300 cursor-pointer"
            @click="quickAddStudent"
          >
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                <QrCode class="h-6 w-6" />
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">Add Learner</p>
                <p class="text-xs text-slate-500 mt-1">Enroll a new learner</p>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-100 bg-slate-50/50 p-5 hover:bg-white hover:shadow-lg transition-all duration-300 cursor-pointer"
            @click="quickPrintReports"
          >
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                <FileText class="h-6 w-6" />
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">Print Reports</p>
                <p class="text-xs text-slate-500 mt-1">Export PDF summary</p>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-100 bg-slate-50/50 p-5 hover:bg-white hover:shadow-lg transition-all duration-300 cursor-pointer"
            @click="quickGoStudents"
          >
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                <Settings class="h-6 w-6" />
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">Master List</p>
                <p class="text-xs text-slate-500 mt-1">Manage all learners</p>
              </div>
            </div>
          </button>
        </div>
      </section>
    </div>

    <!-- Population Analytics Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeModal"></div>
      <div class="relative bg-white rounded-2xl w-full max-w-2xl shadow-xl flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
          <h2 class="text-lg font-semibold text-slate-900">{{ modalTitle }}</h2>
          <button @click="closeModal" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 transition">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        
        <div class="flex-1 overflow-auto p-5">
          <div v-if="modalLoading" class="flex flex-col items-center justify-center py-12">
            <div class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            <p class="mt-4 text-sm font-medium text-slate-500">Loading learners...</p>
          </div>
          <div v-else-if="modalStudents.length === 0" class="text-center py-12 text-slate-500">
            No learners found for this category.
          </div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="student in modalStudents" :key="student.id" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50">
              <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 text-indigo-700 font-bold border border-indigo-200">
                 {{ student.last_name?.charAt(0) }}{{ student.first_name?.charAt(0) }}
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900 truncate">{{ student.last_name }}, {{ student.first_name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ student.grade || 'No Grade' }} - {{ student.section || 'No Section' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed, watch } from 'vue';
import axios from 'axios';
import {
  createAdminCalendarEvent,
  deleteAdminCalendarEvent,
  fetchAdminCalendarEvents,
  fetchDashboardOverview,
  fetchSummaryReportPdfBlob,
  fetchDashboardStats,
  fetchAttendanceTrends,
  fetchAdminSections,
} from '../../services/adminService';
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
  ArcElement,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  ArcElement,
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
const schoolSections = ref([]);
const attendancePieView = ref('status');
const weekDays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const calendarEvents = ref([]);
const selectedCalendarDate = ref(toDateInputValue(new Date()));
const newCalendarEventTitle = ref('');

// ─── Population Modal State ─────────────────────────────────────────────
// Why: Clicking "Male", "Female", or "Absent" on the status card should
//      open a detailed modal listing those specific learners.
// How: openPopulationModal() sends a GET to /api/admin/dashboard/analytics
//      with ?type=male|female|absent and renders the result in a modal.

const isModalOpen = ref(false);
const modalTitle = ref('');
const modalStudents = ref([]);
const modalLoading = ref(false);

/**
 * // Description: openPopulationModal - Fetches and displays a filtered learner
 * //   list in a modal based on the clicked category (Male, Female, Absent).
 * // Author: Antigravity System Agent
 *
 * @param {string} category - One of: 'Male', 'Female', 'Absent'
 */
async function openPopulationModal(category) {
  if (!category) return;
  
  isModalOpen.value = true;
  modalTitle.value = `${category} Learners List`;
  modalLoading.value = true;
  modalStudents.value = [];
  
  try {
    const type = category.toLowerCase();
    const response = await axios.get(`/api/admin/dashboard/analytics?type=${type}`);
    modalStudents.value = response.data.data;
  } catch (error) {
    console.error('Failed to load learners', error);
  } finally {
    modalLoading.value = false;
  }
}

/**
 * // Description: closeModal - Resets modal state so it can be cleanly reopened.
 */
function closeModal() {
  isModalOpen.value = false;
  modalStudents.value = [];
}

// ─── Status Slider Logic ───────────────────────────────────────────────
// Why: The animated "Learner Status Today" card cycles through Male/Female/Absent
//      with a sliding highlight. Each category is also clickable to open its modal.
// How: activeStatusKey drives the label, count, and slider position via computed props.

const activeStatusKey = ref('Male');

const activeStatusLabel = computed(() => {
  if (!activeStatusKey.value) return '';
  return activeStatusKey.value;
});

const activeStatusCount = computed(() => {
  if (!activeStatusKey.value) return null;
  const totals = dashboardStats.value.totals;
  if (activeStatusKey.value === 'Male') return totals?.male_today ?? 0;
  if (activeStatusKey.value === 'Female') return totals?.female_today ?? 0;
  if (activeStatusKey.value === 'Absent') return totals?.absent_today ?? 0;
  return null;
});

/**
 * // Description: toggleStatus - Toggles which demographic (Male/Female/Absent) is
 * //   currently highlighted on the slider. Clicking the active key deselects it.
 */
function toggleStatus(key) {
  if (activeStatusKey.value === key) {
    activeStatusKey.value = null;
  } else {
    activeStatusKey.value = key;
  }
}

/**
 * // Description: sliderStyle - Computes the CSS position for the animated sliding
 * //   highlight bar based on which status key is active.
 */

const sliderStyle = computed(() => {
  const width = 33.33;
  let left = 0;
  if (activeStatusKey.value === 'Male') left = 0;
  else if (activeStatusKey.value === 'Female') left = 33.33;
  else if (activeStatusKey.value === 'Absent') left = 66.66;
  else return { opacity: 0, transform: 'scale(0.8)' };

  return {
    width: `${width}%`,
    left: `${left}%`,
    opacity: 1
  };
});

const trendFilter = reactive({
  group_by: 'day',
  grade: '',
  section: ''
});

const trendResponse = ref([]);

const gradeOptions = computed(() => {
  return [...new Set(
    schoolSections.value
      .map((section) => section.grade_level)
      .filter(Boolean)
  )].sort((a, b) => String(a).localeCompare(String(b), undefined, { numeric: true }));
});

const sectionOptions = computed(() => {
  return [...new Set(
    schoolSections.value
      .filter((section) => !trendFilter.grade || section.grade_level === trendFilter.grade)
      .map((section) => section.name)
      .filter(Boolean)
  )].sort((a, b) => String(a).localeCompare(String(b), undefined, { numeric: true }));
});

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

const attendancePieData = computed(() => {
  const totals = dashboardStats.value.totals || {};
  const gradeRows = dashboardStats.value.attendance_by_grade || [];

  if (attendancePieView.value === 'gender') {
    return makePieData(
      ['Male', 'Female'],
      [totals.male_today || 0, totals.female_today || 0],
      ['#3b82f6', '#ec4899']
    );
  }

  if (attendancePieView.value === 'grade') {
    return makePieData(
      gradeRows.map(item => `Grade ${item.grade || 'N/A'}`),
      gradeRows.map(item => item.count || 0),
      ['#14b8a6', '#8b5cf6', '#f59e0b', '#3b82f6', '#ef4444', '#22c55e', '#06b6d4']
    );
  }

  return makePieData(
    ['Present', 'Absent'],
    [totals.attendance_today || 0, totals.absent_today || 0],
    ['#22c55e', '#f97316']
  );
});

function makePieData(labels, values, colors) {
  const filtered = labels
    .map((label, index) => ({ label, value: values[index] || 0, color: colors[index % colors.length] }))
    .filter(item => item.value > 0);

  return {
    labels: filtered.map(item => item.label),
    datasets: [
      {
        data: filtered.map(item => item.value),
        backgroundColor: filtered.map(item => item.color),
        borderColor: '#ffffff',
        borderWidth: 4,
        hoverOffset: 8,
      }
    ]
  };
}

const calendarMonthLabel = computed(() => {
  return new Date().toLocaleDateString([], { month: 'long', year: 'numeric' });
});

const calendarDays = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  const firstDay = new Date(year, month, 1);
  const start = new Date(year, month, 1 - firstDay.getDay());

  return Array.from({ length: 42 }, (_, index) => {
    const date = new Date(start);
    date.setDate(start.getDate() + index);

    return {
      date: date.getDate(),
      iso: toDateInputValue(date),
      inMonth: date.getMonth() === month,
      isToday:
        date.getFullYear() === now.getFullYear() &&
        date.getMonth() === now.getMonth() &&
        date.getDate() === now.getDate(),
    };
  });
});

function toDateInputValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function calendarMonthValue() {
  const now = new Date();
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

function eventsForDate(date) {
  return calendarEvents.value.filter(event => String(event.event_date).slice(0, 10) === date);
}

function selectCalendarDate(date) {
  selectedCalendarDate.value = date;
}

async function loadCalendarEvents() {
  try {
    calendarEvents.value = await fetchAdminCalendarEvents(calendarMonthValue());
  } catch {
    calendarEvents.value = [];
  }
}

async function saveCalendarEvent() {
  const title = newCalendarEventTitle.value.trim();
  if (!title) return;
  await createAdminCalendarEvent({
    title,
    event_date: selectedCalendarDate.value,
  });
  newCalendarEventTitle.value = '';
  await loadCalendarEvents();
}

async function removeCalendarEvent(id) {
  await deleteAdminCalendarEvent(id);
  await loadCalendarEvents();
}

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

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '62%',
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

async function loadSectionFilters() {
  try {
    schoolSections.value = await fetchAdminSections();
  } catch {
    schoolSections.value = [];
    trendFilter.grade = '';
    trendFilter.section = '';
  }
}

function onGradeFilterChange() {
  if (trendFilter.section && !sectionOptions.value.includes(trendFilter.section)) {
    trendFilter.section = '';
  }
  loadTrends();
}

watch(sectionOptions, (options) => {
  if (trendFilter.section && !options.includes(trendFilter.section)) {
    trendFilter.section = '';
    loadTrends();
  }
});

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
  await loadSectionFilters();
  await Promise.all([
    (async () => {
      try {
        dashboardStats.value = await fetchDashboardStats();
      } catch {
        dashboardStats.value = {};
      }
    })(),
    loadOverview(),
    loadTrends(),
    loadCalendarEvents()
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

onMounted(async () => {
  await loadData();
});
</script>

<style scoped>
/* Sliding Transition for the Title/Count */
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}

/* Base Card Hover effects */
.group:hover .bg-slate-50 {
  background-color: #f1f5f9;
}

/* Responsive adjustments for the grid if needed */
@media (max-width: 768px) {
  .grid-cols-3 {
    grid-template-columns: 1fr;
  }
}
</style>
