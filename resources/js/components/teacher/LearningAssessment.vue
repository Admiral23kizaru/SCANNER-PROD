<template>
  <div class="w-full mx-auto p-4 sm:p-6 lg:max-w-4xl">
    <div class="mb-5">
      <h2 class="text-xl font-bold text-stone-900 tracking-tight">Learning Assessment</h2>
      <p class="text-sm text-stone-500 mt-0.5">Select class filters, export a roster template, then upload for scoring and item analysis</p>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-stone-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-stone-200 bg-blue-50/70">
        <h3 class="text-sm font-bold text-blue-900 uppercase tracking-wide">Learning Assessment Workflow</h3>
      </div>

      <div class="p-5 space-y-4">
        <!-- Step 1 -->
        <div class="rounded-lg border border-stone-200 overflow-hidden">
          <div class="px-4 py-2.5 bg-stone-50 border-b border-stone-200 text-sm font-semibold text-stone-700 flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-stone-900 text-white text-xs font-bold">1</span>
            Select Grade / Section / Subject
          </div>
          <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-stone-600 mb-1">Grade</label>
              <select
                v-model="filters.grade_level"
                class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-200"
                @change="onGradeFilterChange"
              >
                <option value="">All</option>
                <option v-for="g in meta.grades" :key="g" :value="g">{{ g }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-stone-600 mb-1">Section</label>
              <select
                v-model="filters.section"
                class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-200"
              >
                <option value="">All</option>
                <option v-for="s in meta.sections" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-medium text-stone-600 mb-1">Subject</label>
              <select
                v-model.number="filters.subject_id"
                class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-200"
              >
                <option :value="null" disabled>Select subject</option>
                <option v-for="sub in meta.subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
              </select>
              <p class="text-xs text-stone-500 mt-1.5">
                The Excel roster uses <strong class="font-medium text-stone-700">grade</strong> and
                <strong class="font-medium text-stone-700">section</strong>.
                <strong class="font-medium text-stone-700">Subject</strong> names the worksheet tab on template and analyzed exports.
              </p>
            </div>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="rounded-lg border border-stone-200 overflow-hidden">
          <div class="px-4 py-2.5 bg-stone-50 border-b border-stone-200 text-sm font-semibold text-stone-700 flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-stone-900 text-white text-xs font-bold">2</span>
            Export Learning Assessment Excel template
          </div>
          <div class="p-4 space-y-4">
            <p class="text-sm text-stone-600 rounded-lg border border-stone-200 bg-stone-50/40 px-4 py-3">
              Downloads an XLSX roster for the <strong class="font-semibold text-stone-800">grade</strong> and
              <strong class="font-semibold text-stone-800">section</strong> chosen in Step 1. Rows are ordered by grade, section, then name.
            </p>
            <button
              type="button"
              class="w-full inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="exporting"
              @click="exportExcel"
            >
              {{ exporting ? 'Preparing…' : 'Export to Excel' }}
            </button>
            <p class="text-xs text-stone-500">
              Blank grid: column A (Student ID/Name), row 2 Answer Key, then student rows and Item 1–{{ totalItems }} only (no formulas).
            </p>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="rounded-lg border border-stone-200 overflow-hidden">
          <div class="px-4 py-2.5 bg-amber-50 border-b border-amber-100 text-sm font-semibold text-stone-800 flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-amber-800 text-white text-xs font-bold">3</span>
            Export &amp; analyze Excel
          </div>
          <div class="p-4 space-y-3 bg-amber-50/20">
            <p class="text-xs text-stone-600">
              Upload a filled roster (row 1 headers, row 2 answer key, students from row 3). Preview scores and difficulty, then download the full analysis workbook.
            </p>
            <input
              ref="analyzeFileInput"
              type="file"
              accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
              class="hidden"
              @change="onAnalyzeFile"
            />
            <div class="flex flex-wrap items-center gap-3">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-800 transition disabled:opacity-50"
                :disabled="importAnalyzing"
                @click="analyzeFileInput?.click()"
              >
                {{ importAnalyzing ? 'Analyzing…' : 'Choose Excel file…' }}
              </button>
              <span v-if="analyzeResult" class="text-sm text-stone-600">
                Last file: {{ analyzeResult.student_count }} students · {{ analyzeResult.total_keyed_items }} keyed items
              </span>
            </div>
            <p v-if="analyzeError" class="text-sm text-red-600">{{ analyzeError }}</p>
            <button
              v-if="analyzeResult"
              type="button"
              class="text-sm font-medium text-amber-900 underline decoration-amber-700"
              @click="previewOpen = true"
            >
              Open preview
            </button>
          </div>
        </div>

        <div v-if="errorMessage" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
          {{ errorMessage }}
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="previewOpen && analyzeResult"
        class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto bg-black/45 p-4 sm:p-8"
        role="dialog"
        aria-modal="true"
        aria-labelledby="la-preview-title"
        @click.self="previewOpen = false"
      >
        <div
          class="relative w-full max-w-6xl rounded-xl bg-white shadow-2xl border border-stone-200 my-4 max-h-[90vh] flex flex-col"
          @click.stop
        >
          <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-stone-200 bg-stone-50 shrink-0">
            <div>
              <h3 id="la-preview-title" class="text-lg font-bold text-stone-900">Analysis preview</h3>
              <p class="text-xs text-stone-500 mt-0.5">
                {{ analyzeResult.student_count }} students · {{ analyzeResult.total_keyed_items }} items with an answer key ·
                Difficulty index = correct ÷ examinees (non-blank responses per item). This preview is read-only.
              </p>
            </div>
            <button
              type="button"
              class="rounded-lg p-2 text-stone-500 hover:bg-stone-200 hover:text-stone-800 transition"
              aria-label="Close"
              @click="previewOpen = false"
            >
              ✕
            </button>
          </div>

          <div class="overflow-y-auto flex-1 px-5 py-4 space-y-6">
            <div>
              <h4 class="text-sm font-semibold text-stone-800 mb-2">Student scores</h4>
              <div class="overflow-x-auto rounded-lg border border-stone-200">
                <table class="min-w-full text-sm text-left">
                  <thead class="bg-stone-100 text-stone-600 text-xs uppercase">
                    <tr>
                      <th class="py-2 px-3 border-b border-stone-200 whitespace-nowrap">Student</th>
                      <th class="py-2 px-3 border-b border-stone-200">Score</th>
                      <th class="py-2 px-3 border-b border-stone-200">%age</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in analyzeResult.students" :key="'stu-' + idx" class="border-b border-stone-100">
                      <td class="py-2 px-3 font-medium text-stone-900 max-w-[220px] truncate" :title="row.name">{{ row.name }}</td>
                      <td class="py-2 px-3 tabular-nums">{{ row.score }} / {{ analyzeResult.total_keyed_items }}</td>
                      <td class="py-2 px-3 tabular-nums">{{ row.percentage }}%</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div>
              <h4 class="text-sm font-semibold text-stone-800 mb-2">Item analysis (by column)</h4>
              <div class="overflow-x-auto rounded-lg border border-stone-200 max-h-[280px]">
                <table class="min-w-max text-xs text-left">
                  <thead class="bg-stone-100 text-stone-600 sticky top-0 z-10">
                    <tr>
                      <th class="py-2 px-2 border-b border-stone-200 sticky left-0 bg-stone-100 min-w-[160px]">Metric</th>
                      <th
                        v-for="n in analyzeResult.item_numbers"
                        :key="'h-' + n"
                        class="py-2 px-1 border-b border-stone-200 text-center whitespace-nowrap min-w-[52px]"
                      >
                        {{ n }}
                      </th>
                    </tr>
                  </thead>
                  <tbody class="text-stone-800">
                    <tr>
                      <td class="py-1.5 px-2 border-b border-stone-100 sticky left-0 bg-white font-medium">Total correct</td>
                      <td
                        v-for="st in analyzeResult.item_stats"
                        :key="'tc-' + st.item"
                        class="py-1.5 px-1 border-b border-stone-100 text-center tabular-nums"
                      >
                        {{ st.total_correct }}
                      </td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2 border-b border-stone-100 sticky left-0 bg-white font-medium">Examinees</td>
                      <td
                        v-for="st in analyzeResult.item_stats"
                        :key="'ex-' + st.item"
                        class="py-1.5 px-1 border-b border-stone-100 text-center tabular-nums"
                      >
                        {{ st.examinees }}
                      </td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2 border-b border-stone-100 sticky left-0 bg-white font-medium">p (correct/total)</td>
                      <td
                        v-for="st in analyzeResult.item_stats"
                        :key="'p-' + st.item"
                        class="py-1.5 px-1 border-b border-stone-100 text-center tabular-nums"
                      >
                        {{ st.p_value != null ? st.p_value.toFixed(2) : '—' }}
                      </td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2 border-b border-stone-100 sticky left-0 bg-white font-medium">Difficulty %</td>
                      <td
                        v-for="st in analyzeResult.item_stats"
                        :key="'dp-' + st.item"
                        class="py-1.5 px-1 border-b border-stone-100 text-center tabular-nums"
                      >
                        {{ st.difficulty_pct != null ? st.difficulty_pct + '%' : '—' }}
                      </td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2 border-b border-stone-100 sticky left-0 bg-white font-medium">Level (DO 8)</td>
                      <td
                        v-for="st in analyzeResult.item_stats"
                        :key="'lv-' + st.item"
                        class="py-1.5 px-1 border-b border-stone-100 text-center whitespace-nowrap"
                      >
                        {{ st.difficulty_level }}
                      </td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2 border-b border-stone-100 sticky left-0 bg-white font-medium">What it means</td>
                      <td
                        v-for="st in analyzeResult.item_stats"
                        :key="'wm-' + st.item"
                        class="py-1.5 px-1 border-b border-stone-100 text-center text-[11px]"
                      >
                        {{ st.what_it_means || '—' }}
                      </td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2 border-b border-stone-100 sticky left-0 bg-white font-medium">Recommended action</td>
                      <td
                        v-for="st in analyzeResult.item_stats"
                        :key="'ra-' + st.item"
                        class="py-1.5 px-1 border-b border-stone-100 text-center text-[11px]"
                      >
                        {{ st.recommended_action || st.interpretation }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div>
              <h4 class="text-sm font-semibold text-stone-800 mb-2">
                Difficulty Index (percentage-based) interpretation guide
              </h4>
              <div class="overflow-x-auto rounded-lg border border-stone-200">
                <table class="min-w-full text-xs text-left">
                  <thead class="bg-stone-100 text-stone-600">
                    <tr>
                      <th class="py-2 px-2 border-b border-stone-200">Difficulty Index (%)</th>
                      <th class="py-2 px-2 border-b border-stone-200">Interpretation</th>
                      <th class="py-2 px-2 border-b border-stone-200">What It Means</th>
                      <th class="py-2 px-2 border-b border-stone-200">Recommended Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, gi) in INTERPRETATION_GUIDE" :key="'ig-' + gi" class="border-b border-stone-100">
                      <td class="py-2 px-2 align-top tabular-nums">{{ row.range }}</td>
                      <td class="py-2 px-2 align-top font-medium" :class="row.levelClass">{{ row.interpretation }}</td>
                      <td class="py-2 px-2 align-top">{{ row.meaning }}</td>
                      <td class="py-2 px-2 align-top">{{ row.action }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div>
              <h4 class="text-sm font-semibold text-stone-800 mb-2">Difficulty Index chart</h4>
              <p class="text-xs text-stone-500 mb-2">Y-axis: index 0.00–1.00 (0.10 steps). X-axis: item numbers.</p>
              <div class="h-72 w-full min-h-[220px] rounded-lg border border-stone-200 bg-white p-2">
                <Bar v-if="analyzeResult?.item_stats?.length" :data="difficultyChartData" :options="difficultyChartOptions" />
              </div>
            </div>
          </div>

          <div class="flex justify-end px-5 py-4 border-t border-stone-200 bg-stone-50 shrink-0">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-lg border border-stone-300 bg-white px-4 py-2.5 text-sm font-medium text-stone-700 hover:bg-stone-100 transition"
              @click="previewOpen = false"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import axios from 'axios';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { computed, onMounted, ref } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
  apiBase: {
    type: String,
    default: '/api/teacher/learning-assessment',
  },
});

