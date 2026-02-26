<template>
  <div class="flex flex-col h-screen w-full bg-slate-900 text-slate-100 overflow-hidden">
    <header class="shrink-0 flex items-center justify-between px-4 py-3 border-b border-slate-700 bg-slate-800/80">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold">G</div>
        <div>
          <p class="text-xs text-slate-400">ScanUp · Live Attendance</p>
        </div>
      </div>
      <button
        type="button"
        class="rounded-lg px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-600 hover:border-red-500/50 hover:text-red-300 transition flex items-center gap-2"
        @click="logout"
      >
        <span aria-hidden="true">⎋</span>
        Log out
      </button>
    </header>
    <div class="flex flex-1 min-h-0 overflow-hidden">
    <section class="flex flex-col flex-1 min-w-0 p-4 border-r border-slate-700">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-semibold text-white">Scanner</h2>
        <span
          v-if="cameraStatus"
          class="text-xs px-2 py-1 rounded"
          :class="{
            'bg-emerald-900/50 text-emerald-300': cameraStatus === 'active',
            'bg-amber-900/50 text-amber-300': cameraStatus === 'starting',
            'bg-red-900/50 text-red-300': cameraStatus === 'error',
          }"
        >
          {{ cameraStatus === 'active' ? 'Live' : cameraStatus }}
        </span>
      </div>
      <div class="relative flex-1 min-h-[280px] min-w-0 flex items-center justify-center bg-black rounded-lg overflow-hidden">
        <div
          id="qr-reader"
          ref="qrReaderEl"
          class="w-full h-full min-h-[240px] max-h-full max-w-full"
        />
        <!-- Scan overlay corners -->
        <div
          class="absolute inset-0 pointer-events-none flex items-center justify-center"
          aria-hidden="true"
        >
          <div class="relative w-[min(60vw,60vh)] aspect-square max-w-full" v-if="cameraStatus === 'active'">
            <div
              class="absolute inset-0 border-2 border-white/60 rounded-lg"
              style="box-shadow: 0 0 0 9999px rgba(0,0,0,0.30);"
            />
            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-emerald-400 rounded-tl-lg" />
            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-emerald-400 rounded-tr-lg" />
            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-emerald-400 rounded-bl-lg" />
            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-emerald-400 rounded-br-lg" />
          </div>
        </div>
        <!-- Success pulse -->
        <div
          v-if="successPulse"
          class="absolute inset-0 flex items-center justify-center pointer-events-none bg-emerald-500/20 animate-pulse rounded-lg"
          style="animation-duration: 0.6s;"
        />
        <!-- Camera init overlay -->
        <div
          v-if="cameraStatus === 'starting'"
          class="absolute inset-0 flex flex-col items-center justify-center bg-black/70 rounded-lg gap-3"
        >
          <svg class="animate-spin h-10 w-10 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          <p class="text-sm text-slate-300">Starting camera…</p>
        </div>
        <!-- Error overlay with auto-retry countdown -->
        <div
          v-if="cameraStatus === 'error'"
          class="absolute inset-0 flex flex-col items-center justify-center bg-black/80 rounded-lg gap-4 p-6"
        >
          <svg class="h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.82V15.18a1 1 0 01-1.447.91L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
          </svg>
          <p class="text-sm text-red-300 text-center">Camera unavailable</p>
          <p v-if="autoRetryCountdown > 0" class="text-xs text-slate-400 text-center">
            Auto-retry in <span class="font-bold text-amber-300">{{ autoRetryCountdown }}s</span>…
          </p>
          <p v-else class="text-xs text-slate-400 text-center">Retrying…</p>
          <button
            type="button"
            class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600 transition disabled:opacity-50"
            :disabled="starting || stopping"
            @click="manualRetry"
          >
            Retry Now
          </button>
        </div>
      </div>
      <div
        v-if="scanMessage.text"
        class="mt-2 text-sm px-3 py-2 rounded"
        :class="scanMessage.isError ? 'bg-red-900/50 text-red-200' : 'bg-emerald-900/50 text-emerald-200'"
      >
        {{ scanMessage.text }}
      </div>
    </section>
    <section class="flex flex-col w-[420px] shrink-0 border-l border-slate-700 bg-slate-800/50">
      <div class="p-3 border-b border-slate-700">
        <h2 class="text-lg font-semibold text-white">Live Attendance</h2>
        <p class="text-xs text-slate-400 mt-0.5">Most recent first</p>
      </div>
      <div class="flex-1 min-h-0 overflow-auto">
        <table class="w-full text-sm border-collapse">
          <thead class="sticky top-0 bg-slate-800 z-10">
            <tr class="text-left text-slate-400 border-b border-slate-600">
              <th class="w-10 py-2 px-2 font-medium">#</th>
              <th class="py-2 px-2 font-medium">Full Name</th>
              <th class="py-2 px-2 font-medium">Grade/Section</th>
              <th class="py-2 px-2 font-medium">Time In</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, index) in attendanceList"
              :key="row.id"
              class="border-b border-slate-700/80 hover:bg-slate-700/30"
            >
              <td class="py-2 px-2 text-slate-300">{{ index + 1 }}</td>
              <td class="py-2 px-2 font-medium text-white">{{ row.full_name }}</td>
              <td class="py-2 px-2 text-slate-300">{{ row.grade_section }}</td>
              <td class="py-2 px-2 text-slate-400 tabular-nums">{{ formatTime(row.time_in) }}</td>
            </tr>
            <tr v-if="attendanceList.length === 0 && !loadingRecent">
              <td colspan="4" class="py-8 px-4 text-center text-slate-500">No attendance yet</td>
            </tr>
            <tr v-if="loadingRecent && attendanceList.length === 0">
              <td colspan="4" class="py-8 px-4 text-center text-slate-500">Loading…</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { Html5Qrcode } from 'html5-qrcode';
