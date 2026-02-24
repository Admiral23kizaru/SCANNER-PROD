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
          :class="cameraStatus === 'active' ? 'bg-emerald-900/50 text-emerald-300' : 'bg-amber-900/50 text-amber-300'"
        >
          {{ cameraStatus === 'active' ? 'Live' : cameraStatus }}
        </span>
      </div>
      <div class="relative flex-1 min-h-[280px] min-w-0 flex items-center justify-center bg-black rounded-lg overflow-hidden">
        <div
          id="qr-reader"
          ref="qrReaderEl"
          class="w-full h-full min-h-[240px] max-h-full max-w-full [&>div]:!max-h-full [&>div]:!min-h-[200px] [&>div]:!w-full [&>div]:!max-w-full [& video]:!object-contain [& video]:!max-h-full"
        />
        <div
          class="absolute inset-0 pointer-events-none flex items-center justify-center"
          aria-hidden="true"
        >
          <div class="relative w-[min(80vw,80vh)] aspect-square max-w-full">
            <div
              class="absolute inset-0 border-4 border-white/80 rounded-lg"
              style="box-shadow: 0 0 0 9999px rgba(0,0,0,0.35);"
            />
            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-white rounded-tl-lg" />
            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-white rounded-tr-lg" />
            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-white rounded-bl-lg" />
            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-white rounded-br-lg" />
          </div>
        </div>
        <div
          v-if="successPulse"
          class="absolute inset-0 flex items-center justify-center pointer-events-none bg-emerald-500/20 animate-pulse rounded-lg"
          style="animation-duration: 0.6s;"
        />
      </div>
      <div
        v-if="scanMessage.text"
        class="mt-2 text-sm px-3 py-2 rounded"
        :class="scanMessage.isError ? 'bg-red-900/50 text-red-200' : 'bg-emerald-900/50 text-emerald-200'"
      >
        {{ scanMessage.text }}
      </div>
      <div
        v-if="cameraStatus === 'error'"
        class="mt-3 flex flex-col gap-2"
      >
        <p class="text-xs text-slate-400">
          Close other apps or tabs using the camera, then click Retry.
        </p>
        <button
          type="button"
          class="w-full rounded-md bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-500 disabled:opacity-50"
          :disabled="retrying"
          @click="retryCamera"
        >
          {{ retrying ? 'Starting camera…' : 'Retry camera' }}
        </button>
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

const qrReaderEl = ref(null);
const scanner = ref(null);
const cameraStatus = ref('');
const successPulse = ref(false);
const scanMessage = ref({ text: '', isError: false });
const attendanceList = ref([]);
const loadingRecent = ref(false);
const lastScannedAt = ref(0);
const lastScannedValue = ref('');
const retrying = ref(false);
const starting = ref(false);
const stopping = ref(false);

let refreshTimer = null;