function getAuthHeaders() {
  const token = localStorage.getItem('scan_up_token');
  return token ? { Authorization: `Bearer ${token}` } : {};
}

const meta = ref({ grades: [], sections: [], subjects: [], default_grade_level: '', default_section: '' });
const filters = ref({ grade_level: '', section: '', subject_id: null });

const totalItems = ref(50);

const errorMessage = ref('');

const exporting = ref(false);

const analyzeFileInput = ref(null);
const analyzeResult = ref(null);
const importAnalyzing = ref(false);
const previewOpen = ref(false);
const analyzeError = ref('');

/** Matches the percentage-based interpretation guide in the analyzed Excel export. */
const INTERPRETATION_GUIDE = [
  {
    range: '80% – 100%',
    interpretation: 'Too Easy',
    meaning: 'Most students got the item.',
    action: 'Consider revising or removing if appropriate.',
    levelClass: 'bg-[#e6d9f2]',
  },
  {
    range: '50% – 79%',
    interpretation: 'Ideal / Moderately Easy',
    meaning: 'Good item – well balanced.',
    action: 'Usually no change needed.',
    levelClass: 'bg-[#fff9c4]',
  },
  {
    range: '30% – 49%',
    interpretation: 'Moderately Difficult',
    meaning: 'A bit challenging, but still fair.',
    action: 'Review for clarity or alignment.',
    levelClass: 'bg-[#c6efce]',
  },
  {
    range: '0% – 29%',
    interpretation: 'Too Difficult',
    meaning: 'Very few students got it right.',
    action: 'Consider for remediation.',
    levelClass: 'bg-[#6bb6ff]',
  },
];

