<template>
  <div class="flex flex-col h-screen w-full bg-slate-900 text-slate-100 overflow-hidden">
    <!-- Enhanced Header with Clock -->
    <header class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-700 bg-slate-800/80 backdrop-blur-md">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-black text-xl shadow-lg ring-2 ring-emerald-500/20">G</div>
        <div>
          <h1 class="text-lg font-black text-white leading-none">Guard Terminal</h1>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Live Attendance System</p>
        </div>
      </div>
      <div class="text-right">
        <div class="text-3xl font-black text-white tabular-nums tracking-tighter">{{ currentTime }}</div>
        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ currentDate }}</div>
      </div>
    </header>

    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
      <!-- Top Stats Row -->
      <section class="shrink-0 p-4 border-b border-slate-700 bg-slate-800/20">
        <div class="grid grid-cols-4 gap-4 max-w-7xl mx-auto">
          <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-4 flex flex-col items-center justify-center">
            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Total Today</p>
            <p class="text-3xl font-black text-white leading-none tabular-nums">{{ stats.total_today || 0 }}</p>
          </div>
          <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 flex flex-col items-center justify-center">
            <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-1">Present</p>
            <p class="text-3xl font-black text-white leading-none tabular-nums">{{ stats.present_count || 0 }}</p>
          </div>
          <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-4 flex flex-col items-center justify-center">
            <p class="text-[10px] font-black text-amber-400 uppercase tracking-widest mb-1">Late</p>
            <p class="text-3xl font-black text-white leading-none tabular-nums">{{ stats.late_count || 0 }}</p>
          </div>
          <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex flex-col items-center justify-center">
            <p class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-1">Absent</p>
            <p class="text-3xl font-black text-white leading-none tabular-nums">{{ stats.absent_count || 0 }}</p>
          </div>
        </div>
      </section>

      <div class="flex flex-1 min-h-0 overflow-hidden">
        <!-- Main Scanner Area -->
        <section class="flex-1 flex flex-col p-6 min-w-0 bg-slate-900/50">
          <div class="flex gap-6 h-full min-h-0">
            <!-- Left: Camera (Scanner) -->
            <div class="flex-1 flex flex-col gap-4">
              <div class="flex items-center justify-between">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider">Scanner View</h2>
                <div v-if="cameraStatus" class="flex items-center gap-2">
                  <div class="w-2 h-2 rounded-full" :class="cameraStatus === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'" />
                  <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">{{ cameraStatus === 'active' ? 'System Live' : 'Offline' }}</span>
                </div>
              </div>

              <div class="relative w-full aspect-video bg-black rounded-3xl overflow-hidden shadow-2xl ring-1 ring-slate-800 backdrop-blur-3xl group">
                <div id="qr-reader" ref="qrReaderEl" class="w-full h-full" />
                
                <!-- Laser Scanning Effect -->
                <div v-if="cameraStatus === 'active'" class="absolute inset-0 pointer-events-none opacity-40">
                  <div class="absolute inset-x-0 h-[2px] bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.8)] animate-scan-laser" />
                  <div class="absolute inset-0 border-[40px] border-black/30" />
                </div>

                <!-- Overlay UI -->
                <div class="absolute inset-0 pointer-events-none p-8 flex flex-col items-center justify-center">
                  <div v-if="cameraStatus === 'active'" class="w-64 h-64 border-2 border-white/20 rounded-3xl relative">
                    <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-emerald-400 rounded-tl-xl" />
                    <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-emerald-400 rounded-tr-xl" />
                    <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-emerald-400 rounded-bl-xl" />
                    <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-emerald-400 rounded-br-xl" />
                  </div>
                </div>

                <!-- Status Overlays -->
                <Transition name="fade">
                  <div v-if="unknownAlert" class="absolute inset-0 flex items-center justify-center bg-red-600/90 text-white font-black text-2xl uppercase tracking-widest text-center px-6">
                    🚨 Unknown ID - Not Found 🚨
                  </div>
                </Transition>

                <Transition name="fade">
                  <div v-if="successPulse" class="absolute inset-0 bg-emerald-500/20 animate-ping" />
                </Transition>

                <div v-if="cameraStatus === 'starting'" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/95 gap-4">
                  <div class="w-12 h-12 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin" />
                  <p class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.3em]">Hardware Init</p>
                </div>
              </div>

              <!-- Status Messages -->
              <Transition name="slide-up">
                <div v-if="scanMessage.text" 
                     class="px-4 py-3 rounded-2xl flex items-center gap-3 border shadow-lg transition-all duration-300"
                     :class="{
                       'bg-red-500/10 border-red-500/30 text-red-400': scanMessage.type === 'error',
                       'bg-emerald-500/10 border-emerald-500/30 text-emerald-400': scanMessage.type === 'success',
                       'bg-amber-500/10 border-amber-500/30 text-amber-500': scanMessage.type === 'warning'
                     }">
                  <div class="w-2 h-2 rounded-full animate-pulse" 
                       :class="{
                         'bg-red-500': scanMessage.type === 'error',
                         'bg-emerald-500': scanMessage.type === 'success',
                         'bg-amber-500': scanMessage.type === 'warning'
                       }" />
                  <span class="text-xs font-black uppercase tracking-wider">{{ scanMessage.text }}</span>
                </div>
              </Transition>
            </div>

            <!-- Right: Display Box -->
            <div class="w-80 flex flex-col gap-4">
              <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider">Scanning Result</h2>
              <div class="flex-1 rounded-3xl bg-slate-800/40 border border-slate-700/50 p-6 flex flex-col items-center justify-center text-center relative overflow-hidden group">
                <Transition name="zoom" mode="out-in">
                  <div v-if="lastScanDetails" :key="lastScanDetails.id" class="w-full flex flex-col items-center">
                    <div class="relative mb-6">
                      <img :src="getPhotoUrl(lastScanDetails.photo_path)" 
                           class="w-40 h-40 rounded-3xl object-cover border-4 border-emerald-500 shadow-2xl rotate-3 group-hover:rotate-0 transition-transform duration-500"
                           @error="(e) => e.target.src = '/images/default-avatar.png'" />
                      <div class="absolute -bottom-2 -right-2 px-4 py-1.5 rounded-xl text-[10px] font-black uppercase shadow-xl"
                           :class="lastScanDetails.status === 'late' ? 'bg-amber-500 text-amber-950' : 'bg-emerald-500 text-emerald-950'">
                        {{ lastScanDetails.status === 'late' ? 'LATE' : 'Present' }}
                      </div>
                    </div>
                    <h3 class="text-2xl font-black text-white leading-tight mb-1">{{ lastScanDetails.full_name }}</h3>
                    <p class="text-xs font-black text-emerald-400 uppercase tracking-widest mb-4">{{ lastScanDetails.grade_section }}</p>
                    <div class="w-full h-px bg-slate-700/50 my-4" />
                    <div class="flex items-center gap-2 text-slate-400">
                      <Clock class="w-4 h-4" />
                      <span class="text-sm font-black tabular-nums">{{ formatTime(new Date().toISOString()) }}</span>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center opacity-40">
                    <div class="w-24 h-24 rounded-3xl border-2 border-dashed border-slate-600 mb-4 flex items-center justify-center">
                      <User class="w-8 h-8 text-slate-600" />
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Waiting for Scan</p>
                  </div>
                </Transition>
              </div>
            </div>
          </div>
        </section>

        <!-- Attendance Feed Section -->
        <section class="w-[420px] shrink-0 bg-slate-800/30 backdrop-blur-3xl border-l border-slate-700 flex flex-col overflow-hidden">
          <div class="p-6 border-b border-slate-700 flex items-center justify-between">
            <h2 class="text-sm font-black text-white uppercase tracking-[0.2em]">Recent Activity</h2>
            <div class="px-2 py-0.5 rounded-md bg-emerald-500/10 border border-emerald-500/30 text-[9px] font-black text-emerald-500 uppercase">Live Feed</div>
          </div>

          <div class="flex-1 overflow-auto scrollbar-hide p-4 space-y-3">
            <TransitionGroup name="list">
              <div v-for="row in attendanceList" :key="row.id" 
                   class="bg-slate-800/40 hover:bg-slate-700/40 p-3 rounded-2xl border border-slate-700/50 transition-all flex items-center gap-4 group">
                <div class="relative shrink-0">
                  <img :src="getPhotoUrl(row.photo_path)" 
                       class="w-12 h-12 rounded-xl object-cover border-2 border-slate-700 group-hover:border-emerald-500/50 transition-all"
                       @error="(e) => e.target.src = '/images/default-avatar.png'" />
                  <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-slate-800" 
                       :class="row.status === 'late' ? 'bg-amber-500' : 'bg-emerald-500'" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-black text-slate-100 truncate leading-none mb-1">{{ row.full_name }}</p>
                  <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest truncate">{{ row.grade_section }}</p>
                </div>
                <div class="text-right shrink-0">
                  <p class="text-xs font-black text-white tabular-nums">{{ formatTime(row.time_in) }}</p>
                  <p class="text-[8px] font-black text-slate-500 uppercase">Arrived</p>
                </div>
              </div>
            </TransitionGroup>

            <div v-if="attendanceList.length === 0" class="py-20 text-center">
              <div class="w-12 h-12 rounded-2xl border-2 border-slate-700 mx-auto mb-4 flex items-center justify-center animate-pulse">
                <Search class="w-5 h-5 text-slate-700" />
              </div>
              <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Scanning History is Empty</p>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';
