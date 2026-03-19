<template>
  <div>
    <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
      <!-- Toolbar matching AdminTeachersPage layout -->
      <div class="p-4 sm:p-5 border-b border-slate-200 bg-white flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 shadow-sm transition inline-flex items-center gap-2"
            @click="openCreateModal"
          >
            <Plus class="h-4 w-4" />
            Create Student
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition inline-flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="handleExport"
            :disabled="exporting"
          >
            <Download class="h-4 w-4" />
            {{ exporting ? 'Exporting...' : 'Export' }}
          </button>
        </div>

        <div class="flex items-center gap-2">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Search students..."
              class="w-64 max-w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              @input="debouncedFetch"
            />
          </div>
          <button
            type="button"
            class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition"
            title="Filter"
          >
            <Filter class="h-4 w-4" />
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-separate border-spacing-0">
          <thead class="bg-slate-50 text-slate-500 text-xs font-medium">
            <tr>
              <th class="py-3 px-4 border-b border-slate-200">Photo</th>
              <th class="py-3 px-4 border-b border-slate-200">Full Name</th>
              <th class="py-3 px-4 border-b border-slate-200">LRN</th>
              <th class="py-3 px-4 border-b border-slate-200">Grade / Section</th>
              <th class="py-3 px-4 border-b border-slate-200">Guardian</th>
              <th class="py-3 px-4 text-right border-b border-slate-200">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row) in students"
              :key="row.id"
              class="border-b border-slate-100 hover:bg-slate-50 transition"
            >
               <td class="py-3 px-4">
                <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0 shadow-sm">
                  <img
                    v-if="row.photo_path && !photoLoadError[row.id]"
                    :src="getPhotoUrl(row.photo_path)"
                    alt=""
                    class="w-full h-full object-cover"
                    @error="photoLoadError[row.id] = true"
                  />
                  <div
                    v-else
                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-blue-500 text-white font-semibold text-xs"
                  >
                    <img v-if="photoLoadError[row.id]" :src="'/images/default-avatar.png'" class="w-full h-full object-cover" />
                    <span v-else>{{ row.first_name?.charAt(0) || '?' }}</span>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4">
                <div class="min-w-0">
                  <div class="font-medium text-slate-900 truncate">{{ row.full_name }}</div>
                </div>
              </td>
              <td class="py-3 px-4 font-mono text-slate-700 whitespace-nowrap">{{ row.student_number }}</td>
              <td class="py-3 px-4 text-slate-700">{{ row.grade_section || '—' }}</td>
              <td class="py-3 px-4 text-slate-600">{{ row.guardian || '—' }}</td>
              <td class="py-3 px-4 text-right">
                <span class="inline-flex items-center justify-end gap-3">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                    title="Generate ID Card"
                    @click="downloadId(row.id)"
                  >
                    <IdCard class="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                    title="Edit student"
                    @click="openEditModal(row)"
                  >
                    <PencilLine class="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-red-600 hover:bg-slate-100 transition"
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

      <!-- Footer pagination -->
      <div class="p-4 border-t border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50/60">
        <span class="text-sm text-slate-600">
          Showing {{ total ? (currentPage - 1) * perPage + 1 : 0 }}–{{ Math.min(currentPage * perPage, total) }} of {{ total }} entries
        </span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="currentPage <= 1"
            @click="goToPage(currentPage - 1)"
            title="Previous"
          >
            <ChevronLeft class="h-4 w-4" />
          </button>
          <span class="text-sm text-slate-600 px-1">{{ currentPage }} / {{ lastPage || 1 }}</span>
          <button
            type="button"
            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="currentPage >= lastPage"
            @click="goToPage(currentPage + 1)"
            title="Next"
          >
            <ChevronRight class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div
      v-if="showFormModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeForm"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold p-6 pb-0">{{ editingId ? 'Edit Student' : 'Create Student' }}</h2>
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
              <label class="block text-sm font-medium text-stone-700 mb-1">LRN <span class="text-xs text-stone-400 font-normal">(12 digits)</span></label>
              <input v-model="form.student_number" type="text" required :readonly="!!editingId" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm font-mono" maxlength="12" />
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
              <label class="block text-sm font-medium text-stone-700 mb-1">Guardian Email</label>
              <input v-model="form.guardian_email" type="email" placeholder="parent@gmail.com" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Contact Number</label>
              <input v-model="form.contact_number" type="text" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Notification Preference</label>
              <select
                v-model.number="form.notification_preference"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm bg-white"
              >
                <option :value="0">No SMS — Email only (free, unlimited)</option>
                <option :value="1">Regular SMS — 1 SMS per day + Email</option>
                <option :value="2">VIP SMS — Every scan SMS + Email</option>
              </select>
              <p class="mt-1 text-xs text-stone-400">Email is always sent on every scan regardless of this setting.</p>
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

    <!-- Delete Modal -->
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
          <button type="button" class="rounded-md border border-stone-300 px-4 py-2 text-sm" @click="showDeleteModal = false">Cancel</button>
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
import { ref, onMounted } from 'vue';
import { Search, PencilLine, Trash2, IdCard, Plus, Download, Filter, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { fetchAdminStudents, createAdminStudent, updateAdminStudent, deleteStudent, getAdminStudentIdUrl, exportAdminStudents } from '../../services/adminService';

const students = ref([]);
const loading = ref(false);
const exporting = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(15);
const searchQuery = ref('');
const searchInput = ref('');
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);
const photoLoadError = ref({});

function getPhotoUrl(path) {
  if (!path) return '/images/default-avatar.png';
  // Strip 'public/' or 'storage/' or leading slashes
  const cleanPath = path.replace(/^(public\/|storage\/|\/storage\/|\/public\/)/, '').replace(/^\//, '');
  return '/storage/' + cleanPath;
}

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
  guardian_email: '',
  contact_number: '',
  notification_preference: 0,
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

async function handleExport() {
  if (exporting.value) return;
  exporting.value = true;
  try {
    const blob = await exportAdminStudents({ search: searchInput.value || undefined });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = 'students_export.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    alert('Failed to export students.');
  } finally {
    exporting.value = false;
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
    first_name: '', last_name: '', middle_name: '',
    student_number: '', grade: '', section: '',
    guardian: '', guardian_email: '', contact_number: '',
    notification_preference: 0,
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
    guardian_email: row.guardian_email ?? '',
    contact_number: row.contact_number ?? '',
    notification_preference: row.notification_preference ?? 0,
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
    guardian_email: form.value.guardian_email || '',
    contact_number: form.value.contact_number || '',
    notification_preference: form.value.notification_preference ?? 0,
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
    alert(err.response?.data?.message || 'Delete failed.');
  } finally {
    deleting.value = false;
  }
}

async function downloadId(id) {
  try {
    const res = await getAdminStudentIdUrl(id);
    if (res?.url) window.open(res.url, '_blank', 'noopener,noreferrer');
  } catch {
    alert('Failed to generate secure ID link.');
  }
}

onMounted(async () => {
  await load();
  const flag = sessionStorage.getItem('admin_open_create_student');
  if (flag) {
    sessionStorage.removeItem('admin_open_create_student');
    openCreateModal();
  }
});
</script>
