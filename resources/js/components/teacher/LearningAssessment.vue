<template>
  <div class="w-full mx-auto p-4 sm:p-6 lg:max-w-7xl">
    <div class="mb-5">
      <h2 class="text-xl font-bold text-stone-900 tracking-tight">Learning Assessment</h2>
      <p class="text-sm text-stone-500 mt-0.5">Fast score entry, roster export, and import / item analysis</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
      <!-- Score Entry Workflow -->
      <div class="bg-white rounded-xl shadow-md border border-stone-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-200 bg-blue-50/70">
          <h3 class="text-sm font-bold text-blue-900 uppercase tracking-wide">Score Entry Workflow</h3>
        </div>

        <div class="p-5 space-y-4">
          <!-- Step 1 -->
          <div class="rounded-lg border border-stone-200 overflow-hidden">
            <div class="px-4 py-2.5 bg-stone-50 border-b border-stone-200 text-sm font-semibold text-stone-700 flex items-center gap-2">
              <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-stone-900 text-white text-xs font-bold">1</span>
              Select Grade/Section/Subject
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
                  @change="loadStudents"
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
                  The student list and Excel roster follow <strong class="font-medium text-stone-700">grade and section</strong>.
                  The selected subject names the <strong class="font-medium text-stone-700">Excel worksheet tab</strong> and is stored when you submit a score.
                </p>
              </div>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="rounded-lg border border-stone-200 overflow-hidden">
            <div class="px-4 py-2.5 bg-stone-50 border-b border-stone-200 text-sm font-semibold text-stone-700 flex items-center gap-2">
              <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-stone-900 text-white text-xs font-bold">2</span>
              Select Student
            </div>
            <div class="p-4">
              <select
                v-model.number="form.student_id"
                class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-200"
              >
                <option :value="null" disabled>Select student</option>
                <option v-for="st in students" :key="st.id" :value="st.id">
                  {{ st.name }} — {{ st.student_number }}
                </option>
              </select>
              <p v-if="students.length === 0" class="text-xs text-stone-500 mt-2">No students found for the selected filters.</p>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="rounded-lg border border-stone-200 overflow-hidden">
            <div class="px-4 py-2.5 bg-stone-50 border-b border-stone-200 text-sm font-semibold text-stone-700 flex items-center gap-2">
              <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-stone-900 text-white text-xs font-bold">3</span>
              Enter Wrong Item Numbers
            </div>
            <form class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end" @submit.prevent="submitScore">
              <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-stone-600 mb-1">Wrong Item Numbers (comma-separated)</label>
                <input
                  v-model="form.wrong_items"
                  type="text"
                  class="w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2"
                  :class="wrongItemsError ? 'border-red-300 focus:ring-red-200' : 'border-stone-200 focus:ring-stone-200'"
                  placeholder="e.g. 5,12,23,34,45"
                />
                <p v-if="wrongItemsError" class="text-xs text-red-600 mt-1">{{ wrongItemsError }}</p>
              </div>
              <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="submitting || !form.student_id"
              >
                {{ submitting ? 'Submitting…' : 'Submit' }}
              </button>
            </form>
          </div>

          <div v-if="successMessage" class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ successMessage }}
          </div>
          <div v-if="errorMessage" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ errorMessage }}
          </div>

          <!-- Quick Log -->
          <div class="rounded-xl border border-stone-200 overflow-hidden">
            <div class="px-4 py-2.5 bg-stone-50 border-b border-stone-200 text-sm font-semibold text-stone-700">
              Quick Log (last 10 entries)
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="bg-white text-stone-500 text-xs font-medium">
                  <tr>
                    <th class="py-3 px-4 border-b border-stone-200">Section</th>
                    <th class="py-3 px-4 border-b border-stone-200">Subject</th>
                    <th class="py-3 px-4 border-b border-stone-200">Student</th>
                    <th class="py-3 px-4 border-b border-stone-200">Wrong Items</th>
                    <th class="py-3 px-4 border-b border-stone-200">Score</th>
                    <th class="py-3 px-4 border-b border-stone-200">Time</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in recent" :key="row.id" class="border-b border-stone-100 hover:bg-stone-50 transition">
                    <td class="py-3 px-4 text-stone-700">{{ row.section || '—' }}</td>
                    <td class="py-3 px-4 text-stone-700">{{ row.subject || '—' }}</td>
                    <td class="py-3 px-4 font-medium text-stone-800">{{ row.student }}</td>
                    <td class="py-3 px-4 text-stone-700 font-mono text-xs">{{ formatWrongItems(row.wrong_items) }}</td>
                    <td class="py-3 px-4 text-stone-800">{{ row.score }}/{{ row.total_items }}</td>
                    <td class="py-3 px-4 text-stone-600 text-xs">{{ formatTime(row.created_at) }}</td>
                  </tr>
                  <tr v-if="recent.length === 0">
                    <td colspan="6" class="py-8 px-4 text-center text-stone-500">No entries yet.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Export Template -->
      <div class="bg-white rounded-xl shadow-md border border-stone-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-200 bg-stone-50/70 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-stone-800 uppercase tracking-wide">Export Learning Assessment Excel Template</h3>
            <p class="text-xs text-stone-500 mt-0.5">Download an XLSX roster for the class selected in Step 1</p>
          </div>
        </div>

        <div class="p-5 space-y-4">
          <p class="text-sm text-stone-600 rounded-lg border border-stone-200 bg-stone-50/40 px-4 py-3">
            The Excel file lists everyone in the selected <strong class="font-semibold text-stone-800">grade</strong> and
            <strong class="font-semibold text-stone-800">section</strong> (same as Step 1). Rows are ordered by grade, section, then name.
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
            Template is a blank grid: column A (Student ID/Name), row 2 Answer Key, then student rows and Item 1–{{ totalItems }} only (no formulas).
          </p>
        </div>
      </div>
    </div>

    <!-- Import & Analyze -->
    <div class="bg-white rounded-xl shadow-md border border-stone-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-stone-200 bg-amber-50/70">
        <h3 class="text-sm font-bold text-stone-800 uppercase tracking-wide">Import &amp; Analyze (Excel)</h3>
        <p class="text-xs text-stone-500 mt-0.5">
          Upload a filled roster (row 1 headers, row 2 answer key, students from row 3). Preview scores and difficulty, then download the full analysis workbook.
        </p>
      </div>
      <div class="p-5 space-y-3">
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
          class="text-sm font-medium text-amber-800 underline"
          @click="previewOpen = true"
        >
          Open preview
        </button>
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
                Difficulty index = correct ÷ examinees (non-blank responses per item)
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

          <div class="flex flex-col gap-2 px-5 py-4 border-t border-stone-200 bg-stone-50 shrink-0">
            <p v-if="analyzeExportMessage" class="text-sm text-red-600">{{ analyzeExportMessage }}</p>
            <div class="flex flex-wrap gap-3">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 transition disabled:opacity-50"
              :disabled="analyzeExporting"
              @click="downloadAnalyzedExcel"
            >
              {{ analyzeExporting ? 'Building file…' : 'Download analyzed Excel' }}
            </button>
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

