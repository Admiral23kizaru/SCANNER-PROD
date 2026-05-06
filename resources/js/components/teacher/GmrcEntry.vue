<template>
  <div class="w-full mx-auto p-4 sm:p-6 lg:max-w-7xl">
    <div class="mb-5">
      <h2 class="text-xl font-bold text-stone-900 tracking-tight">GMRC Quick Entry and Export</h2>
      <p class="text-sm text-stone-500 mt-0.5">Fast score entry + filtered Excel template export</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
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
              Select Section/Subject
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-stone-600 mb-1">Grade</label>
                <select
                  v-model="filters.grade_level"
                  class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-200"
                  @change="loadStudents"
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
                <input
                  v-model="subject"
                  type="text"
                  class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-200"
                  placeholder="GMRC"
                />
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
                    <th class="py-3 px-4 border-b border-stone-200">Student</th>
                    <th class="py-3 px-4 border-b border-stone-200">Wrong Items</th>
                    <th class="py-3 px-4 border-b border-stone-200">Score</th>
                    <th class="py-3 px-4 border-b border-stone-200">Time</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in recent" :key="row.id" class="border-b border-stone-100 hover:bg-stone-50 transition">
                    <td class="py-3 px-4 text-stone-700">{{ row.section || '—' }}</td>
                    <td class="py-3 px-4 font-medium text-stone-800">{{ row.student }}</td>
                    <td class="py-3 px-4 text-stone-700 font-mono text-xs">{{ formatWrongItems(row.wrong_items) }}</td>
                    <td class="py-3 px-4 text-stone-800">{{ row.score }}/{{ row.total_items }}</td>
                    <td class="py-3 px-4 text-stone-600 text-xs">{{ formatTime(row.created_at) }}</td>
                  </tr>
                  <tr v-if="recent.length === 0">
                    <td colspan="5" class="py-8 px-4 text-center text-stone-500">No entries yet.</td>
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
            <h3 class="text-sm font-bold text-stone-800 uppercase tracking-wide">Export GMRC Excel Template</h3>
            <p class="text-xs text-stone-500 mt-0.5">Download an XLSX template (filtered)</p>
          </div>
        </div>

        <div class="p-5 space-y-4">
          <div class="rounded-lg border border-stone-200 bg-stone-50/40 p-4">
            <div class="text-xs font-semibold text-stone-600 mb-2">Export Settings</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-stone-600 mb-1">Filter by Grade</label>
                <select
                  v-model="exportFilters.grade_level"
                  class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-200"
                >
                  <option value="">All</option>
                  <option v-for="g in meta.grades" :key="'exg-' + g" :value="g">{{ g }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-stone-600 mb-1">Filter by Section</label>
                <select
                  v-model="exportFilters.section"
                  class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-200"
                >
                  <option value="">All</option>
                  <option v-for="s in meta.sections" :key="'exs-' + s" :value="s">{{ s }}</option>
                </select>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="w-full inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-800 transition disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="exporting"
            @click="exportExcel"
          >
            {{ exporting ? 'Preparing…' : 'Export to Excel (Filtered)' }}
          </button>

          <p class="text-xs text-stone-500">
            Template includes row 1 "Item 1..{{ totalItems }}", row 2 "Answer Key", and student rows.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const base = '/api/teacher/gmrc';
function getAuthHeaders() {
  const token = localStorage.getItem('scan_up_token');
  return token ? { Authorization: `Bearer ${token}` } : {};
}

const meta = ref({ grades: [], sections: [], default_grade_level: '', default_section: '' });
const filters = ref({ grade_level: '', section: '' });
const exportFilters = ref({ grade_level: '', section: '' });
const subject = ref('GMRC');

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
  const { data } = await axios.get(`${base}/meta`, {
    headers: { ...getAuthHeaders(), Accept: 'application/json' },
  });
  meta.value = data;
  if (!filters.value.grade_level && data.default_grade_level) filters.value.grade_level = data.default_grade_level;
  if (!filters.value.section && data.default_section) filters.value.section = data.default_section;
  if (!exportFilters.value.grade_level && data.default_grade_level) exportFilters.value.grade_level = data.default_grade_level;
  if (!exportFilters.value.section && data.default_section) exportFilters.value.section = data.default_section;
}

async function loadStudents() {
  const { data } = await axios.get(`${base}/students`, {
    params: { grade_level: filters.value.grade_level || undefined, section: filters.value.section || undefined },
    headers: { ...getAuthHeaders(), Accept: 'application/json' },
  });
  students.value = data.data || [];
  if (form.value.student_id && !students.value.some((s) => s.id === form.value.student_id)) {
    form.value.student_id = null;
  }
}

async function loadRecent() {
  const { data } = await axios.get(`${base}/recent`, {
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
      `${base}/scores`,
      { student_id: form.value.student_id, wrong_items: form.value.wrong_items, total_items: totalItems.value },
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

async function exportExcel() {
  exporting.value = true;
  errorMessage.value = '';
  try {
    const res = await axios.get(`${base}/export`, {
      params: {
        grade_level: exportFilters.value.grade_level || undefined,
        section: exportFilters.value.section || undefined,
        total_items: totalItems.value,
      },
      responseType: 'blob',
      headers: { ...getAuthHeaders(), Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
    });

    const cd = res.headers?.['content-disposition'] || '';
    const match = /filename=\"?([^\";]+)\"?/i.exec(cd);
    const filename = match?.[1] || 'GMRC_Template.xlsx';

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
  await loadStudents();
  await loadRecent();
});
</script>

