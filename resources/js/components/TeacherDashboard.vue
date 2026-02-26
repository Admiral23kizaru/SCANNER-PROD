<template>
  <div class="min-h-screen bg-slate-100 text-slate-800">
    <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-10">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-lg font-bold shadow">
              T
            </div>
            <div>
              <h1 class="text-xl font-bold text-slate-800 tracking-tight">Teacher Dashboard</h1>
              <p class="text-xs text-slate-500">ScanUp · Manage your students</p>
            </div>
          </div>
          <button
            type="button"
            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 border border-slate-200 hover:border-red-200 hover:text-red-600 transition flex items-center gap-2"
            @click="logout"
          >
            <span aria-hidden="true">⎋</span>
            Log out
          </button>
        </div>
      </div>
    </header>

    <div class="max-w-6xl mx-auto p-4 sm:p-6">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
          <h2 class="text-lg font-semibold text-slate-800">List of Learners</h2>
          <button
            type="button"
            class="rounded-lg bg-slate-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800 shadow-sm transition inline-flex items-center gap-2"
            @click="openAddModal"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Add Learner
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
              placeholder="Search..."
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
                <th class="py-3 px-4 font-semibold">Last Name</th>
                <th class="py-3 px-4 font-semibold">First Name</th>
                <th class="py-3 px-4 font-semibold">Middle Name</th>
                <th class="py-3 px-4 font-semibold">Grade</th>
                <th class="py-3 px-4 font-semibold">Section</th>
                <th class="py-3 px-4 font-semibold">LRN</th>
                <th class="py-3 px-4 font-semibold text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in students"
                :key="row.id"
                class="border-b border-slate-200 hover:bg-slate-50 transition"
              >
                <td class="py-3 px-4 text-slate-500 tabular-nums">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                <td class="py-3 px-4 font-medium text-slate-800">{{ row.last_name }}</td>
                <td class="py-3 px-4 text-slate-700">{{ row.first_name }}</td>
                <td class="py-3 px-4 text-slate-600">{{ row.middle_name || '—' }}</td>
                <td class="py-3 px-4 text-slate-700">{{ row.grade || row.grade_section || '—' }}</td>
                <td class="py-3 px-4 text-slate-700">{{ row.section || '—' }}</td>
                <td class="py-3 px-4 tabular-nums text-slate-600">{{ row.student_number }}</td>
                <td class="py-3 px-4 text-right">
                  <span class="inline-flex items-center justify-end gap-1">
                    <button
                      type="button"
                      class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm"
                      title="Make ID"
                      @click="downloadId(row.id)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm"
                      title="View learner"
                      @click="openViewModal(row)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-500 text-white hover:bg-slate-600 transition shadow-sm"
                      title="Edit"
                      @click="openEditModal(row)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                    </button>
                  </span>
                </td>
              </tr>
              <tr v-if="loading && students.length === 0">
                <td colspan="8" class="py-12 text-center text-slate-500">Loading…</td>
              </tr>
              <tr v-if="!loading && students.length === 0">
                <td colspan="8" class="py-12 text-center text-slate-500">No learners found.</td>
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
            <span class="text-sm text-slate-600 px-2">
              Page {{ currentPage }} of {{ lastPage || 1 }}
            </span>
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
    </div>
    <div
      v-if="showFormModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showFormModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-slate-200" @click.stop>
        <h2 class="text-lg font-semibold text-slate-800 p-6 pb-0">{{ editingId ? 'Edit Student' : 'Add Learner' }}</h2>
        <form @submit.prevent="submitForm" class="p-6 overflow-y-auto flex-1">
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Last Name</label>
              <input
                v-model="form.last_name"
                type="text"
                required
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">First Name</label>
              <input
                v-model="form.first_name"
                type="text"
                required
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Middle Name</label>
              <input
                v-model="form.middle_name"
                type="text"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Grade</label>
                <input
                  v-model="form.grade"
                  type="text"
                  placeholder="e.g. 7"
                  class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Section</label>
                <input
                  v-model="form.section"
                  type="text"
                  placeholder="e.g. A"
                  class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Guardian</label>
              <input
                v-model="form.guardian"
                type="text"
                placeholder="Guardian name"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Parent Email (Gmail)</label>
              <input
                v-model="form.parent_email"
                type="email"
                placeholder="e.g. parent@gmail.com"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">LRN</label>
              <input
                v-model="form.student_number"
                type="text"
                required
                :readonly="!!editingId"
                placeholder="Learner Reference Number"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div class="rounded-lg border-2 border-dashed border-slate-300 bg-slate-50/80 p-4">
              <label class="block text-sm font-medium text-slate-700 mb-2">Photo <span class="text-xs text-slate-400 font-normal">(PNG only)</span></label>
              <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 transition shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Choose file
                  <input
                    ref="photoInputRef"
                    type="file"
                    accept="image/png,.png"
                    class="sr-only"
                    @change="onPhotoChange"
                  />
                </label>
                <span class="text-sm text-slate-500">{{ photoFileName || 'No file chosen' }}</span>
              </div>
              <p v-if="photoError" class="mt-1 text-xs text-red-500">{{ photoError }}</p>
            </div>
          </div>
          <div v-if="formError" class="mt-2 text-sm text-red-600">{{ formError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button
              type="button"
              class="rounded-md border border-slate-300 px-4 py-2 text-sm"
              @click="showFormModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
            >
              {{ editingId ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div
      v-if="showViewModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showViewModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full border border-slate-200" @click.stop>
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Learner details</h2>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between gap-4"><dt class="text-slate-500">Last name</dt><dd class="font-medium text-slate-800">{{ viewModalStudent?.last_name }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-slate-500">First name</dt><dd class="font-medium text-slate-800">{{ viewModalStudent?.first_name }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-slate-500">Middle name</dt><dd class="text-slate-700">{{ viewModalStudent?.middle_name || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-slate-500">Grade</dt><dd class="text-slate-700">{{ viewModalStudent?.grade || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-slate-500">Section</dt><dd class="text-slate-700">{{ viewModalStudent?.section || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-slate-500">LRN</dt><dd class="tabular-nums text-slate-700">{{ viewModalStudent?.student_number }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-slate-500">Guardian</dt><dd class="text-slate-700">{{ viewModalStudent?.guardian || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-slate-500">Parent Email</dt><dd class="text-slate-700">{{ viewModalStudent?.parent_email || '—' }}</dd></div>
        </dl>
        <div class="mt-4 flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 transition"
            @click="showViewModal = false; openQrModal(viewModalStudent)"
          >
            Show QR
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 transition"
            @click="showViewModal = false"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="showQrModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showQrModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl p-6 text-center border border-slate-200" @click.stop>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">QR Code</h2>
        <p class="text-sm text-slate-500 mb-4">{{ qrModalStudent?.full_name }} ({{ qrModalStudent?.student_number }})</p>
        <div class="inline-block p-4 bg-slate-50 border border-slate-200 rounded-lg">
          <canvas ref="qrCanvas" width="200" height="200" />
        </div>
        <div class="mt-4">
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 transition"
            @click="showQrModal = false"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import QRCode from 'qrcode';
import { setStoredToken, getStoredToken } from '../router';
import { fetchStudents, createStudent, createStudentWithFormData, updateStudent, updateStudentWithFormData, uploadStudentPhoto } from '../services/studentService';

const router = useRouter();

const students = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(10);
const searchQuery = ref('');
const searchInput = ref('');

const showFormModal = ref(false);
const showViewModal = ref(false);
const showQrModal = ref(false);
const viewModalStudent = ref(null);
const editingId = ref(null);
const form = ref({
  first_name: '',
  last_name: '',
  middle_name: '',
  grade: '',
  section: '',
  grade_section: '',
  guardian: '',
  parent_email: '',
  contact_number: '',
  student_number: '',
});
const formError = ref('');
const qrModalStudent = ref(null);
const qrCanvas = ref(null);
const qrDataUrl = ref('');
const photoInputRef = ref(null);
const photoFile = ref(null);
const photoFileName = ref('');
const photoError = ref('');

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
    const res = await fetchStudents({
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

function onPhotoChange(e) {
  const file = e.target.files?.[0];
  photoError.value = '';
  if (file) {
    if (file.type !== 'image/png') {
      photoError.value = 'Only PNG images are accepted.';
      photoFile.value = null;
      photoFileName.value = '';
      if (photoInputRef.value) photoInputRef.value.value = '';
      return;
    }
  }
  photoFile.value = file || null;
  photoFileName.value = file ? file.name : '';
}

function openAddModal() {
  editingId.value = null;
  form.value = {
    first_name: '', last_name: '', middle_name: '', grade: '', section: '',
    grade_section: '', guardian: '', parent_email: '', contact_number: '', student_number: '',
  };
  formError.value = '';
  photoFile.value = null;
  photoFileName.value = '';
  photoError.value = '';
  if (photoInputRef.value) photoInputRef.value.value = '';
  showFormModal.value = true;
}

function openEditModal(row) {
  editingId.value = row.id;
  form.value = {
    first_name: row.first_name ?? '',
    last_name: row.last_name ?? '',
    middle_name: row.middle_name ?? '',
    grade: row.grade ?? '',
    section: row.section ?? '',
    grade_section: row.grade_section ?? '',
    guardian: row.guardian ?? '',
    parent_email: row.parent_email ?? '',
    contact_number: row.contact_number ?? '',
    student_number: row.student_number ?? '',
  };
  formError.value = '';
  photoFile.value = null;
  photoFileName.value = '';
  if (photoInputRef.value) photoInputRef.value.value = '';
  showFormModal.value = true;
}

function buildFormData() {
  const fd = new FormData();
  fd.append('first_name', form.value.first_name);
  fd.append('last_name', form.value.last_name);
  fd.append('middle_name', form.value.middle_name || '');
  fd.append('student_number', form.value.student_number);
  fd.append('grade', form.value.grade || '');
  fd.append('section', form.value.section || '');
  fd.append('guardian', form.value.guardian || '');
   fd.append('parent_email', form.value.parent_email || '');
  fd.append('contact_number', form.value.contact_number || '');
  if (photoFile.value) fd.append('photo', photoFile.value);
  return fd;
}

async function submitForm() {
  formError.value = '';
  try {
    if (editingId.value) {
      let res;
      if (photoFile.value) {
        res = await updateStudentWithFormData(editingId.value, buildFormData());
      } else {
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
        res = await updateStudent(editingId.value, payload);
      }
      const updated = res.student;
      const idx = students.value.findIndex((s) => s.id === updated.id);
      if (idx >= 0) {
        students.value[idx] = { ...updated, full_name: updated.full_name };
      } else {
        await load();
      }
    } else {
      if (photoFile.value) {
        const res = await createStudentWithFormData(buildFormData());
        students.value = [res.student, ...students.value];
        total.value = (total.value || 0) + 1;
      } else {
        const res = await createStudent({
          first_name: form.value.first_name,
          last_name: form.value.last_name,
          middle_name: form.value.middle_name || '',
          student_number: form.value.student_number,
          grade: form.value.grade || '',
          section: form.value.section || '',
          guardian: form.value.guardian || '',
          parent_email: form.value.parent_email || '',
          contact_number: form.value.contact_number || '',
        });
        students.value = [res.student, ...students.value];
        total.value = (total.value || 0) + 1;
      }
    }
    showFormModal.value = false;
  } catch (err) {
    const msg = err.response?.data?.message || 'Request failed.';
    const errors = err.response?.data?.errors;
    formError.value = errors ? Object.values(errors).flat().join(' ') : msg;
  }
}

function openViewModal(row) {
  viewModalStudent.value = row;
  showViewModal.value = true;
}

function openQrModal(row) {
  qrModalStudent.value = row;
  showQrModal.value = true;
}

watch([showQrModal, qrModalStudent], async () => {
  if (!showQrModal.value || !qrModalStudent.value) return;
  await nextTick();
  const canvas = qrCanvas.value;
  if (!canvas) return;
  const lrn = String(qrModalStudent.value.student_number ?? '').trim();
  const fullName = qrModalStudent.value.full_name || '';
  const grade = qrModalStudent.value.grade || '';
  const section = qrModalStudent.value.section || '';
  const guardian = qrModalStudent.value.guardian || '';
  const email = qrModalStudent.value.parent_email || '';
  
  const qrData = `Name: ${fullName}\nLRN: ${lrn}\nGrade/Section: ${grade} ${section}\nGuardian: ${guardian}\nParent Email: ${email}`;
  
  try {
    await QRCode.toCanvas(canvas, qrData, {
      width: 350,
      margin: 2,
      errorCorrectionLevel: 'H',
    });
    try {
      qrDataUrl.value = canvas.toDataURL('image/png');
    } catch (_) {
      qrDataUrl.value = '';
    }
  } catch (e) {
    console.error('QR render failed', e);
  }
});

async function downloadId(id) {
  const token = getStoredToken();
  if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
  }
  try {
    const res = await axios.get(`/api/teacher/students/${encodeURIComponent(id)}/id-url`);
    if (res.data?.url) {
      window.open(res.data.url, '_blank', 'noopener,noreferrer');
    }
  } catch (err) {
    console.error(err);
    alert('Failed to generate secure ID link.');
  }
}

async function logout() {
  const token = getStoredToken();
  if (token) {
    try {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      await axios.post('/api/logout');
    } catch (_) {}
    setStoredToken(null);
  }
  router.push('/login');
}

load();
</script>