/** Base path for Learning Assessment API (teacher role). */
const learningAssessmentApiBase = '/api/teacher/learning-assessment';

function getAuthHeaders() {
  const token = localStorage.getItem('scan_up_token');
  return token ? { Authorization: `Bearer ${token}` } : {};
}

const meta = ref({ grades: [], sections: [], subjects: [], default_grade_level: '', default_section: '' });
const filters = ref({ grade_level: '', section: '', subject_id: null });

const students = ref([]);
const recent = ref([]);

const totalItems = ref(50);

const form = ref({
  student_id: null,
  wrong_items: '',
});

const wrongItemsError = ref('');
const submitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const exporting = ref(false);

const analyzeFileInput = ref(null);
const analyzeResult = ref(null);
const importAnalyzing = ref(false);
const analyzeExporting = ref(false);
const previewOpen = ref(false);
const analyzeError = ref('');
const analyzeExportMessage = ref('');

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

function validateWrongItems(raw) {
  const v = String(raw ?? '').trim();
  if (!v) return '';
  if (!/^\d+(?:\s*,\s*\d+)*$/.test(v)) {
    return 'Only numbers and commas are allowed.';
  }
  const nums = v.split(',').map((x) => Number(String(x).trim())).filter((n) => Number.isFinite(n));
  if (nums.some((n) => n < 1 || n > totalItems.value)) {
    return `Items must be between 1 and ${totalItems.value}.`;
  }
  return '';
}

