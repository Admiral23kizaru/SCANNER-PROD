<template>
  <div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold text-slate-800">Master Student List</h1>
        <button
          type="button"
          class="rounded-lg bg-slate-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800 shadow-sm transition inline-flex items-center gap-2"
          @click="openCreateModal"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          Create Student
        </button>
      </div>
      <div class="p-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
        <label class="flex items-center gap-2 text-sm text-slate-600">
          Show
          <select
            v-model.number="perPage"
            class="rounded border border-slate-300 px-2 py-1.5 text-sm text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            @change="currentPage = 1; load()"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
          entries
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-600">
          Search:
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search by name, LRN, grade/section..."
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm w-48 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            @input="debouncedFetch"
          />
        </label>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-700 text-white">
            <tr>
              <th class="py-3 px-4 font-semibold">#</th>
              <th class="py-3 px-4 font-semibold">Full Name</th>
              <th class="py-3 px-4 font-semibold">LRN</th>
              <th class="py-3 px-4 font-semibold">Grade/Section</th>
              <th class="py-3 px-4 font-semibold text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, idx) in students"
              :key="row.id"
              class="border-b border-slate-200 hover:bg-slate-50 transition"
            >
              <td class="py-3 px-4 text-slate-500 tabular-nums">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
              <td class="py-3 px-4 font-medium text-slate-800">{{ row.full_name }}</td>
              <td class="py-3 px-4 tabular-nums text-slate-600">{{ row.student_number }}</td>
              <td class="py-3 px-4 text-slate-700">{{ row.grade_section }}</td>
              <td class="py-3 px-4 text-right">
                <span class="inline-flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm"
                    title="Edit student"
                    @click="openEditModal(row)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-600/90 text-white hover:bg-red-600 transition shadow-sm"
                    title="Delete student"
                    @click="confirmDelete(row)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </span>
              </td>
            </tr>
            <tr v-if="loading && students.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-500">Loading…</td>
            </tr>
            <tr v-if="!loading && students.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-500">No students found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50/30">
        <span class="text-sm text-slate-600">
          Showing {{ total ? (currentPage - 1) * perPage + 1 : 0 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} entries
        </span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="currentPage <= 1"
            @click="goToPage(currentPage - 1)"
          >
            Previous
          </button>
          <span class="text-sm text-slate-600 px-2">Page {{ currentPage }} of {{ lastPage || 1 }}</span>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="currentPage >= lastPage"
            @click="goToPage(currentPage + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="showFormModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeForm"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-slate-200" @click.stop>
        <h2 class="text-lg font-semibold text-slate-800 p-6 pb-0">{{ editingId ? 'Edit Student' : 'Create Student' }}</h2>
        <form @submit.prevent="submitForm" class="p-6 overflow-y-auto flex-1">
          <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">First Name</label>
                <input v-model="form.first_name" type="text" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Last Name</label>
                <input v-model="form.last_name" type="text" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Middle Name</label>
              <input v-model="form.middle_name" type="text" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">LRN</label>
              <input v-model="form.student_number" type="text" required :readonly="!!editingId" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Grade</label>
                <input v-model="form.grade" type="text" placeholder="e.g. 7" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Section</label>
                <input v-model="form.section" type="text" placeholder="e.g. A" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Guardian</label>
              <input v-model="form.guardian" type="text" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Parent Email</label>
              <input v-model="form.parent_email" type="email" placeholder="parent@gmail.com" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Contact Number</label>
              <input v-model="form.contact_number" type="text" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            </div>
          </div>

          <div v-if="formError" class="mt-2 text-sm text-red-600">{{ formError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm" @click="closeForm">Cancel</button>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
              {{ editingId ? 'Save' : 'Create' }}
            </button>
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
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Delete Student</h2>
        <p class="text-sm text-slate-600 mb-4">
          Are you sure you want to delete <strong>{{ deleteTarget?.full_name }}</strong> ({{ deleteTarget?.student_number }})?
          All attendance records for this student will be permanently deleted.
        </p>
        <div class="flex justify-end gap-2">
          <button
            type="button"
            class="rounded-md border border-slate-300 px-4 py-2 text-sm"
            @click="showDeleteModal = false"
          >
            Cancel
          </button>
          <button
            type="button"
            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
            :disabled="deleting"
            @click="executeDelete"
          >
            {{ deleting ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { fetchAdminStudents, createAdminStudent, updateAdminStudent, deleteStudent } from '../../services/adminService';

const students = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(10);
const searchQuery = ref('');
const searchInput = ref('');
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

const showFormModal = ref(false);
const editingId = ref(null);
const form = ref({
  first_name: '',
  last_name: '',
  middle_name: '',
  student_number: '',
  grade: '',
  section: '',
  guardian: '',
  parent_email: '',
  contact_number: '',
});
const formError = ref('');

let debounceTimer = null;

function debouncedFetch() {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    searchInput.value = searchQuery.value;
    currentPage.value = 1;
    load();
  }, 300);
}

async function load() {
  loading.value = true;
  try {
    const res = await fetchAdminStudents({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchInput.value || undefined,
    });
    students.value = res.data || [];
    currentPage.value = res.current_page ?? 1;
    lastPage.value = res.last_page ?? 1;
    total.value = res.total ?? 0;
  } catch {
    students.value = [];
  } finally {
    loading.value = false;
  }
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value) return;
  currentPage.value = page;
  load();
}

function confirmDelete(row) {
  deleteTarget.value = row;
  showDeleteModal.value = true;
}

function openCreateModal() {
  editingId.value = null;
  form.value = {
    first_name: '',
    last_name: '',
    middle_name: '',
    student_number: '',
    grade: '',
    section: '',
    guardian: '',
    parent_email: '',
    contact_number: '',
  };
  formError.value = '';
  showFormModal.value = true;
}

function openEditModal(row) {
  editingId.value = row.id;
  form.value = {
    first_name: row.first_name ?? '',
    last_name: row.last_name ?? '',
    middle_name: row.middle_name ?? '',
    student_number: row.student_number ?? '',
    grade: row.grade ?? '',
    section: row.section ?? '',
    guardian: row.guardian ?? '',
    parent_email: row.parent_email ?? '',
    contact_number: row.contact_number ?? '',
  };
  formError.value = '';
  showFormModal.value = true;
}

function closeForm() {
  showFormModal.value = false;
  editingId.value = null;
}

async function submitForm() {
  formError.value = '';
  const payload = {
    first_name: form.value.first_name,
    last_name: form.value.last_name,
    middle_name: form.value.middle_name || '',
    student_number: form.value.student_number,
    grade: form.value.grade || '',
    section: form.value.section || '',
    guardian: form.value.guardian || '',
    parent_email: form.value.parent_email || '',
    contact_number: form.value.contact_number || '',
  };
  try {
    if (editingId.value) {
      await updateAdminStudent(editingId.value, payload);
    } else {
      await createAdminStudent(payload);
    }
    closeForm();
    await load();
  } catch (err) {
    const msg = err.response?.data?.message || 'Request failed.';
    const errors = err.response?.data?.errors;
    formError.value = errors ? Object.values(errors).flat().join(' ') : msg;
  }
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  deleting.value = true;
  try {
    await deleteStudent(deleteTarget.value.id);
    showDeleteModal.value = false;
    deleteTarget.value = null;
    await load();
  } catch (err) {
    const msg = err.response?.data?.message || 'Delete failed.';
    alert(msg);
  } finally {
    deleting.value = false;
  }
}

load();
</script>