import { Clock, User, Search, Play, Pause, RefreshCw } from 'lucide-vue-next';
import { scanAttendancePublic, fetchRecentAttendancePublic, fetchGuardStatsPublic } from '../services/attendanceService';

const DEBOUNCE_MS = 2500;
const REFRESH_INTERVAL_MS = 8000;
const AUTO_RETRY_DELAY_S = 8;

const qrReaderEl = ref(null);
const scanner = ref(null);
const cameraStatus = ref('');
const successPulse = ref(false);
const scanMessage = ref({ text: '', type: 'success' });
const attendanceList = ref([]);
const loadingRecent = ref(false);
const lastScannedAt = ref(0);
const lastScannedValue = ref('');
const starting = ref(false);
const stopping = ref(false);
const autoRetryCountdown = ref(0);

// UI States
const currentTime = ref('');
const currentDate = ref('');
const stats = ref({ total_today: 0, present_count: 0, late_count: 0, absent_count: 0 });
const lastScanDetails = ref(null);
const showHighlight = ref(false);
const unknownAlert = ref(false);

let clockInterval = null;
let highlightTimer = null;
let unknownTimer = null;
let refreshTimer = null;
let autoRetryTimer = null;
let countdownInterval = null;
let destroyed = false;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function formatTime(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
}

