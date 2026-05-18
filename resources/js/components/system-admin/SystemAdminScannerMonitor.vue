<template>
  <section class="space-y-5">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-950">Live Scanner Monitor</h2>
        <p class="text-sm text-slate-500">
          Division-level scanner heartbeat wall. This shows terminal health and scan activity, not camera video.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
          AJAX polling every 10s
        </span>
        <button
          type="button"
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          @click="loadCards"
        >
          Refresh
        </button>
      </div>
    </div>

    <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
      {{ error }}
    </div>

    <div class="grid gap-5 xl:grid-cols-2 2xl:grid-cols-3">
      <article
        v-for="card in paginatedCards"
        :key="`${card.school_id || card.deped_school_id}-${card.scanner_key}`"
        class="overflow-hidden rounded-2xl border shadow-sm"
        :class="terminalClass(card.connection_status)"
      >
        <header class="flex items-center justify-between gap-4 border-b border-slate-700/80 bg-slate-800/80 p-4">
          <div class="flex min-w-0 items-center gap-3">
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-black text-white shadow-lg"
              :class="statusAccentClass(card.connection_status)"
            >
              A
            </div>
            <div class="min-w-0">
              <p class="truncate text-base font-black leading-none text-white">{{ card.school_name }}</p>
              <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-emerald-300">
                {{ card.deped_school_id }} | {{ card.scanner_key || 'main-terminal' }}
              </p>
            </div>
          </div>
          <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider" :class="statusPillClass(card.connection_status)">
            {{ statusLabel(card.connection_status) }}
          </span>
        </header>

        <div class="border-b border-slate-700/80 bg-slate-900 p-4">
          <div class="grid grid-cols-4 gap-2">
            <div
              v-for="stat in statCards(card)"
              :key="stat.label"
              class="rounded-xl border px-3 py-3 text-center"
              :class="stat.class"
            >
              <p class="text-[9px] font-black uppercase tracking-widest">{{ stat.label }}</p>
              <p class="mt-1 text-2xl font-black text-white">{{ numberValue(stat.value) }}</p>
            </div>
          </div>
        </div>

        <div class="grid gap-4 bg-slate-900 p-4 lg:grid-cols-[1.3fr_1fr]">
          <section>
            <div class="mb-3 flex items-center justify-between">
              <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Scanner View</h3>
              <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full" :class="statusDotClass(card.connection_status)" />
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-300">
                  {{ card.connection_status === 'online' ? 'System Live' : 'Offline' }}
                </span>
              </div>
            </div>

            <div class="relative aspect-video overflow-hidden rounded-2xl bg-black shadow-2xl ring-1 ring-slate-700">
              <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(16,185,129,0.12),transparent_55%)]" />
              <div class="absolute inset-x-0 top-1/2 h-px" :class="scannerLaserClass(card.connection_status)" />
              <div class="absolute inset-0 border-[22px] border-black/25" />
              <div class="absolute inset-0 flex items-center justify-center p-8">
                <div
                  class="relative h-32 w-32 rounded-3xl border-2"
                  :class="scannerFrameClass(card.connection_status)"
                >
                  <span class="absolute -left-1 -top-1 h-8 w-8 rounded-tl-xl border-l-4 border-t-4" :class="scannerCornerClass(card.connection_status)" />
                  <span class="absolute -right-1 -top-1 h-8 w-8 rounded-tr-xl border-r-4 border-t-4" :class="scannerCornerClass(card.connection_status)" />
                  <span class="absolute -bottom-1 -left-1 h-8 w-8 rounded-bl-xl border-b-4 border-l-4" :class="scannerCornerClass(card.connection_status)" />
                  <span class="absolute -bottom-1 -right-1 h-8 w-8 rounded-br-xl border-b-4 border-r-4" :class="scannerCornerClass(card.connection_status)" />
                </div>
              </div>
              <div v-if="card.connection_status !== 'online'" class="absolute inset-0 flex items-center justify-center bg-slate-950/70">
                <p class="rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-red-300">
                  Waiting for heartbeat
                </p>
              </div>
            </div>

            <p class="mt-3 text-[10px] text-slate-500">
              Last heartbeat: {{ formatDate(card.last_seen_at) || 'Never' }}
            </p>
          </section>

          <section class="rounded-2xl border border-slate-700/70 bg-slate-800/40 p-4">
            <div class="mb-3 flex items-center justify-between">
              <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-300">Latest Scan</h3>
              <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-[9px] font-black uppercase text-emerald-300">
                Live Feed
              </span>
            </div>

            <div v-if="card.latest_scan" class="space-y-3">
              <div class="flex items-center gap-3 rounded-2xl border border-slate-700/70 bg-slate-900/70 p-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400">
                  <span class="text-xs font-black">ID</span>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-black text-white">{{ card.latest_scan.student_name }}</p>
                  <p class="truncate text-[10px] font-bold uppercase tracking-widest text-slate-500">
                    {{ card.latest_scan.grade_section || 'No grade/section' }}
                  </p>
                </div>
              </div>
              <div class="flex items-center justify-between text-xs text-slate-400">
                <span class="capitalize">{{ scanStatus(card.latest_scan.status) }}</span>
                <span>{{ formatTime(card.latest_scan.scanned_at) }}</span>
              </div>
            </div>

            <div v-else class="flex min-h-[150px] flex-col items-center justify-center text-center opacity-60">
              <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-dashed border-slate-600">
                <span class="text-xl text-slate-600">?</span>
              </div>
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Scanning history is empty</p>
            </div>
          </section>
        </div>
      </article>
    </div>

    <div v-if="!loading && cards.length === 0" class="rounded-lg border border-slate-200 bg-white p-10 text-center">
      <p class="font-semibold text-slate-900">No scanner heartbeat yet.</p>
      <p class="mt-1 text-sm text-slate-500">Open a school scanner from the live BAT launcher to create its monitor card.</p>
    </div>

    <div
      v-if="cards.length > 0"
      class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between"
    >
      <p class="text-sm text-slate-600">
        Showing {{ pageStart }}-{{ pageEnd }} of {{ cards.length }} scanner cards
      </p>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="currentPage <= 1"
          @click="currentPage -= 1"
        >
          Previous
        </button>
        <span class="text-sm text-slate-600">{{ currentPage }} / {{ totalPages }}</span>
        <button
          type="button"
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="currentPage >= totalPages"
          @click="currentPage += 1"
        >
          Next
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { fetchSystemAdminScannerMonitor } from '../../services/systemAdminService';

