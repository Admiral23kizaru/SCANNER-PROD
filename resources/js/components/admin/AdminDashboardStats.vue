<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">Dashboard Overview</h1>
      <p class="text-sm text-slate-500 mt-1">Welcome to the Ozamiz Schools QR-ID Management System</p>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm text-slate-600">Total Students</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.total_students ?? '—' }}</p>
            <p class="mt-2 text-xs text-slate-500">Enrolled learners</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
            <GraduationCap class="h-5 w-5" />
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm text-slate-600">Total Teachers</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.total_teachers ?? '—' }}</p>
            <p class="mt-2 text-xs text-slate-500">Registered accounts</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
            <Users class="h-5 w-5" />
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm text-slate-600">Today's Attendance</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.todays_attendance ?? '—' }}</p>
            <p class="mt-2 text-xs text-slate-500">Scans recorded today</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
            <CalendarDays class="h-5 w-5" />
          </div>
        </div>
      </div>
    </div>

    <!-- Lower panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Recent Activity</h2>
          <button type="button" class="text-slate-400 hover:text-slate-600" title="More">
            <MoreHorizontal class="h-5 w-5" />
          </button>
        </div>

        <div class="mt-4 space-y-3">
          <div
            v-for="(a, i) in recentActivity"
            :key="a.time + '-' + i"
            class="flex items-start gap-3 rounded-xl bg-slate-50 p-3"
          >
            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-700">
              <component :is="iconFor(a.type)" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-900">{{ a.title }}</p>
              <p class="text-xs text-slate-500 truncate">
                {{ a.subtitle }}
                <span v-if="a.time">- {{ formatTime(a.time) }}</span>
              </p>
            </div>
          </div>

          <div v-if="!loading && recentActivity.length === 0" class="text-sm text-slate-500">
            No recent activity yet.
          </div>
        </div>
      </section>

      <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900">Quick Actions</h2>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition cursor-pointer"
            @click="quickAddTeacher"
          >
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <UserPlus class="h-5 w-5" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900">Add Teacher</p>
                <p class="text-xs text-slate-500 mt-0.5">Register new teacher</p>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition cursor-pointer"
            @click="quickAddStudent"
          >
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <QrCode class="h-5 w-5" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900">Add Student</p>
                <p class="text-xs text-slate-500 mt-0.5">Register new student</p>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition cursor-pointer"
            @click="quickPrintReports"
          >
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <FileText class="h-5 w-5" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900">Print Reports</p>
                <p class="text-xs text-slate-500 mt-0.5">Attendance summary report</p>
              </div>
            </div>
          </button>

          <button
            type="button"
            class="group text-left rounded-2xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition cursor-pointer"
            @click="quickGoStudents"
          >
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <Settings class="h-5 w-5" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900">Manage Students</p>
                <p class="text-xs text-slate-500 mt-0.5">Go to master list</p>
              </div>
            </div>
          </button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchDashboardOverview, fetchSummaryReportPdfBlob } from '../../services/adminService';
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
} from 'lucide-vue-next';

const emit = defineEmits(['navigate']);

const stats = ref({});
const recentActivity = ref([]);
const loading = ref(false);

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

onMounted(async () => {
  loading.value = true;
  try {
    const data = await fetchDashboardOverview();
    stats.value = data.stats || {};
    recentActivity.value = data.recent_activity || [];
  } catch {
    stats.value = {};
    recentActivity.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