import { scanAttendancePublic, fetchRecentAttendancePublic } from '../services/attendanceService';
import { setStoredToken, getStoredToken } from '../router';

const DEBOUNCE_MS = 2000;
const REFRESH_INTERVAL_MS = 5000;
const AUTO_RETRY_DELAY_S = 8; // seconds before auto-retry on error

const qrReaderEl = ref(null);
const scanner = ref(null);
const cameraStatus = ref('');
const successPulse = ref(false);
const scanMessage = ref({ text: '', isError: false });
const attendanceList = ref([]);
const loadingRecent = ref(false);
const lastScannedAt = ref(0);
const lastScannedValue = ref('');
const starting = ref(false);
const stopping = ref(false);
const autoRetryCountdown = ref(0);

let refreshTimer = null;
let autoRetryTimer = null;
let countdownInterval = null;
let destroyed = false;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function formatTime(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function showMessage(text, isError = false, duration = 4000) {
  scanMessage.value = { text, isError };
  if (duration > 0) {
    setTimeout(() => { scanMessage.value = { text: '', isError: false }; }, duration);
  }
}

function triggerSuccessPulse() {
  successPulse.value = true;
  setTimeout(() => { successPulse.value = false; }, 600);
}

function isDebounceLocked(value) {
  const now = Date.now();
  return value === lastScannedValue.value && now - lastScannedAt.value < DEBOUNCE_MS;
}

function prependAttendance(student, attendance) {
  const fullName = (student && (student.full_name || [student.first_name, student.last_name].filter(Boolean).join(' '))) || '—';
  const gradeSection = (student && (student.grade_section ?? '—')) || '—';
  const timeIn = (attendance && (attendance.scanned_at || attendance.time_in)) || new Date().toISOString();
  attendanceList.value = [
    {
      id: (attendance && attendance.id) || Date.now(),
      full_name: fullName,
      grade_section: gradeSection,
      time_in: timeIn,
    },
    ...attendanceList.value,
  ];
}

// ─── Scan handler ─────────────────────────────────────────────────────────────

async function onScanSuccess(decodedText) {
  let raw = String(decodedText).trim();
  if (!raw) return;
  
  // Robustly extract LRN out of legacy QR string formats if printed on card
  const lrnMatch = raw.match(/LRN:\s*([\w\d-]+)/i);
  if (lrnMatch && lrnMatch[1]) {
    raw = lrnMatch[1];
  } else if (raw.includes('\n')) {
    // Grab the first line or try to find a number if the QR code is bloated
    const numericMatch = raw.match(/\d{5,}/);
    if (numericMatch) raw = numericMatch[0];
  }

  if (isDebounceLocked(raw)) {
    showMessage('Duplicate scan — please wait.', true);
    return;
  }
  lastScannedValue.value = raw;
  lastScannedAt.value = Date.now();
  try {
    const res = await scanAttendancePublic(raw);
    
    // Check if the server explicitly returned an error/duplicate status (but HTTP 200)
    if (res && res.status && res.status !== 'success') {
      showMessage(res.message || 'Scan failed.', true);
      return;
    }
    
    const student = res && res.student;
    const attendance = res && res.attendance;
    triggerSuccessPulse();
    const name = student && (student.full_name || [student.first_name, student.last_name].filter(Boolean).join(' '));
    showMessage(name ? `✓ ${name} – recorded.` : '✓ Recorded.', false);
    if (student && attendance) prependAttendance(student, attendance);
  } catch (err) {
    const status = err.response?.status;
    const data = err.response?.data;
    const msg = data?.message || (status === 404 ? 'Student not found.' : status === 422 ? 'Already scanned today.' : 'Scan failed.');
    showMessage(msg, true);
  }
}

// ─── Attendance list ──────────────────────────────────────────────────────────

async function loadRecent() {
  loadingRecent.value = true;
  try {
    const res = await fetchRecentAttendancePublic();
    attendanceList.value = (res.data || []).map((row) => ({
      id: row.id,
      full_name: row.full_name || '—',
      grade_section: row.grade_section || '—',
      time_in: row.time_in,
    }));
  } catch {
    attendanceList.value = [];
  } finally {
    loadingRecent.value = false;
  }
}

function startRefreshTimer() {
  if (refreshTimer) clearInterval(refreshTimer);
  refreshTimer = setInterval(loadRecent, REFRESH_INTERVAL_MS);
}

function stopRefreshTimer() {
  if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; }
}

