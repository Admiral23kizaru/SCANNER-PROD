<template>
  <!--
    GuardScanner.vue — Public Guard Terminal Component
    ===================================================
    Displays a full-screen QR scanning interface for school gate guards.
    All reactive logic is delegated to the useScanner() composable.

    Layout:
      ┌─────────────────────────────────────────────────┐
      │ Header: Logo, Title, Live Clock                 │
      ├─────────────────────────────────────────────────┤
      │ Stats Row: Total / Present / Late / Absent      │
      ├──────────────────────┬──────────────────────────┤
      │ Camera View          │ Recent Activity Feed     │
      │ (QR Reader + Overlay)│ (ScrollList, max 50)     │
      │ Scan Result Card     │                          │
      └──────────────────────┴──────────────────────────┘
  -->
  <div class="flex flex-col h-screen w-full bg-slate-900 text-slate-100 overflow-hidden">
    <!-- ── Header ───────────────────────────────────────────────────────── -->
    <header class="shrink-0 flex items-center justify-between px-6 py-4 border-b border-slate-700 bg-slate-800/80 backdrop-blur-md">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-black text-xl shadow-lg ring-2 ring-emerald-500/20">G</div>
        <div>
          <h1 class="text-lg font-black text-white leading-none">Guard Terminal</h1>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Live Attendance System</p>
        </div>
      </div>
      <!-- Live clock — updated every second by useScanner -->
      <div class="text-right">
        <div class="text-3xl font-black text-white tabular-nums tracking-tighter">{{ currentTime }}</div>
        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ currentDate }}</div>
      </div>
    </header>

    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
      <!-- ── Stats Row ──────────────────────────────────────────────────── -->
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
        <!-- ── Main Scanner Area ───────────────────────────────────────── -->
        <section class="flex-1 flex flex-col p-6 min-w-0 bg-slate-900/50">
          <div class="flex gap-6 h-full min-h-0">

            <!-- Left: Camera view (html5-qrcode mounts into #qr-reader) -->
            <div class="flex-1 flex flex-col gap-4">
              <div class="flex items-center justify-between">
                <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider">Scanner View</h2>
                <!-- Camera status indicator dot -->
                <div v-if="cameraStatus" class="flex items-center gap-2">
                  <div class="w-2 h-2 rounded-full"
                       :class="cameraStatus === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'" />
                  <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">
                    {{ cameraStatus === 'active' ? 'System Live' : 'Offline' }}
                  </span>
                </div>
              </div>

              <!-- Scanner viewport: html5-qrcode renders the <video> inside #qr-reader -->
              <div class="relative w-full aspect-video bg-black rounded-3xl overflow-hidden shadow-2xl ring-1 ring-slate-800 backdrop-blur-3xl group">
                <div id="qr-reader" ref="qrReaderEl" class="w-full h-full" />

                <!-- Animated laser line overlay (decorative) -->
                <div v-if="cameraStatus === 'active'" class="absolute inset-0 pointer-events-none opacity-40">
                  <div class="absolute inset-x-0 h-[2px] bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.8)] animate-scan-laser" />
                  <div class="absolute inset-0 border-[40px] border-black/30" />
                </div>

                <!-- Corner bracket targeting overlay (cosmetic QR aim guide) -->
                <div class="absolute inset-0 pointer-events-none p-8 flex flex-col items-center justify-center">
                  <div v-if="cameraStatus === 'active'" class="w-64 h-64 border-2 border-white/20 rounded-3xl relative">
                    <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-emerald-400 rounded-tl-xl" />
                    <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-emerald-400 rounded-tr-xl" />
                    <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-emerald-400 rounded-bl-xl" />
                    <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-emerald-400 rounded-br-xl" />
                  </div>
                </div>

                <!-- Unknown ID red overlay (triggered by triggerUnknownAlert) -->
                <Transition name="fade">
                  <div v-if="unknownAlert"
                       class="absolute inset-0 flex items-center justify-center bg-red-600/90 text-white font-black text-2xl uppercase tracking-widest text-center px-6">
                    🚨 Unknown ID - Not Found 🚨
                  </div>
                </Transition>

                <!-- Successful scan green pulse overlay -->
                <Transition name="fade">
                  <div v-if="successPulse" class="absolute inset-0 bg-emerald-500/20 animate-ping" />
                </Transition>

                <!-- Camera initialising spinner -->
                <div v-if="cameraStatus === 'starting'"
                     class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/95 gap-4">
                  <div class="w-12 h-12 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin" />
                  <p class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.3em]">Hardware Init</p>
                </div>
              </div>

              <!-- Scan status message bar (success / warning / error) -->
              <Transition name="slide-up">
                <div v-if="scanMessage.text"
                     class="px-4 py-3 rounded-2xl flex items-center gap-3 border shadow-lg transition-all duration-300"
                     :class="{
                       'bg-red-500/10 border-red-500/30 text-red-400': scanMessage.type === 'error',
                       'bg-emerald-500/10 border-emerald-500/30 text-emerald-400': scanMessage.type === 'success',
                       'bg-amber-500/10 border-amber-500/30 text-amber-500': scanMessage.type === 'warning',
                     }">
                  <div class="w-2 h-2 rounded-full animate-pulse"
                       :class="{
                         'bg-red-500': scanMessage.type === 'error',
                         'bg-emerald-500': scanMessage.type === 'success',
                         'bg-amber-500': scanMessage.type === 'warning',
                       }" />
                  <div v-if="scanProcessing" class="w-3 h-3 border-2 border-amber-500/30 border-t-amber-500 rounded-full animate-spin" />
                  <span class="text-xs font-black uppercase tracking-wider">{{ scanMessage.text }}</span>
                </div>
              </Transition>
            </div>

            <!-- Right: Last-scanned person result card -->
            <div class="w-80 flex flex-col gap-4">
              <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider">Scanning Result</h2>
              <div class="flex-1 rounded-3xl bg-slate-800/40 border border-slate-700/50 p-6 flex flex-col items-center justify-center text-center relative overflow-hidden group">
                <Transition name="zoom" mode="out-in">
                  <!-- Populated: show student/teacher details after a scan -->
                  <div v-if="lastScanDetails" :key="lastScanDetails.id" class="w-full flex flex-col items-center">
                    <div class="relative mb-6">
                      <img :src="getPhotoUrl(lastScanDetails.photo_path)"
                           class="w-40 h-40 rounded-3xl object-cover border-4 border-emerald-500 shadow-2xl rotate-3 group-hover:rotate-0 transition-transform duration-500"
                           @error="(e) => e.target.src = '/images/default-avatar.png'" />
                      <!-- Late / On-Time badge -->
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

                  <!-- Empty: waiting for first scan -->
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

        <!-- ── Recent Activity Feed ────────────────────────────────────── -->
        <section class="w-[420px] shrink-0 bg-slate-800/30 backdrop-blur-3xl border-l border-slate-700 flex flex-col overflow-hidden">
          <div class="p-6 border-b border-slate-700 flex items-center justify-between">
            <h2 class="text-sm font-black text-white uppercase tracking-[0.2em]">Recent Activity</h2>
            <div class="px-2 py-0.5 rounded-md bg-emerald-500/10 border border-emerald-500/30 text-[9px] font-black text-emerald-500 uppercase">Live Feed</div>
          </div>

          <div class="flex-1 overflow-auto scrollbar-hide p-4 space-y-3">
            <!-- Each new scan is prepended and animated in via TransitionGroup -->
            <TransitionGroup name="list">
              <div v-for="row in attendanceList" :key="row.id"
                   class="bg-slate-800/40 hover:bg-slate-700/40 p-3 rounded-2xl border border-slate-700/50 transition-all flex items-center gap-4 group">
                <div class="relative shrink-0">
                  <img :src="getPhotoUrl(row.photo_path)"
                       class="w-12 h-12 rounded-xl object-cover border-2 border-slate-700 group-hover:border-emerald-500/50 transition-all"
                       @error="(e) => e.target.src = '/images/default-avatar.png'" />
                  <!-- Status dot: green = on_time, amber = late -->
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

            <!-- Empty state -->
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
/**
 * GuardScanner.vue — Guard Terminal UI
 *
 * This component is intentionally thin: all reactive logic lives inside the
 * useScanner() composable (resources/js/composables/useScanner.js).
 *
 * Responsibilities of this file:
 *   - Template markup and layout
 *   - Lucide icon imports
 *   - Destructuring the composable's public API into the template scope
 *
 * See useScanner.js for detailed documentation of each function.
 */