const difficultyChartData = computed(() => {
  const r = analyzeResult.value;
  if (!r?.item_stats?.length) {
    return { labels: [], datasets: [] };
  }
  return {
    labels: r.item_numbers.map((n) => String(n)),
    datasets: [
      {
        label: 'Difficulty Index',
        data: r.item_stats.map((s) => (s.p_value != null ? Number(s.p_value) : 0)),
        backgroundColor: 'rgba(37, 99, 235, 0.55)',
        borderColor: 'rgb(30, 64, 175)',
        borderWidth: 1,
      },
    ],
  };
});

const difficultyChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    title: {
      display: true,
      text: 'Difficulty Index Chart',
      font: { size: 14, weight: '600' },
    },
    tooltip: {
      callbacks: {
        label(ctx) {
          const v = ctx.parsed.y;
          return `Index: ${v != null ? v.toFixed(2) : '—'}`;
        },
      },
    },
  },
  scales: {
    x: {
      title: { display: true, text: 'Item Number' },
      ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 26 },
    },
    y: {
      min: 0,
      max: 1,
      ticks: { stepSize: 0.1 },
      title: { display: true, text: 'Difficulty Index' },
      grid: { color: 'rgba(0,0,0,0.08)' },
    },
  },
}));

async function loadMeta() {
  const { data } = await axios.get(`${props.apiBase}/meta`, {
    params: {
      grade_level: filters.value.grade_level || undefined,
    },
    headers: { ...getAuthHeaders(), Accept: 'application/json' },
  });
  meta.value = data;
}