function formatWrongItems(arr) {
  if (!arr || arr.length === 0) return '—';
  return Array.isArray(arr) ? arr.join(',') : String(arr);
}

function formatTime(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '—';
  return d.toLocaleString();
}

async function loadMeta() {
  const { data } = await axios.get(`${learningAssessmentApiBase}/meta`, {
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
  await loadStudents();
}

async function loadStudents() {
  const { data } = await axios.get(`${learningAssessmentApiBase}/students`, {
    params: {
      grade_level: filters.value.grade_level || undefined,
      section: filters.value.section || undefined,
    },
    headers: { ...getAuthHeaders(), Accept: 'application/json' },
  });
  students.value = data.data || [];
  if (form.value.student_id && !students.value.some((s) => s.id === form.value.student_id)) {
    form.value.student_id = null;
  }
}

async function loadRecent() {
  const { data } = await axios.get(`${learningAssessmentApiBase}/recent`, {
    headers: { ...getAuthHeaders(), Accept: 'application/json' },
  });
  recent.value = data.data || [];
}

async function submitScore() {
  successMessage.value = '';
  errorMessage.value = '';
  wrongItemsError.value = validateWrongItems(form.value.wrong_items);
  if (wrongItemsError.value) return;
  if (!form.value.student_id) return;

  submitting.value = true;
  try {
    const { data } = await axios.post(
      `${learningAssessmentApiBase}/scores`,
      {
        student_id: form.value.student_id,
        subject_id: filters.value.subject_id,
        wrong_items: form.value.wrong_items,
        total_items: totalItems.value,
      },
      { headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' } }
    );
    const entry = data.entry;
    successMessage.value = `${entry.student} - Score: ${entry.score}/${entry.total_items} (${Math.round((entry.score / entry.total_items) * 100)}%)`;
    form.value.wrong_items = '';
    await loadRecent();
    setTimeout(() => (successMessage.value = ''), 5000);
  } catch (err) {
    errorMessage.value = err?.response?.data?.message || 'Failed to submit score.';
  } finally {
    submitting.value = false;
  }
}

async function onAnalyzeFile(ev) {
  const input = ev.target;
  const file = input?.files?.[0];
  if (!file) return;
  importAnalyzing.value = true;
  analyzeError.value = '';
  analyzeExportMessage.value = '';
  try {
    const fd = new FormData();
    fd.append('file', file);
    const { data } = await axios.post(`${learningAssessmentApiBase}/import-analyze`, fd, {
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

async function downloadAnalyzedExcel() {
  if (!analyzeResult.value) return;
  analyzeExporting.value = true;
  analyzeExportMessage.value = '';
  try {
    const sub = meta.value.subjects?.find((s) => s.id === filters.value.subject_id);
    const r = analyzeResult.value;
    const res = await axios.post(
      `${learningAssessmentApiBase}/import-analyze/export`,
      {
        sheet_title: sub?.name || 'Learning Assessment',
        item_numbers: r.item_numbers,
        answer_key: r.answer_key,
        students: r.students,
        item_stats: r.item_stats,
      },
      {
        responseType: 'blob',
        headers: {
          ...getAuthHeaders(),
          'Content-Type': 'application/json',
          Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        },
      }
    );

    const cd = res.headers?.['content-disposition'] || '';
    const match = /filename=\"?([^\";]+)\"?/i.exec(cd);
    const filename = match?.[1] || 'Learning_Assessment_Analyzed.xlsx';
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
      /* ignore */
    }
    analyzeExportMessage.value = fallback;
  } finally {
    analyzeExporting.value = false;
  }
}

async function exportExcel() {
  exporting.value = true;
  errorMessage.value = '';
  try {
    const res = await axios.get(`${learningAssessmentApiBase}/export`, {
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
  await loadStudents();
  await loadRecent();
});
</script>
