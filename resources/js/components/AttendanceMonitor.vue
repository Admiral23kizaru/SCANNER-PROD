<!--
  Header Comment: Action: Implementing Teacher-specific Attendance Monitoring with Split-View UI.
  Note: UI should prioritize high-contrast badges for quick scanning of classroom attendance.

  Source: AttendanceMonitor.vue
  Destination: /api/teacher/attendance/monitor (GET, authenticated)
  Function: Displays today's Present vs Absent split-view with auto-refresh polling (30s).
-->
<template>
  <div class="w-full mx-auto p-4 sm:p-6 lg:max-w-7xl">
    <!-- Page header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
      <div>
        <h2 class="text-xl font-bold text-stone-900 tracking-tight">Attendance Monitor</h2>
        <p class="text-sm text-stone-500 mt-0.5">
          <span v-if="monitorDate">{{ formattedDate }}</span>
          <span v-else>Loading…</span>
          <span v-if="totalStudents > 0" class="ml-2 text-stone-400">
            · {{ totalStudents }} total learner{{ totalStudents !== 1 ? 's' : '' }}
          </span>
        </p>
      </div>

      <div class="flex items-center gap-2">
        <!-- Auto-refresh indicator -->
        <div class="flex items-center gap-1.5 text-xs text-stone-400">
          <span
            class="relative flex h-2 w-2"
            :class="autoRefresh ? '' : 'opacity-30'"
          >
            <span
              v-if="autoRefresh"
              class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
            ></span>
            <span
              class="relative inline-flex rounded-full h-2 w-2"
              :class="autoRefresh ? 'bg-emerald-500' : 'bg-stone-300'"
            ></span>
          </span>
          <button
            type="button"
            class="hover:text-stone-600 transition"
            @click="autoRefresh = !autoRefresh"
            :title="autoRefresh ? 'Auto-refresh ON (30s) – click to pause' : 'Auto-refresh OFF – click to resume'"
          >
            {{ autoRefresh ? 'Live' : 'Paused' }}
          </button>
        </div>

        <!-- Manual refresh button -->
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-3.5 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 active:bg-stone-100 transition shadow-sm"
          :disabled="loading"
          @click="loadData"
        >
          <svg
            class="h-4 w-4 transition-transform"
            :class="loading ? 'animate-spin' : ''"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ loading ? 'Refreshing…' : 'Refresh' }}
        </button>
      </div>
    </div>

    <!-- Search bar -->
    <div class="mb-4">
      <div class="relative max-w-md">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-400 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search by name or LRN…"
          class="w-full rounded-lg border border-stone-200 bg-white pl-9 pr-3 py-2.5 text-sm text-stone-700 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-300 transition"
        />
      </div>
    </div>

    <!-- Error banner -->
    <div v-if="errorMsg" class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 flex items-start gap-3">
      <svg class="h-5 w-5 text-red-400 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-sm text-red-700">{{ errorMsg }}</p>
    </div>

    <!-- Split-view columns -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <!-- ═══ LEFT: PRESENT TODAY ═══ -->
      <div class="bg-white rounded-xl shadow-md border border-stone-200 overflow-hidden flex flex-col">
        <!-- Column header -->
        <div class="px-5 py-4 border-b border-stone-200 bg-emerald-50/60 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center shadow-sm">
              <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-sm font-bold text-emerald-900 uppercase tracking-wide">Present Today</h3>
          </div>
          <span class="inline-flex items-center rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white shadow-sm">
            {{ filteredPresent.length }}
          </span>
        </div>

        <!-- Student list -->
        <div class="flex-1 overflow-y-auto max-h-[60vh] p-3 space-y-2">
          <div
            v-for="student in filteredPresent"
            :key="student.id"
            class="flex items-center gap-3 rounded-lg border border-stone-100 bg-stone-50/50 px-3.5 py-2.5 hover:bg-emerald-50/40 transition group"
          >
            <!-- Avatar -->
            <div class="w-9 h-9 rounded-full overflow-hidden bg-stone-200 shrink-0 border border-stone-200">
              <img
                v-if="student.photo_path"
                :src="student.photo_path"
                class="w-full h-full object-cover"
                @error="student.photo_path = null"
              />
              <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-400 to-emerald-600 text-white text-xs font-bold">
                {{ (student.first_name?.[0] || '') + (student.last_name?.[0] || '') }}
              </div>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-stone-800 truncate">
                {{ titleCase(student.last_name) }}, {{ titleCase(student.first_name) }}
              </p>
              <p class="text-xs text-stone-500 truncate">
                {{ student.grade_section || '—' }}
                <span class="text-stone-300 mx-1">·</span>
                {{ student.student_number }}
              </p>
            </div>

            <!-- Time-in badge -->
            <div class="flex flex-col items-end gap-0.5 shrink-0">
              <span
                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold shadow-sm"
                :class="student.status === 'late'
                  ? 'bg-amber-100 text-amber-800 border border-amber-200'
                  : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
              >
                {{ student.time_in }}
              </span>
              <span
                v-if="student.status === 'late'"
                class="text-[10px] font-medium text-amber-600 uppercase tracking-wider"
              >Late</span>
              <span
                v-else
                class="text-[10px] font-medium text-emerald-600 uppercase tracking-wider"
              >On time</span>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="!loading && filteredPresent.length === 0" class="flex flex-col items-center justify-center py-10 text-stone-400">
            <svg class="h-10 w-10 mb-2 opacity-40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">{{ searchQuery ? 'No matches found.' : 'No students scanned in yet.' }}</p>
          </div>
        </div>
      </div>

      <!-- ═══ RIGHT: ABSENT TODAY ═══ -->
      <div class="bg-white rounded-xl shadow-md border border-stone-200 overflow-hidden flex flex-col">
        <!-- Column header -->
        <div class="px-5 py-4 border-b border-stone-200 bg-red-50/60 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-red-500 flex items-center justify-center shadow-sm">
              <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-sm font-bold text-red-900 uppercase tracking-wide">Absent Today</h3>
          </div>
          <span class="inline-flex items-center rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white shadow-sm">
            {{ filteredAbsent.length }}
          </span>
        </div>

        <!-- Student list -->
        <div class="flex-1 overflow-y-auto max-h-[60vh] p-3 space-y-2">
          <div
            v-for="student in filteredAbsent"
            :key="student.id"
            class="flex items-center gap-3 rounded-lg border border-stone-100 bg-stone-50/50 px-3.5 py-2.5 hover:bg-red-50/40 transition group"
          >
            <!-- Avatar -->
            <div class="w-9 h-9 rounded-full overflow-hidden bg-stone-200 shrink-0 border border-stone-200">
              <img
                v-if="student.photo_path"
                :src="student.photo_path"
                class="w-full h-full object-cover"
                @error="student.photo_path = null"
              />
              <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-red-400 to-red-600 text-white text-xs font-bold">
                {{ (student.first_name?.[0] || '') + (student.last_name?.[0] || '') }}
              </div>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-stone-800 truncate">
                {{ titleCase(student.last_name) }}, {{ titleCase(student.first_name) }}
              </p>
              <p class="text-xs text-stone-500 truncate">
                {{ student.grade_section || '—' }}
                <span class="text-stone-300 mx-1">·</span>
                {{ student.student_number }}
              </p>
            </div>

            <!-- Absent badge -->
            <span class="inline-flex items-center rounded-md bg-red-100 border border-red-200 px-2 py-0.5 text-xs font-bold text-red-700 shadow-sm shrink-0">
              Absent
            </span>
          </div>

          <!-- Empty state -->
          <div v-if="!loading && filteredAbsent.length === 0" class="flex flex-col items-center justify-center py-10 text-stone-400">
            <svg class="h-10 w-10 mb-2 opacity-40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">{{ searchQuery ? 'No matches found.' : 'All students are present! 🎉' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading skeleton overlay (first load only) -->
    <div v-if="loading && !hasLoaded" class="fixed inset-0 z-40 flex items-center justify-center bg-white/60 backdrop-blur-sm">
      <div class="flex flex-col items-center gap-3">
        <div class="h-8 w-8 rounded-full border-3 border-stone-300 border-t-stone-700 animate-spin"></div>
        <span class="text-sm text-stone-500 font-medium">Loading attendance data…</span>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * AttendanceMonitor.vue
 *
 * Action: Implementing Teacher-specific Attendance Monitoring with Split-View UI.
 * Source: TeacherDashboard sidebar → "Attendance Monitor" tab.
 * Destination: /api/teacher/attendance/monitor (GET, authenticated).
 * Function: Fetches present/absent student lists, renders a two-column card layout
 *           with high-contrast badges, and auto-refreshes every 30 seconds.
 */

import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { fetchTeacherMonitor } from '../services/attendanceService';

// ─── State ───────────────────────────────────────────────────────────────────

const loading = ref(false);
const hasLoaded = ref(false);
const errorMsg = ref('');
const searchQuery = ref('');
const autoRefresh = ref(true);

const presentStudents = ref([]);
const absentStudents = ref([]);
const totalStudents = ref(0);
const monitorDate = ref('');

let pollTimer = null;

// ─── Computed ────────────────────────────────────────────────────────────────

const formattedDate = computed(() => {
  if (!monitorDate.value) return '';
  const dt = new Date(monitorDate.value + 'T00:00:00');
  return dt.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

function matchesSearch(student) {
  if (!searchQuery.value.trim()) return true;
  const q = searchQuery.value.trim().toLowerCase();
  return (
    (student.full_name || '').toLowerCase().includes(q) ||
    (student.last_name || '').toLowerCase().includes(q) ||
    (student.first_name || '').toLowerCase().includes(q) ||
    (student.student_number || '').toLowerCase().includes(q) ||
    (student.grade_section || '').toLowerCase().includes(q)
  );
}

const filteredPresent = computed(() => presentStudents.value.filter(matchesSearch));
const filteredAbsent = computed(() => absentStudents.value.filter(matchesSearch));

// ─── Helpers ─────────────────────────────────────────────────────────────────

function titleCase(str) {
  if (!str || typeof str !== 'string') return '';
  return str.replace(/\w\S*/g, (t) => t.charAt(0).toUpperCase() + t.slice(1).toLowerCase());
}

// ─── Data fetching ───────────────────────────────────────────────────────────

async function loadData() {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await fetchTeacherMonitor();
    presentStudents.value = res.presentStudents || [];
    absentStudents.value = res.absentStudents || [];
    totalStudents.value = res.totalStudents || 0;
    monitorDate.value = res.date || '';
    hasLoaded.value = true;
  } catch (err) {
    errorMsg.value = err?.response?.data?.message || 'Failed to load attendance data. Please try again.';
  } finally {
    loading.value = false;
  }
}

// ─── Auto-refresh polling ────────────────────────────────────────────────────

function startPolling() {
  stopPolling();
  pollTimer = setInterval(() => {
    if (autoRefresh.value && !loading.value) {
      loadData();
    }
  }, 30000); // 30 seconds
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}

// ─── Lifecycle ───────────────────────────────────────────────────────────────

onMounted(() => {
  loadData();
  startPolling();
});

onBeforeUnmount(() => {
  stopPolling();
});

// Expose loadData so parent can trigger refresh if needed
defineExpose({ loadData });
</script>
