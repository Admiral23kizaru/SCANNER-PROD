<template>
  <div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold text-slate-800">Manage Teachers</h1>
        <button
          type="button"
          class="rounded-lg bg-slate-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800 shadow-sm transition inline-flex items-center gap-2"
          @click="openCreateModal"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          Create Teacher
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-700 text-white">
            <tr>
              <th class="py-3 px-4 font-semibold">#</th>
              <th class="py-3 px-4 font-semibold">Name</th>
              <th class="py-3 px-4 font-semibold">Email</th>
              <th class="py-3 px-4 font-semibold">Created</th>
              <th class="py-3 px-4 font-semibold text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(t, idx) in teachers"
              :key="t.id"
              class="border-b border-slate-200 hover:bg-slate-50 transition"
            >
              <td class="py-3 px-4 text-slate-500 tabular-nums">{{ idx + 1 }}</td>
              <td class="py-3 px-4 font-medium text-slate-800">{{ t.name }}</td>
              <td class="py-3 px-4 text-slate-700">{{ t.email }}</td>
              <td class="py-3 px-4 text-slate-600">{{ formatDate(t.created_at) }}</td>
              <td class="py-3 px-4 text-right">
                <span class="inline-flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm"
                    title="Edit teacher"
                    @click="openEditModal(t)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-600/90 text-white hover:bg-red-600 transition shadow-sm"
                    title="Delete teacher"
                    @click="confirmDelete(t)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </span>
              </td>
            </tr>
            <tr v-if="loading && teachers.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-500">Loading…</td>
            </tr>
            <tr v-if="!loading && teachers.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-500">No teachers yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50/30">
        <span class="text-sm text-slate-600">
          Showing {{ teachers.length }} of {{ teachers.length }} entries
        </span>
      </div>
    </div>

    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showCreateModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200" @click.stop>
        <h2 class="text-lg font-semibold mb-4">Create Teacher Account</h2>
        <form @submit.prevent="submitCreate">
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Email (Username)</label>
              <input
                v-model="form.email"
                type="email"
                required
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
              <input
                v-model="form.password"
                type="password"
                required
                minlength="8"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
              />
            </div>
          </div>
          <div v-if="formError" class="mt-2 text-sm text-red-600">{{ formError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button
              type="button"
              class="rounded-md border border-slate-300 px-4 py-2 text-sm"
              @click="showCreateModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
            >
              Create
            </button>
          </div>
        </form>
      </div>
    </div>

    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showEditModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200" @click.stop>
        <h2 class="text-lg font-semibold mb-4">Edit Teacher</h2>
        <form @submit.prevent="submitEdit">
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
              <input v-model="editForm.name" type="text" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
              <input v-model="editForm.email" type="email" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
              <p class="text-xs text-slate-500 mb-2">Optional: set a new password for this teacher.</p>
              <div class="space-y-2">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
                  <input v-model="editForm.password" type="password" minlength="8" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Confirm New Password</label>
                  <input v-model="editForm.password_confirmation" type="password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                </div>
              </div>
            </div>
          </div>
          <div v-if="editError" class="mt-2 text-sm text-red-600">{{ editError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm" @click="showEditModal = false">Cancel</button>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save</button>
          </div>
        </form>
      </div>
    </div>

    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showDeleteModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" @click.stop>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Delete Teacher</h2>
        <p class="text-sm text-slate-600 mb-4">
          Are you sure you want to delete <strong>{{ deleteTarget?.name }}</strong> ({{ deleteTarget?.email }})?
        </p>
        <div v-if="deleteError" class="mb-3 text-sm text-red-600">{{ deleteError }}</div>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm" @click="showDeleteModal = false">Cancel</button>
          <button type="button" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50" :disabled="deleting" @click="executeDelete">
            {{ deleting ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchTeachers, createTeacher, updateTeacher, deleteTeacher } from '../../services/adminService';

const teachers = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);
const deleteError = ref('');

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});
const formError = ref('');

const editTargetId = ref(null);
const editForm = ref({ name: '', email: '', password: '', password_confirmation: '' });
const editError = ref('');

function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString();
}

function openCreateModal() {
  form.value = { name: '', email: '', password: '', password_confirmation: '' };
  formError.value = '';
  showCreateModal.value = true;
}

function openEditModal(t) {
  editTargetId.value = t.id;
  editForm.value = { name: t.name || '', email: t.email || '', password: '', password_confirmation: '' };
  editError.value = '';
  showEditModal.value = true;
}

function confirmDelete(t) {
  deleteTarget.value = t;
  deleteError.value = '';
  showDeleteModal.value = true;
}

async function load() {
  loading.value = true;
  try {
    const res = await fetchTeachers();
    teachers.value = res.data || [];
  } catch {
    teachers.value = [];
  } finally {
    loading.value = false;
  }
}

async function submitCreate() {
  formError.value = '';
  if (form.value.password !== form.value.password_confirmation) {
    formError.value = 'Passwords do not match.';
    return;
  }
  try {
    await createTeacher(form.value);
    showCreateModal.value = false;
    await load();
  } catch (err) {
    const msg = err.response?.data?.message || 'Request failed.';
    const errors = err.response?.data?.errors;
    formError.value = errors ? Object.values(errors).flat().join(' ') : msg;
  }
}

async function submitEdit() {
  editError.value = '';
  if (!editTargetId.value) return;

  if ((editForm.value.password || editForm.value.password_confirmation) && editForm.value.password !== editForm.value.password_confirmation) {
    editError.value = 'Passwords do not match.';
    return;
  }

  const payload = {
    name: editForm.value.name,
    email: editForm.value.email,
  };
  if (editForm.value.password) {
    payload.password = editForm.value.password;
    payload.password_confirmation = editForm.value.password_confirmation;
  }

  try {
    await updateTeacher(editTargetId.value, payload);
    showEditModal.value = false;
    editTargetId.value = null;
    await load();
  } catch (err) {
    const msg = err.response?.data?.message || 'Request failed.';
    const errors = err.response?.data?.errors;
    editError.value = errors ? Object.values(errors).flat().join(' ') : msg;
  }
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  deleting.value = true;
  deleteError.value = '';
  try {
    await deleteTeacher(deleteTarget.value.id);
    showDeleteModal.value = false;
    deleteTarget.value = null;
    await load();
  } catch (err) {
    deleteError.value = err.response?.data?.message || 'Delete failed.';
  } finally {
    deleting.value = false;
  }
}

onMounted(load);
</script>