function updateClock() {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
  currentDate.value = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
}

function getInitials(first, last) {
  return ((first?.[0] || '') + (last?.[0] || '')).toUpperCase();
}

function getInitialsColor(name) {
  if (!name) return '#475569';
  const colors = ['#e11d48', '#db2777', '#c026d3', '#9333ea', '#7c3aed', '#4f46e5', '#3b82f6', '#0ea5e9', '#06b6d4', '#0d9488', '#059669', '#16a34a', '#65a30d', '#ca8a04', '#d97706', '#ea580c'];
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  return colors[Math.abs(hash) % colors.length];
}

function playBeep(type = 'success') {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    const audioCtx = new AudioContext();
    
    const playNote = (freq, start, duration, typeNode = 'sine') => {
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.type = typeNode;
      osc.frequency.setValueAtTime(freq, audioCtx.currentTime + start);
      gain.gain.setValueAtTime(0.1, audioCtx.currentTime + start);
      gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + start + duration);
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.start(audioCtx.currentTime + start);
      osc.stop(audioCtx.currentTime + start + duration);
    };

    if (type === 'success') {
      playNote(880, 0, 0.15, 'sine');
    } else if (type === 'warning') {
      // Double beep for already scanned
      playNote(600, 0, 0.1, 'sine');
      playNote(600, 0.15, 0.1, 'sine');
    } else {
      // Error beep
      playNote(220, 0, 0.4, 'sawtooth');
    }
  } catch (e) {}
}