function formatTime(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function showMessage(text, isError = false) {
  scanMessage.value = { text, isError };
  const t = setTimeout(() => {
    scanMessage.value = { text: '', isError: false };
  }, 4000);
  return () => clearTimeout(t);
}

function triggerSuccessPulse() {
  successPulse.value = true;
  setTimeout(() => {
    successPulse.value = false;
  }, 600);
}

function isDebounceLocked(value) {
  const now = Date.now();
  if (value === lastScannedValue.value && now - lastScannedAt.value < DEBOUNCE_MS) {
    return true;
  }
  return false;
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

async function onScanSuccess(decodedText) {
  const raw = String(decodedText).trim();
  if (!raw) return;

  if (isDebounceLocked(raw)) {
    showMessage('Duplicate scan. Please wait before scanning again.', true);
    return;
  }

  lastScannedValue.value = raw;
  lastScannedAt.value = Date.now();

  try {
    const res = await scanAttendancePublic(raw);
    const student = res && res.student;
    const attendance = res && res.attendance;
    triggerSuccessPulse();
    const name = student && (student.full_name || [student.first_name, student.last_name].filter(Boolean).join(' '));
    showMessage(name ? `${name} – recorded.` : 'Recorded.', false);
    if (student && attendance) {
      prependAttendance(student, attendance);
    }
  } catch (err) {
    const status = err.response?.status;
    const data = err.response?.data;
    const msg = data?.message || (status === 404 ? 'Student not found.' : status === 422 ? 'Invalid or duplicate scan.' : 'Scan failed.');
    showMessage(msg, true);
  }
}

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
  refreshTimer = setInterval(loadRecent, REFRESH_INTERVAL_MS);
}

function stopRefreshTimer() {
  if (refreshTimer) {
    clearInterval(refreshTimer);
    refreshTimer = null;
  }
}

async function stopScannerAndRelease() {
  if (stopping.value) return;
  stopping.value = true;
  try {
    if (scanner.value) {
      try {
        if (scanner.value.isScanning) {
          await scanner.value.stop();
        }
      } catch (_) {}
      try {
        await scanner.value.clear();
      } catch (_) {}
      scanner.value = null;
    }
    await new Promise((r) => setTimeout(r, 250));
  } finally {
    stopping.value = false;
  }
}

async function startCamera() {
  if (starting.value || stopping.value) return;
  starting.value = true;
  retrying.value = false;
  await nextTick();
  const el = document.getElementById('qr-reader');
  if (!el) {
    cameraStatus.value = 'error';
    showMessage('Scanner element not found.', true);
    starting.value = false;
    return;
  }

  if (scanner.value) {
    await stopScannerAndRelease();
  }

  const html5Qr = new Html5Qrcode('qr-reader', {
    verbose: false,
    formatsToSupport: [0],
  });

  scanner.value = html5Qr;
  cameraStatus.value = 'starting';

  const config = {
    fps: 25,
    qrbox: { width: 300, height: 300 },
    aspectRatio: 1.0,
    disableFlip: false,
    rememberLastUsedCamera: true,
    experimentalFeatures: {
      useBarCodeDetectorIfSupported: true,
    },
  };

  const tryStart = async (constraints) => {
    return html5Qr.start(
      constraints,
      config,
      (decodedText) => onScanSuccess(decodedText),
      () => {}
    );
  };

  try {
    try {
      await tryStart({
        facingMode: 'environment',
        advanced: [{ focusMode: 'continuous' }],
      });
    } catch (envErr) {
      await tryStart({
        facingMode: 'user',
        advanced: [{ focusMode: 'continuous' }],
      });
    }
    cameraStatus.value = 'active';
  } catch (e) {
    cameraStatus.value = 'error';
    await stopScannerAndRelease();
    const errMsg = e && (e.message || String(e));
    const isInUse = /device in use|NotReadableError/i.test(errMsg);
    const hint = isInUse ? ' Close other apps/tabs using the camera, then click Retry camera.' : '';
    const permHint = errMsg.toLowerCase().includes('permission') ? ' Allow camera access and refresh.' : '';
    const insecure = errMsg.toLowerCase().includes('secure') || errMsg.toLowerCase().includes('insecure') ? ' Use HTTPS or localhost.' : '';
    showMessage(`Camera failed: ${errMsg || 'Unknown error'}${hint}${permHint}${insecure}`, true);
  }

  await loadRecent();
  startRefreshTimer();
  starting.value = false;
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

async function retryCamera() {
  if (starting.value || stopping.value) return;
  retrying.value = true;
  scanMessage.value = { text: '', isError: false };
  stopRefreshTimer();
  await stopScannerAndRelease();
  const container = document.getElementById('qr-reader');
  if (container) {
    container.innerHTML = '';
  }
  await nextTick();
  await startCamera();
  retrying.value = false;
}

onMounted(() => {
  startCamera();
});

onUnmounted(async () => {
  stopRefreshTimer();
  await stopScannerAndRelease();
});
</script>