const cards = ref([]);
const error = ref('');
const loading = ref(false);
const currentPage = ref(1);
const perPage = 10;
let timer = null;

const totalPages = computed(() => Math.max(1, Math.ceil(cards.value.length / perPage)));

const paginatedCards = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return cards.value.slice(start, start + perPage);
});

const pageStart = computed(() => {
  if (cards.value.length === 0) return 0;
  return (currentPage.value - 1) * perPage + 1;
});

const pageEnd = computed(() => Math.min(currentPage.value * perPage, cards.value.length));

watch(totalPages, (pages) => {
  if (currentPage.value > pages) {
    currentPage.value = pages;
  }
});

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

function formatDate(value) {
  if (!value) return '';
  return new Date(value).toLocaleString();
}

function formatTime(value) {
  if (!value) return '';
  return new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function scanStatus(status) {
  return status === 'late' ? 'late scan' : 'on time scan';
}

function statusLabel(status) {
  if (status === 'online') return 'Online';
  if (status === 'idle') return 'Idle';
  return 'Offline';
}

function statCards(card) {
  const stats = card.stats || {};
  return [
    { label: 'Total', value: stats.total_today ?? card.scans_today, class: 'border-blue-500/20 bg-blue-500/10 text-blue-300' },
    { label: 'Present', value: stats.present_count, class: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' },
    { label: 'Late', value: stats.late_count, class: 'border-amber-500/20 bg-amber-500/10 text-amber-300' },
    { label: 'Absent', value: stats.absent_count, class: 'border-red-500/20 bg-red-500/10 text-red-300' },
  ];
}

function terminalClass(status) {
  if (status === 'online') return 'border-emerald-500/30 bg-slate-900';
  if (status === 'idle') return 'border-amber-500/30 bg-slate-900';
  return 'border-red-500/20 bg-slate-900';
}

function statusAccentClass(status) {
  if (status === 'online') return 'bg-emerald-600 ring-2 ring-emerald-500/20';
  if (status === 'idle') return 'bg-amber-600 ring-2 ring-amber-500/20';
  return 'bg-red-700 ring-2 ring-red-500/20';
}

function statusPillClass(status) {
  if (status === 'online') return 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-300';
  if (status === 'idle') return 'border border-amber-500/30 bg-amber-500/10 text-amber-300';
  return 'border border-red-500/30 bg-red-500/10 text-red-300';
}

function statusDotClass(status) {
  if (status === 'online') return 'bg-emerald-400 animate-pulse';
  if (status === 'idle') return 'bg-amber-400';
  return 'bg-red-500';
}

function scannerFrameClass(status) {
  if (status === 'online') return 'border-white/20';
  if (status === 'idle') return 'border-amber-400/20';
  return 'border-red-400/20';
}

function scannerCornerClass(status) {
  if (status === 'online') return 'border-emerald-400';
  if (status === 'idle') return 'border-amber-400';
  return 'border-red-400';
}

function scannerLaserClass(status) {
  if (status === 'online') return 'bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.8)]';
  if (status === 'idle') return 'bg-amber-500 shadow-[0_0_15px_rgba(245,158,11,0.7)]';
  return 'bg-red-500/50';
}

async function loadCards() {
  loading.value = true;
  error.value = '';

  try {
    cards.value = await fetchSystemAdminScannerMonitor();
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load scanner monitor.';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadCards();
  timer = setInterval(loadCards, 10000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});
</script>