// ─── Auto-retry on error ──────────────────────────────────────────────────────

function clearAutoRetry() {
  if (autoRetryTimer) { clearTimeout(autoRetryTimer); autoRetryTimer = null; }
  if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
  autoRetryCountdown.value = 0;
}

function scheduleAutoRetry() {
  clearAutoRetry();
  if (destroyed) return;
  autoRetryCountdown.value = AUTO_RETRY_DELAY_S;
  countdownInterval = setInterval(() => {
    autoRetryCountdown.value = Math.max(0, autoRetryCountdown.value - 1);
  }, 1000);
  autoRetryTimer = setTimeout(async () => {
    clearAutoRetry();
    if (!destroyed && cameraStatus.value === 'error') {
      await startCamera();
    }
  }, AUTO_RETRY_DELAY_S * 1000);
}

// ─── Force-release any camera stream held by the browser ─────────────────────
// This "steals" the camera from whatever has it, then immediately releases it.
// After this, html5-qrcode can open it cleanly.

async function forceReleaseCameraStream() {
  try {
    if (!navigator.mediaDevices?.getUserMedia) return;
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    stream.getTracks().forEach((track) => track.stop());
    await new Promise((r) => setTimeout(r, 400));
  } catch (_) {
    // If we can't get it either, just wait
    await new Promise((r) => setTimeout(r, 400));
  }
}