function getPhotoUrl(path) {
  if (!path) return '/images/default-avatar.png';
  if (typeof path === 'string' && path.startsWith('http')) return path;
  const cleanPath = String(path).replace(/^\/?storage\//, '').replace(/^\//, '');
  return '/storage/' + cleanPath;
}

function triggerHighlight(student, attendance) {
  lastScanDetails.value = { ...student, status: attendance.status, full_name: student.full_name || `${student.first_name} ${student.last_name}` };
  showHighlight.value = true;
  if (highlightTimer) clearTimeout(highlightTimer);
  highlightTimer = setTimeout(() => { showHighlight.value = false; }, 3500);
}

function triggerUnknownAlert() {
  unknownAlert.value = true;
  if (unknownTimer) clearTimeout(unknownTimer);
  unknownTimer = setTimeout(() => { unknownAlert.value = false; }, 3000);
}

function showMessage(text, type = 'success', duration = 4000) {
  scanMessage.value = { text, type };
  if (duration > 0) setTimeout(() => { if (scanMessage.value.text === text) scanMessage.value = { text: '', type: 'success' }; }, duration);
}

function triggerSuccessPulse() {
  successPulse.value = true;
  setTimeout(() => { successPulse.value = false; }, 600);
}

function isDebounceLocked(value) {
  const now = Date.now();
  return value === lastScannedValue.value && (now - lastScannedAt.value) < DEBOUNCE_MS;
}

function prependAttendance(student, attendance) {
  const fullName = student.full_name || `${student.first_name} ${student.last_name}`;
  attendanceList.value = [{
    id: attendance.id || Date.now(),
    full_name: fullName,
    first_name: student.first_name,
    last_name: student.last_name,
    grade_section: student.grade_section || '—',
    time_in: attendance.scanned_at || new Date().toISOString(),
    status: attendance.status || 'on_time',
    photo_path: student.photo_path
  }, ...attendanceList.value].slice(0, 50);
}

// ─── Scan Logic ──────────────────────────────────────────────────────────────

async function onScanSuccess(decodedText) {
  let raw = String(decodedText).trim();
  if (!raw) return;
  
  // Extract LRN from formats like "LRN: 1234567890" or dirty reads
  const lrnMatch = raw.match(/LRN:\s*([\w\d-]+)/i);
  if (lrnMatch && lrnMatch[1]) {
    raw = lrnMatch[1];
  } else if (raw.includes('\n')) {
    const numericMatch = raw.match(/\d{5,}/);
    if (numericMatch) raw = numericMatch[0];
  }

  if (isDebounceLocked(raw)) return;

  lastScannedValue.value = raw;
  lastScannedAt.value = Date.now();

  try {
    const res = await scanAttendancePublic(raw);
    
    if (res && res.status !== 'success') {
      if (res.status === 'already_scanned') {
        playBeep('warning');
        showMessage(res.message, 'warning');
        return;
      }
      playBeep('error');
      if (res.status === 'invalid') triggerUnknownAlert();
      showMessage(res.message || 'Scan failed.', 'error');
      return;
    }
    
    const { student, attendance } = res;
    playBeep('success');
    triggerSuccessPulse();
    triggerHighlight(student, attendance);
    if (res.stats) stats.value = res.stats;
    else refreshStats();
    prependAttendance(student, attendance);
    showMessage(`${student.first_name} recorded.`, 'success');
  } catch (err) {
    playBeep('error');
    const status = err.response?.status;
    if (status === 404) triggerUnknownAlert();
    showMessage(err.response?.data?.message || 'Scan failed.', 'error');
  }
}

// ─── Data Persistence ─────────────────────────────────────────────────────────

async function loadRecent() {
  if (loadingRecent.value) return;
  loadingRecent.value = true;
  try {
    const res = await fetchRecentAttendancePublic();
    attendanceList.value = (res.data || []).map(row => ({
      ...row,
      time_in: row.time_in,
    }));
  } catch (e) {
    console.error('List load failed', e);
  } finally {
    loadingRecent.value = false;
  }
}

async function refreshStats() {
  try {
    const data = await fetchGuardStatsPublic();
    if (data) stats.value = data;
  } catch (e) {}
}

function startRefreshTimer() {
  stopRefreshTimer();
  refreshTimer = setInterval(() => {
    loadRecent();
    refreshStats();
  }, REFRESH_INTERVAL_MS);
}

function stopRefreshTimer() {
  if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; }
}

