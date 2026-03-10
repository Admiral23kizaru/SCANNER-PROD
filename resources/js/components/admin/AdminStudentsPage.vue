<template>
  <div>
    <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
      <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold text-slate-900">Master Student List</h1>
        <button
          type="button"
          class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 shadow-sm transition inline-flex items-center gap-2"
          @click="openCreateModal"
        >
          <PencilLine class="h-5 w-5" />
          Create Student
        </button>
      </div>
      <div class="p-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-white">
        <label class="flex items-center gap-2 text-sm text-slate-600">
          Show
          <select
            v-model.number="perPage"
            class="rounded border border-slate-300 px-2 py-1.5 text-sm text-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
            @change="currentPage = 1; load()"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
          entries
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-600 w-full sm:w-auto">
          <span>Search:</span>
          <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
              <Search class="h-4 w-4" />
            </span>
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Search by name, LRN, grade/section..."
              class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-1.5 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
              @input="debouncedFetch"
            />
          </div>
        </label>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-separate border-spacing-0">
          <thead class="bg-slate-900 text-slate-50 text-xs uppercase tracking-wide">
            <tr>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">#</th>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">Full Name</th>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">LRN</th>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">Grade/Section</th>
              <th class="py-3 px-4 font-semibold text-right border-b border-slate-800/80">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, idx) in students"
              :key="row.id"
              class="border-b border-slate-200/80 odd:bg-slate-50/40 even:bg-white hover:bg-blue-50/60 transition"
            >
              <td class="py-3 px-4 text-slate-500 tabular-nums">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
              <td class="py-3 px-4 font-medium text-slate-900">{{ row.full_name }}</td>
              <td class="py-3 px-4 tabular-nums text-slate-700">{{ row.student_number }}</td>
              <td class="py-3 px-4 text-slate-700">{{ row.grade_section }}</td>
              <td class="py-3 px-4 text-right">
                <span class="inline-flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-600 text-white hover:bg-green-700 transition shadow-sm"
                    title="Generate ID Card"
                    @click="downloadId(row.id)"
                  >
                    <IdCard class="h-5 w-5" />
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm"
                    title="Edit student"
                    @click="openEditModal(row)"
                  >
                    <PencilLine class="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-500 text-white hover:bg-red-600 transition shadow-sm"
                    title="Delete student"
                    @click="confirmDelete(row)"
                  >
                    <Trash2 class="h-4 w-4" />
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
      <div class="p-4 border-t border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50/60">
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
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold text-stone-800 p-6 pb-0">{{ editingId ? 'Edit Student' : 'Create Student' }}</h2>
        <form @submit.prevent="submitForm" class="p-6 overflow-y-auto flex-1">
          <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">First Name</label>
                <input v-model="form.first_name" type="text" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Last Name</label>
                <input v-model="form.last_name" type="text" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Middle Name</label>
              <input v-model="form.middle_name" type="text" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">LRN</label>
              <input v-model="form.student_number" type="text" required :readonly="!!editingId" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Grade</label>
                <input v-model="form.grade" type="text" placeholder="e.g. 7" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Section</label>
                <input v-model="form.section" type="text" placeholder="e.g. A" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Guardian</label>
              <input v-model="form.guardian" type="text" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Parent Email</label>
              <input v-model="form.parent_email" type="email" placeholder="parent@gmail.com" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Contact Number</label>
              <input v-model="form.contact_number" type="text" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>
          </div>

          <div v-if="formError" class="mt-2 text-sm text-red-600">{{ formError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="rounded-md border border-stone-300 px-4 py-2 text-sm" @click="closeForm">Cancel</button>
            <button type="submit" class="rounded-md bg-blue-800 px-4 py-2 text-sm font-medium text-white hover:bg-blue-900">
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
        <h2 class="text-lg font-semibold text-stone-800 mb-2">Delete Student</h2>
        <p class="text-sm text-stone-600 mb-4">
          Are you sure you want to delete <strong>{{ deleteTarget?.full_name }}</strong> ({{ deleteTarget?.student_number }})?
          All attendance records for this student will be permanently deleted.
        </p>
        <div class="flex justify-end gap-2">
          <button
            type="button"
            class="rounded-md border border-stone-300 px-4 py-2 text-sm"
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
import { Search, PencilLine, Trash2, IdCard } from 'lucide-vue-next';
import { fetchAdminStudents, createAdminStudent, updateAdminStudent, deleteStudent, getAdminStudentIdUrl } from '../../services/adminService';

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

async function downloadId(id) {
  try {
    const res = await getAdminStudentIdUrl(id);
    if (res?.url) {
      window.open(res.url, '_blank', 'noopener,noreferrer');
    }
  } catch (err) {
    console.error(err);
    alert('Failed to generate secure ID link.');
  }
}

load();
</script>