// ─── Scanner lifecycle ────────────────────────────────────────────────────────

async function stopScannerAndRelease() {
  if (stopping.value) {
    // Wait for any in-progress stop
    await new Promise((r) => {
      const poll = setInterval(() => {
        if (!stopping.value) { clearInterval(poll); r(); }
      }, 50);
    });
    return;
  }
  stopping.value = true;
  try {
    if (scanner.value) {
      try { if (scanner.value.isScanning) await scanner.value.stop(); } catch (_) {}
      try { await scanner.value.clear(); } catch (_) {}
      scanner.value = null;
    }
    await new Promise((r) => setTimeout(r, 500));
  } finally {
    stopping.value = false;
  }
}

async function startCamera() {
  if (starting.value || stopping.value || destroyed) return;
  starting.value = true;
  clearAutoRetry();
  cameraStatus.value = 'starting';
  scanMessage.value = { text: '', isError: false };

  await nextTick();

  // 1) Stop any existing scanner
  if (scanner.value) {
    await stopScannerAndRelease();
  }

  // 2) Wipe the DOM container
  const container = document.getElementById('qr-reader');
  if (!container) {
    cameraStatus.value = 'error';
    starting.value = false;
    scheduleAutoRetry();
    return;
  }
  container.innerHTML = '';
  await nextTick();

  // 3) Force-release any existing camera stream (handles "Device in use")
  await forceReleaseCameraStream();

  // 4) Create fresh instance
  let html5Qr;
  try {
    html5Qr = new Html5Qrcode('qr-reader', { verbose: false, formatsToSupport: [0] });
  } catch (e) {
    cameraStatus.value = 'error';
    starting.value = false;
    scheduleAutoRetry();
    return;
  }
  scanner.value = html5Qr;

  const config = {
    fps: 15, // Reverted to 15 to prevent CPU throttling on low-end devices
    // Removed qrbox entirely to scan the ENTIRE viewport instantly
    aspectRatio: 1.0,
    disableFlip: false, // Allows reading inverted codes
    useBarCodeDetectorIfSupported: true,
    videoConstraints: {
      width: { ideal: 640 },
      height: { ideal: 480 },
    }
  };

  const onSuccess = (text) => onScanSuccess(text);
  const onFailure = () => {};

  // 5) Try environment camera, then user camera
  let started = false;
  let lastErr = null;

  for (const constraint of [{ facingMode: 'environment' }, { facingMode: 'user' }]) {
    if (destroyed) break;
    try {
      await html5Qr.start(constraint, config, onSuccess, onFailure);
      started = true;
      break;
    } catch (err) {
      lastErr = err;
      // If "device in use", try force-releasing again before next attempt
      const msg = err && (err.message || String(err));
      if (/NotReadableError|device in use/i.test(msg)) {
        await forceReleaseCameraStream();
      } else {
        await new Promise((r) => setTimeout(r, 500));
      }
    }
  }

  if (started && !destroyed) {
    cameraStatus.value = 'active';
    await loadRecent();
    startRefreshTimer();
  } else {
    cameraStatus.value = 'error';
    try { await stopScannerAndRelease(); } catch (_) {}
    scheduleAutoRetry();
  }

  starting.value = false;
}

// ─── Actions ──────────────────────────────────────────────────────────────────

async function manualRetry() {
  if (starting.value || stopping.value) return;
  clearAutoRetry();
  stopRefreshTimer();
  await startCamera();
}

async function logout() {
  const token = getStoredToken();
  if (token) {
    try {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      await axios.post('/api/logout');
    } catch (_) {}
    setStoredToken(null);
  }
  window.location.href = '/login';
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  destroyed = false;
  startCamera();
});

onUnmounted(async () => {
  destroyed = true;
  clearAutoRetry();
  stopRefreshTimer();
  await stopScannerAndRelease();
});
</script>
