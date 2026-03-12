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
            <p class="mt-2 text-xs text-slate-500">
              <span class="text-emerald-600 font-medium">↑ +12%</span> from last month
            </p>
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
            <p class="mt-2 text-xs text-slate-500">
              <span class="text-emerald-600 font-medium">↑ +3%</span> from last month
            </p>
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
            <p class="mt-2 text-xs text-slate-500">
              <span class="text-emerald-600 font-medium">↑ +2.1%</span> from yesterday
            </p>
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
          <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3">
            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-700">
              <Check class="h-4 w-4" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-900">Student check-in</p>
              <p class="text-xs text-slate-500 truncate">John Doe - Grade 10A - 9:15 AM</p>
            </div>
          </div>

          <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3">
            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-700">
              <UserPlus class="h-4 w-4" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-900">New teacher registered</p>
              <p class="text-xs text-slate-500 truncate">Maria Santos - Math Department - 8:45 AM</p>
            </div>
          </div>

          <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3">
            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-700">
              <AlertTriangle class="h-4 w-4" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-900">Late arrival alert</p>
              <p class="text-xs text-slate-500 truncate">Anna Cruz - Grade 8B - 8:30 AM</p>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900">Quick Actions</h2>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <button type="button" class="group text-left rounded-2xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <UserPlus class="h-5 w-5" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900">Add Student</p>
                <p class="text-xs text-slate-500 mt-0.5">Register new student</p>
              </div>
            </div>
          </button>

          <button type="button" class="group text-left rounded-2xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <QrCode class="h-5 w-5" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900">Generate QR</p>
                <p class="text-xs text-slate-500 mt-0.5">Create new QR code</p>
              </div>
            </div>
          </button>

          <button type="button" class="group text-left rounded-2xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <FileText class="h-5 w-5" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900">View Reports</p>
                <p class="text-xs text-slate-500 mt-0.5">Attendance analytics</p>
              </div>
            </div>
          </button>

          <button type="button" class="group text-left rounded-2xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <Settings class="h-5 w-5" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900">Settings</p>
                <p class="text-xs text-slate-500 mt-0.5">System configuration</p>
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
import { fetchStats } from '../../services/adminService';
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

const stats = ref({});

onMounted(async () => {
  try {
    stats.value = await fetchStats();
  } catch {
    stats.value = {};
  }
});
</script>