import { Clock, User, Search } from 'lucide-vue-next';
import { useScanner } from '../composables/useScanner';

const {
    // Refs — bound to template
    qrReaderEl,
    cameraStatus,
    successPulse,
    scanMessage,
    attendanceList,
    autoRetryCountdown,
    currentTime,
    currentDate,
    stats,
    lastScanDetails,
    unknownAlert,
    scanProcessing,
    // Utilities used in template expressions
    formatTime,
    getPhotoUrl,
    manualRetry,
} = useScanner();
</script>

<style scoped>
/* Hide scrollbar on the activity feed while keeping scroll functionality */
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

/* Status message slide-up animation */
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from { opacity: 0; transform: translateY(20px); }
.slide-up-leave-to  { opacity: 0; transform: translateY(10px); }

/* Feed row slide-in animation (TransitionGroup) */
.list-enter-active, .list-leave-active { transition: all 0.5s ease; }
.list-enter-from { opacity: 0; transform: translateX(-30px); }
.list-leave-to   { opacity: 0; transform: translateX(30px); }

/* Status overlay fade animation */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Result card zoom animation */
.zoom-enter-active, .zoom-leave-active { transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
.zoom-enter-from { opacity: 0; transform: scale(0.9) translateY(10px); }
.zoom-leave-to   { opacity: 0; transform: scale(1.05); }

/* Laser scan line animation */
@keyframes scan-laser {
  0%   { top: 0; }
  50%  { top: 100%; }
  100% { top: 0; }
}
.animate-scan-laser {
  animation: scan-laser 3s linear infinite;
}
</style>