async function onGradeFilterChange() {
  await loadMeta();
  if (filters.value.section && !meta.value.sections.includes(filters.value.section)) {
    filters.value.section = '';
  }
}

async function onAnalyzeFile(ev) {
  const input = ev.target;
  const file = input?.files?.[0];
  if (!file) return;
  importAnalyzing.value = true;
  analyzeError.value = '';
  try {
    const fd = new FormData();
    fd.append('file', file);
    const { data } = await axios.post(`${props.apiBase}/import-analyze`, fd, {
      headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    analyzeResult.value = data;
    previewOpen.value = true;
  } catch (err) {
    analyzeError.value = err?.response?.data?.message || 'Could not analyze that file.';
    analyzeResult.value = null;
  } finally {
    importAnalyzing.value = false;
    input.value = '';
  }
}

async function exportExcel() {
  exporting.value = true;
  errorMessage.value = '';
  try {
    const res = await axios.get(`${props.apiBase}/export`, {
      params: {
        grade_level: filters.value.grade_level || undefined,
        section: filters.value.section || undefined,
        subject_id: filters.value.subject_id || undefined,
        total_items: totalItems.value,
      },
      responseType: 'blob',
      headers: { ...getAuthHeaders(), Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
    });

    const cd = res.headers?.['content-disposition'] || '';
    const match = /filename=\"?([^\";]+)\"?/i.exec(cd);
    const filename = match?.[1] || 'Learning_Assessment_Template.xlsx';

    const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    let fallback = 'Export failed.';
    try {
      const blobData = err?.response?.data;
      if (blobData instanceof Blob) {
        const txt = await blobData.text();
        const parsed = JSON.parse(txt);
        fallback = parsed?.message || fallback;
      } else if (blobData?.message) {
        fallback = blobData.message;
      }
    } catch (_) {
      // ignore parse errors and keep fallback message
    }
    errorMessage.value = fallback;
  } finally {
    exporting.value = false;
  }
}

onMounted(async () => {
  await loadMeta();
  if (!filters.value.grade_level && meta.value.default_grade_level) {
    filters.value.grade_level = meta.value.default_grade_level;
  }
  if (!filters.value.section && meta.value.default_section) {
    filters.value.section = meta.value.default_section;
  }
  if (!filters.value.subject_id && meta.value.subjects?.length) {
    filters.value.subject_id = meta.value.subjects[0].id;
  }
  if (filters.value.grade_level) {
    await loadMeta();
    if (filters.value.section && !meta.value.sections.includes(filters.value.section)) {
      filters.value.section = '';
    }
  }
});
</script>
