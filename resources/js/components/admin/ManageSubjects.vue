<template>
  <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
    <div class="p-4 sm:p-5 border-b border-slate-200 bg-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Manage Subjects</h2>
        <p class="text-sm text-slate-500 mt-1">Add, edit, delete subjects and use them for Semestral Assessment</p>
      </div>
      <button
        type="button"
        class="rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-800 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-sky-200"
        @click="openCreate"
      >
        Add Subject
      </button>
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

  <!-- Create/Edit Modal -->
  <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeModal">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full border border-slate-200" @click.stop>
      <div class="p-5 border-b border-slate-200">
        <h3 class="text-base font-semibold text-slate-900">{{ editingId ? 'Edit Subject' : 'Add Subject' }}</h3>
      </div>
      <form class="p-5 space-y-3" @submit.prevent="submit">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Subject area</label>
          <select
            v-model="form.name"
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:bg-slate-50"
            :disabled="subjectAreas.length === 0"
            required
          >
            <option value="" disabled>Select subject area</option>
            <option v-for="area in subjectAreas" :key="area" :value="area">{{ area }}</option>
          </select>
          <p v-if="areasLoading" class="mt-1 text-xs text-slate-500">Loading subject areas from EHRIS…</p>
          <p v-else-if="subjectAreas.length === 0" class="mt-1 text-xs text-amber-700">
            No subject areas found. Check EHRIS connection and that <code class="text-[11px]">tbl_subject_area</code> has data, then click Add Subject again.
          </p>
        </div>
        <div v-if="formError" class="text-sm text-red-600">{{ formError }}</div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm" @click="closeModal">Cancel</button>
          <button
            type="submit"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="saving || (!editingId && subjectAreas.length === 0)"
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
import { onMounted, ref } from 'vue';

function getAuthHeaders() {
  const token = localStorage.getItem('scan_up_token');
  return token ? { Authorization: `Bearer ${token}` } : {};
}

const subjects = ref([]);
const subjectAreas = ref([]);
const areasLoading = ref(false);
const loading = ref(false);
const errorMsg = ref('');

const showModal = ref(false);
const editingId = ref(null);
const deleteTarget = ref(null);
const saving = ref(false);
const form = ref({ name: '' });
const formError = ref('');

function formatTime(iso) {
  if (!iso) return '-';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '-';
  return d.toLocaleString();
}

async function loadSubjectAreas() {
  areasLoading.value = true;
  try {
    const res = await axios.get('/api/admin/subjects/areas', {
      headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    subjectAreas.value = res.data.areas || res.data.data || [];
  } catch (e) {
    subjectAreas.value = [];
    formError.value = e?.response?.data?.message || 'Could not load subject areas from EHRIS.';
  } finally {
    areasLoading.value = false;
  }
}

async function load() {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.get('/api/admin/subjects', { headers: { ...getAuthHeaders(), Accept: 'application/json' } });
    subjects.value = res.data.data || [];
    subjectAreas.value = res.data.areas || [];
    if (subjectAreas.value.length === 0) {
      await loadSubjectAreas();
    }
  } catch (e) {
    subjects.value = [];
    errorMsg.value = e?.response?.data?.message || 'Failed to load subjects.';
  } finally {
    loading.value = false;
  }
}

async function openCreate() {
  editingId.value = null;
  formError.value = '';
  await loadSubjectAreas();
  form.value = { name: subjectAreas.value[0] || '' };
  showModal.value = true;
}

async function openEdit(s) {
  editingId.value = s.id;
  form.value = { name: s.name || '' };
  formError.value = '';
  await loadSubjectAreas();
  if (s.name && !subjectAreas.value.includes(s.name)) {
    subjectAreas.value = [...subjectAreas.value, s.name].sort((a, b) => a.localeCompare(b));
  }
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

onMounted(load);
</script>
