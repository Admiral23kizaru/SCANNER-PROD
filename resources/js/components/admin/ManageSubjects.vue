<template>
  <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
    <div class="p-4 sm:p-5 border-b border-slate-200 bg-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Manage Subjects</h2>
        <p class="text-sm text-slate-500 mt-1">Add, edit, delete subjects and use them for Learning Assessment</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg border border-sky-200 bg-white px-4 py-2.5 text-sm font-semibold text-sky-700 hover:bg-sky-50 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-sky-200"
          @click="openEhrisModal"
        >
          Fetch EHRIS Subjects
        </button>
        <button
          type="button"
          class="rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-800 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-sky-200"
          @click="openCreate"
        >
          Add Subject
        </button>
      </div>
    </div>

    <div v-if="errorMsg" class="m-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
      {{ errorMsg }}
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wide">
          <tr>
            <th class="py-3 px-4 border-b border-slate-200">Subject</th>
            <th class="py-3 px-4 border-b border-slate-200">Created</th>
            <th class="py-3 px-4 border-b border-slate-200 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in subjects" :key="s.id" class="border-b border-slate-100 hover:bg-sky-50/50 transition">
            <td class="py-3 px-4 font-medium text-slate-900">{{ s.name }}</td>
            <td class="py-3 px-4 text-slate-600 text-xs">{{ formatTime(s.created_at) }}</td>
            <td class="py-3 px-4 text-right">
              <div class="inline-flex items-center gap-2">
                <button
                  type="button"
                  class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                  @click="openEdit(s)"
                >
                  Edit
                </button>
                <button
                  type="button"
                  class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 transition"
                  @click="openDelete(s)"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && subjects.length === 0">
            <td colspan="3" class="py-12 text-center text-slate-400 italic">No subjects yet.</td>
          </tr>
          <tr v-if="loading && subjects.length === 0">
            <td colspan="3" class="py-12 text-center text-slate-500">Loading...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- EHRIS Subjects Modal -->
  <div v-if="showEhrisModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showEhrisModal = false">
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full border border-slate-200 overflow-hidden" @click.stop>
      <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h3 class="text-base font-semibold text-slate-900">Fetch EHRIS Subjects</h3>
          <p class="text-sm text-slate-500 mt-1">Preview school teacher assignments plus active EHRIS library subjects, then sync into local subjects.</p>
        </div>
        <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm" @click="showEhrisModal = false">Close</button>
      </div>

      <div v-if="ehrisError" class="m-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        {{ ehrisError }}
      </div>

      <div class="p-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200">
        <p class="text-sm text-slate-600">
          DepEd School ID: <span class="font-mono font-semibold">{{ ehrisDepedSchoolId || '-' }}</span>
        </p>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 disabled:opacity-50"
            :disabled="ehrisLoading"
            @click="loadEhrisSubjects"
          >
            {{ ehrisLoading ? 'Fetching...' : 'Refresh' }}
          </button>
          <button
            type="button"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
            :disabled="ehrisSyncing || !ehrisRows.length"
            @click="syncAllEhrisSubjects"
          >
            {{ ehrisSyncing ? 'Syncing...' : 'Sync All' }}
          </button>
          <button
            type="button"
            class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
            :disabled="ehrisSyncing || !ehrisSelected.length"
            @click="syncSelectedEhrisSubjects"
          >
            Sync Selected
          </button>
        </div>
      </div>

      <div class="max-h-[520px] overflow-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wide">
            <tr>
              <th class="py-3 px-4 border-b border-slate-200 w-12">
                <input type="checkbox" :checked="ehrisAllSelected" @change="toggleAllEhris" />
              </th>
              <th class="py-3 px-4 border-b border-slate-200">Subject</th>
              <th class="py-3 px-4 border-b border-slate-200">Source</th>
              <th class="py-3 px-4 border-b border-slate-200">Teachers</th>
              <th class="py-3 px-4 border-b border-slate-200">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in ehrisRows" :key="row.name" class="border-b border-slate-100 hover:bg-sky-50/50">
              <td class="py-3 px-4">
                <input type="checkbox" :checked="ehrisSelected.includes(row.name)" @change="toggleEhrisSubject(row.name)" />
              </td>
              <td class="py-3 px-4 font-medium text-slate-900">{{ row.name }}</td>
              <td class="py-3 px-4 text-slate-600">
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="row.source === 'teacher_assignment' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'">
                  {{ row.source === 'teacher_assignment' ? 'Teacher assignment' : 'Subject library' }}
                </span>
              </td>
              <td class="py-3 px-4 text-slate-600">
                {{ row.teacher_count }} teacher(s)
                <p class="text-xs text-slate-400 truncate max-w-[420px]">{{ row.sample_teachers || 'No teacher names available' }}</p>
              </td>
              <td class="py-3 px-4">
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="row.is_synced ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                  {{ row.is_synced ? 'Already synced' : 'Not synced' }}
                </span>
              </td>
            </tr>
            <tr v-if="!ehrisLoading && ehrisRows.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-400 italic">No EHRIS subjects found for this school.</td>
            </tr>
            <tr v-if="ehrisLoading">
              <td colspan="5" class="py-12 text-center text-slate-500">Fetching EHRIS subjects...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeModal">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full border border-slate-200" @click.stop>
      <div class="p-5 border-b border-slate-200">
        <h3 class="text-base font-semibold text-slate-900">{{ editingId ? 'Edit Subject' : 'Add Subject' }}</h3>
      </div>
      <form class="p-5 space-y-3" @submit.prevent="submit">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Subject name</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200"
            placeholder="e.g. Science"
            required
          />
        </div>
        <div v-if="formError" class="text-sm text-red-600">{{ formError }}</div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm" @click="closeModal">Cancel</button>
          <button
            type="submit"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="saving"
          >
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirm -->
  <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="deleteTarget = null">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full border border-slate-200 p-5" @click.stop>
      <h3 class="text-base font-semibold text-slate-900">Delete subject</h3>
      <p class="text-sm text-slate-600 mt-1">Delete <span class="font-medium">{{ deleteTarget.name }}</span>?</p>
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm" @click="deleteTarget = null">Cancel</button>
        <button
          type="button"
          class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="saving"
          @click="doDelete"
        >
          {{ saving ? 'Deleting...' : 'Delete' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

function getAuthHeaders() {
  const token = localStorage.getItem('scan_up_token');
  return token ? { Authorization: `Bearer ${token}` } : {};
}

const subjects = ref([]);
const loading = ref(false);
const errorMsg = ref('');

const showModal = ref(false);
const editingId = ref(null);
const deleteTarget = ref(null);
const saving = ref(false);
const form = ref({ name: '' });
const formError = ref('');
const showEhrisModal = ref(false);
const ehrisLoading = ref(false);
const ehrisSyncing = ref(false);
const ehrisError = ref('');
const ehrisRows = ref([]);
const ehrisSelected = ref([]);
const ehrisDepedSchoolId = ref('');

const ehrisAllSelected = computed(() => {
  if (!ehrisRows.value.length) return false;
  return ehrisRows.value.every((row) => ehrisSelected.value.includes(row.name));
});

function formatTime(iso) {
  if (!iso) return '-';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '-';
  return d.toLocaleString();
}

async function load() {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.get('/api/admin/subjects', { headers: { ...getAuthHeaders(), Accept: 'application/json' } });
    subjects.value = res.data.data || [];
  } catch (e) {
    subjects.value = [];
    errorMsg.value = e?.response?.data?.message || 'Failed to load subjects.';
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  editingId.value = null;
  form.value = { name: '' };
  formError.value = '';
  showModal.value = true;
}

function openEdit(s) {
  editingId.value = s.id;
  form.value = { name: s.name || '' };
  formError.value = '';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingId.value = null;
}

function openDelete(s) {
  deleteTarget.value = s;
}

async function submit() {
  formError.value = '';
  saving.value = true;
  try {
    if (editingId.value) {
      await axios.put(`/api/admin/subjects/${editingId.value}`, { name: form.value.name }, { headers: { ...getAuthHeaders(), Accept: 'application/json' } });
    } else {
      await axios.post('/api/admin/subjects', { name: form.value.name }, { headers: { ...getAuthHeaders(), Accept: 'application/json' } });
    }
    closeModal();
    await load();
  } catch (e) {
    const msg = e?.response?.data?.message || 'Save failed.';
    const errors = e?.response?.data?.errors;
    formError.value = errors ? Object.values(errors).flat().join(' ') : msg;
  } finally {
    saving.value = false;
  }
}

async function doDelete() {
  if (!deleteTarget.value) return;
  saving.value = true;
  try {
    await axios.delete(`/api/admin/subjects/${deleteTarget.value.id}`, { headers: { ...getAuthHeaders(), Accept: 'application/json' } });
    deleteTarget.value = null;
    await load();
  } catch (e) {
    errorMsg.value = e?.response?.data?.message || 'Delete failed.';
  } finally {
    saving.value = false;
  }
}

async function openEhrisModal() {
  showEhrisModal.value = true;
  await loadEhrisSubjects();
}

async function loadEhrisSubjects() {
  ehrisLoading.value = true;
  ehrisError.value = '';
  try {
    const res = await axios.get('/api/admin/subjects/ehris', { headers: { ...getAuthHeaders(), Accept: 'application/json' } });
    ehrisRows.value = res.data.data || [];
    ehrisDepedSchoolId.value = res.data.deped_school_id || '';
    ehrisSelected.value = ehrisRows.value.filter((row) => !row.is_synced).map((row) => row.name);
  } catch (e) {
    ehrisRows.value = [];
    ehrisSelected.value = [];
    ehrisError.value = e?.response?.data?.message || 'Failed to fetch EHRIS subjects.';
  } finally {
    ehrisLoading.value = false;
  }
}

function toggleEhrisSubject(name) {
  if (ehrisSelected.value.includes(name)) {
    ehrisSelected.value = ehrisSelected.value.filter((item) => item !== name);
  } else {
    ehrisSelected.value = [...ehrisSelected.value, name];
  }
}

function toggleAllEhris() {
  ehrisSelected.value = ehrisAllSelected.value ? [] : ehrisRows.value.map((row) => row.name);
}

async function syncEhrisSubjects(subjectNames = null) {
  ehrisSyncing.value = true;
  ehrisError.value = '';
  try {
    await axios.post('/api/admin/subjects/sync-ehris', subjectNames ? { subjects: subjectNames } : {}, {
      headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    await Promise.all([load(), loadEhrisSubjects()]);
  } catch (e) {
    ehrisError.value = e?.response?.data?.message || 'Failed to sync EHRIS subjects.';
  } finally {
    ehrisSyncing.value = false;
  }
}

async function syncAllEhrisSubjects() {
  await syncEhrisSubjects(null);
}

async function syncSelectedEhrisSubjects() {
  await syncEhrisSubjects([...ehrisSelected.value]);
}

onMounted(load);
</script>