// ─── Camera Management ────────────────────────────────────────────────────────

async function forceReleaseCameraStream() {
  try {
    if (!navigator.mediaDevices?.getUserMedia) return;
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    stream.getTracks().forEach(t => t.stop());
    await new Promise(r => setTimeout(r, 400));
  } catch (_) {}
}

async function startCamera() {
  if (starting.value || stopping.value || destroyed) return;
  starting.value = true;
  clearAutoRetry();
  cameraStatus.value = 'starting';

  await nextTick();
  const container = document.getElementById('qr-reader');
  if (container) container.innerHTML = '';
  
  await forceReleaseCameraStream();

  const html5Qr = new Html5Qrcode('qr-reader', { verbose: false, formatsToSupport: [0] });
  scanner.value = html5Qr;

  const config = {
    fps: 15,
    aspectRatio: 1.0,
    disableFlip: false,
    useBarCodeDetectorIfSupported: true,
    videoConstraints: { width: { ideal: 640 }, height: { ideal: 480 } }
  };

  let started = false;
  for (const constraint of [{ facingMode: 'environment' }, { facingMode: 'user' }]) {
    try {
      await html5Qr.start(constraint, config, onScanSuccess, () => {});
      started = true;
      break;
    } catch (e) {
      await new Promise(r => setTimeout(r, 400));
    }
  }

  if (started && !destroyed) {
    cameraStatus.value = 'active';
    loadRecent();
    refreshStats();
    startRefreshTimer();
  } else {
    cameraStatus.value = 'error';
    scheduleAutoRetry();
  }
  starting.value = false;
}

async function stopScannerAndRelease() {
  if (stopping.value) return;
  stopping.value = true;
  try {
    if (scanner.value) {
      if (scanner.value.isScanning) await scanner.value.stop();
      await scanner.value.clear();
      scanner.value = null;
    }
  } catch (_) {}
  finally { stopping.value = false; }
}

function scheduleAutoRetry() {
  clearAutoRetry();
  autoRetryCountdown.value = AUTO_RETRY_DELAY_S;
  countdownInterval = setInterval(() => { autoRetryCountdown.value = Math.max(0, autoRetryCountdown.value - 1); }, 1000);
  autoRetryTimer = setTimeout(() => { if (cameraStatus.value === 'error') startCamera(); }, AUTO_RETRY_DELAY_S * 1000);
}

function clearAutoRetry() {
  if (autoRetryTimer) clearTimeout(autoRetryTimer);
  if (countdownInterval) clearInterval(countdownInterval);
  autoRetryCountdown.value = 0;
}

function manualRetry() { startCamera(); }

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  destroyed = false;
  updateClock();
  clockInterval = setInterval(updateClock, 1000);
  startCamera();
});

onUnmounted(async () => {
  destroyed = true;
  clearInterval(clockInterval);
  stopRefreshTimer();
  await stopScannerAndRelease();
});
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

.slide-up-enter-active, .slide-up-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from { opacity: 0; transform: translateY(20px); }
.slide-up-leave-to { opacity: 0; transform: translateY(10px); }

.list-enter-active, .list-leave-active { transition: all 0.5s ease; }
.list-enter-from { opacity: 0; transform: translateX(-30px); }
.list-leave-to { opacity: 0; transform: translateX(30px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.zoom-enter-active, .zoom-leave-active { transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
.zoom-enter-from { opacity: 0; transform: scale(0.9) translateY(10px); }
.zoom-leave-to { opacity: 0; transform: scale(1.05); }

@keyframes scan-laser {
  0% { top: 0; }
  50% { top: 100%; }
  100% { top: 0; }
}
.animate-scan-laser {
  animation: scan-laser 3s linear infinite;
}
</style>
