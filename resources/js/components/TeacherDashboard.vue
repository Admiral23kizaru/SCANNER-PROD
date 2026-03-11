<template>
  <div class="min-h-screen bg-stone-50 text-stone-900 flex">
    <!-- Sidebar -->
    <aside
      class="w-64 shrink-0 flex flex-col border-r border-stone-200 bg-white fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:static lg:transform-none"
      :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <!-- Brand -->
      <div class="px-6 py-3 border-b border-stone-700" style="background-color: #050517;">
        <div class="flex items-center gap-3">
          <img
            :src="depedLogo"
            alt="Logo"
            class="h-10 w-auto rounded-full object-contain bg-white"
          />
          <div class="leading-tight">
            <h1 class="text-sm font-semibold tracking-tight text-white"></h1>
            <p class="text-[17px] text-white uppercase tracking-[0.18em]">
              Teacher
            </p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
        <button
          type="button"
          class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
          :class="currentTab === 'learners' ? 'bg-stone-800 text-white shadow-sm' : 'text-stone-400 hover:bg-stone-800/50 hover:text-white'"
          @click="currentTab = 'learners'; isSidebarOpen = false"
        >
          <Users class="h-4 w-4" />
          <span>Learners List</span>
        </button>

        <button
          type="button"
          class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
          :class="currentTab === 'locator' ? 'bg-stone-800 text-white shadow-sm' : 'text-stone-400 hover:bg-stone-800/50 hover:text-white'"
          @click="currentTab = 'locator'; isSidebarOpen = false"
        >
          <FileText class="h-4 w-4" />
          <span>Locator Slips</span>
        </button>
      </nav>
    </aside>

    <!-- Mobile sidebar overlay -->
    <div
      v-if="isSidebarOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="isSidebarOpen = false"
    ></div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col bg-stone-50">
      <!-- Top navbar -->
      <header class="sticky top-0 z-10" style="background-color: #050517;">
        <div class="h-16 flex flex-wrap lg:h-16 items-center justify-between px-4 lg:px-10 py-2 border-b border-stone-700/80">
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="lg:hidden p-2 text-white hover:bg-white/10 rounded-lg transition"
              @click="isSidebarOpen = true"
            >
              <Menu class="h-5 w-5" />
            </button>
            <div class="hidden sm:block">
              <p class="text-xs font-medium tracking-[0.25em] text-stone-400 uppercase">
                {{ pageTitle }}
              </p>
              <p class="text-sm font-semibold text-white">
                {{ pageSubtitle }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 lg:gap-4">
            <template v-if="currentTab === 'learners'">
              <button
                type="button"
                class="hidden sm:flex rounded-lg border border-stone-500 bg-stone-800 px-3 py-2 text-xs font-medium text-white hover:border-stone-400 hover:bg-stone-700 transition items-center gap-1.5"
                @click="triggerBulkImport"
              >
                <Upload class="h-4 w-4" />
                Bulk Import
              </button>
              <input
                ref="bulkImportInput"
                type="file"
                accept=".csv,.xlsx,.xls"
                class="sr-only"
                @change="onBulkImportFile"
              />
              <span v-if="bulkImporting" class="text-sm text-stone-500 mr-2">Importing…</span>
              <button
                type="button"
                class="rounded-lg px-3 py-2 text-xs font-medium text-black shadow-sm transition flex items-center gap-1.5"
                style="background: linear-gradient(90deg, #03d5ff, #00ffd1);"
                @click="openAddModal"
              >
                <Plus class="h-4 w-4" />
                Add Learner
              </button>
            </template>
            <button
              type="button"
              class="grid grid-flow-col items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-white hover:bg-stone-800 transition-colors border border-white/20 ml-2"
              @click="logout"
            >
              <LogOut class="h-4 w-4" />
              <span class="hidden sm:inline">Log out</span>
            </button>
          </div>
        </div>
      </header>
    
      <main class="flex-1 overflow-auto">
        <div v-show="currentTab === 'learners'" class="w-full">
           <!-- Page Content Wrapper -->
           <div class="w-full mx-auto p-4 sm:p-6 lg:max-w-6xl">
      <!-- White card with subtle shadow -->
      <div class="bg-white rounded-lg shadow-md border border-stone-200 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-stone-200 bg-stone-50/50">
          <div v-if="bulkImportResult" class="mb-3 p-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-800">
            Imported {{ bulkImportResult.imported }} learner(s). {{ bulkImportResult.skipped ? bulkImportResult.skipped + ' skipped (duplicate or invalid).' : '' }}
          </div>
          <div v-if="bulkImportError" class="mb-3 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
            {{ bulkImportError }}
          </div>
          <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-stone-800">List of Learners</h2>
          </div>
        </div>
        <!-- Search & Filter -->
        <div class="p-4 border-b border-stone-200 flex flex-wrap items-center justify-between gap-3 bg-white">
          <label class="flex items-center gap-2 text-sm text-stone-600">
            Show
            <select
              v-model.number="perPage"
              class="rounded border border-stone-300 px-2 py-1.5 text-sm text-stone-700 focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              @change="currentPage = 1; load()"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            entries
          </label>
          <label class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-400 pointer-events-none" />
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Search..."
              class="rounded-full border border-stone-300 pl-9 pr-4 py-2 text-sm w-48 focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              @input="debouncedFetch"
            />
          </label>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="text-white" style="background-color: #050517;">
              <tr>
                <th class="py-3 px-4 font-semibold">#</th>
                <th class="py-3 px-4 font-semibold">Last Name</th>
                <th class="py-3 px-4 font-semibold">First Name</th>
                <th class="py-3 px-4 font-semibold">Middle Name</th>
                <th class="py-3 px-4 font-semibold">Grade</th>
                <th class="py-3 px-4 font-semibold">Section</th>
                <th class="py-3 px-4 font-semibold">LRN</th>
                <th class="py-3 px-4 font-semibold text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in students"
                :key="row.id"
                class="border-b border-stone-200 hover:bg-stone-50/70 transition"
              >
                <td class="py-4 px-4 text-stone-500 tabular-nums">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                <td class="py-4 px-4 font-medium text-stone-800 capitalize">{{ titleCase(row.last_name) }}</td>
                <td class="py-4 px-4 text-stone-700 capitalize">{{ titleCase(row.first_name) }}</td>
                <td class="py-4 px-4 text-stone-600 capitalize">{{ row.middle_name ? titleCase(row.middle_name) : '—' }}</td>
                <td class="py-4 px-4 text-stone-700">{{ row.grade || row.grade_section || '—' }}</td>
                <td class="py-4 px-4 text-stone-700">{{ row.section || '—' }}</td>
                <td class="py-4 px-4 font-mono text-stone-600 tabular-nums">{{ row.student_number }}</td>
                <td class="py-4 px-4 text-right">
                  <span class="inline-flex items-center justify-end gap-1">
                    <button
                      type="button"
                      class="inline-flex items-center justify-center w-9 h-9 rounded-full text-white transition shadow-sm hover:opacity-90"
                      style="background-color: #050517;"
                      title="Profile"
                      @click="openViewModal(row)"
                    >
                      <User class="h-5 w-5" />
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-stone-600 text-white hover:bg-stone-700 transition shadow-sm"
                      title="Edit Profile"
                      @click="openEditModal(row)"
                    >
                      <Pencil class="h-5 w-5" />
                    </button>
                  </span>
                </td>
              </tr>
              <tr v-if="loading && students.length === 0">
                <td colspan="8" class="py-12 text-center text-stone-500">Loading…</td>
              </tr>
              <tr v-if="!loading && students.length === 0">
                <td colspan="8" class="py-12 text-center text-stone-500">No learners found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t border-stone-200 flex items-center justify-between flex-wrap gap-3 bg-stone-50/30">
          <span class="text-sm text-stone-600">
            Showing {{ total ? (currentPage - 1) * perPage + 1 : 0 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} entries
          </span>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed transition"
              :disabled="currentPage <= 1"
              @click="goToPage(currentPage - 1)"
            >
              Previous
            </button>
            <span class="text-sm text-stone-600 px-2">
              Page {{ currentPage }} of {{ lastPage || 1 }}
            </span>
            <button
              type="button"
              class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed transition"
              :disabled="currentPage >= lastPage"
              @click="goToPage(currentPage + 1)"
            >
              Next
            </button>
          </div>
          </div>
        </div>
      </div>
      </div>
          <div v-show="currentTab === 'locator'" class="w-full">
            <TeacherLocatorSlipsPage />
          </div>
      </main>
    </div>
    <div
      v-if="showFormModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showFormModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold text-stone-800 p-6 pb-0">{{ editingId ? 'Edit Student' : 'Add Learner' }}</h2>
        <form @submit.prevent="submitForm" class="p-6 overflow-y-auto flex-1">
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Last Name</label>
              <input
                v-model="form.last_name"
                type="text"
                required
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">First Name</label>
              <input
                v-model="form.first_name"
                type="text"
                required
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Middle Name</label>
              <input
                v-model="form.middle_name"
                type="text"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
              />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Grade</label>
                <input
                  v-model="form.grade"
                  type="text"
                  placeholder="e.g. 7"
                  class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Section</label>
                <input
                  v-model="form.section"
                  type="text"
                  placeholder="e.g. A"
                  class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Guardian</label>
              <input
                v-model="form.guardian"
                type="text"
                placeholder="Guardian name"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Contact Number</label>
              <input
                v-model="form.contact_number"
                type="text"
                placeholder="e.g. 09XXXXXXXXX"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Guardian Email</label>
              <input
                v-model="form.guardian_email"
                type="email"
                placeholder="For notifications"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">LRN <span class="text-xs text-stone-500 font-normal">(exactly 12 digits)</span></label>
              <input
                v-model="form.student_number"
                type="text"
                required
                placeholder="12-digit Learner Reference Number"
                maxlength="12"
                inputmode="numeric"
                pattern="[0-9]*"
                :class="[
                  'w-full rounded-md border px-3 py-2 text-sm focus:ring-1',
                  isLrnValid ? 'border-stone-300 focus:border-blue-700 focus:ring-blue-700' : 'border-red-400 focus:border-red-500 focus:ring-red-500'
                ]"
              />
              <p v-if="form.student_number && !isLrnValid" class="mt-1 text-xs text-red-600">
                LRN must be exactly 12 digits.
              </p>
            </div>
            <div class="rounded-lg border-2 border-dashed border-stone-300 bg-stone-50/80 p-4">
              <label class="block text-sm font-medium text-stone-700 mb-2">Photo <span class="text-xs text-stone-400 font-normal">(PNG only)</span></label>
              <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-stone-300 text-sm font-medium text-stone-700 hover:bg-stone-50 transition shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                <span class="text-sm text-stone-500">{{ photoFileName || 'No file chosen' }}</span>
              </div>
              <p v-if="photoError" class="mt-1 text-xs text-red-500">{{ photoError }}</p>
            </div>
          </div>
          <div v-if="formError" class="mt-2 text-sm text-red-600">{{ formError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button
              type="button"
              class="rounded-md border border-stone-300 px-4 py-2 text-sm"
              @click="showFormModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50 disabled:cursor-not-allowed transition"
              :class="canSaveForm ? 'bg-blue-800 hover:bg-blue-900' : 'bg-stone-400 cursor-not-allowed'"
              :disabled="!canSaveForm"
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
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold text-stone-800 mb-4">Learner details</h2>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Last name</dt><dd class="font-medium text-stone-800">{{ viewModalStudent?.last_name }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">First name</dt><dd class="font-medium text-stone-800">{{ viewModalStudent?.first_name }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Middle name</dt><dd class="text-stone-700">{{ viewModalStudent?.middle_name || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Grade</dt><dd class="text-stone-700">{{ viewModalStudent?.grade || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Section</dt><dd class="text-stone-700">{{ viewModalStudent?.section || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">LRN</dt><dd class="tabular-nums text-stone-700">{{ viewModalStudent?.student_number }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Guardian</dt><dd class="text-stone-700">{{ viewModalStudent?.guardian || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Contact</dt><dd class="text-stone-700">{{ viewModalStudent?.contact_number || '—' }}</dd></div>
        </dl>
        <div class="mt-4 flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 transition"
            @click="showViewModal = false; openQrModal(viewModalStudent)"
          >
            Show QR
          </button>
          <button
            type="button"
            class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 transition"
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
      <div class="bg-white rounded-lg shadow-xl p-6 text-center border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold text-stone-800 mb-2">QR Code</h2>
        <p class="text-sm text-stone-500 mb-4">{{ qrModalStudent?.full_name }} ({{ qrModalStudent?.student_number }})</p>
        <div class="inline-block p-4 bg-stone-50 border border-stone-200 rounded-lg">
          <canvas ref="qrCanvas" width="200" height="200" />
        </div>
        <div class="mt-4">
          <button
            type="button"
            class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 transition"
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
import { ref, computed, watch, nextTick } from 'vue';
import axios from 'axios';
import QRCode from 'qrcode';
import { LogOut, Search, Upload, Plus, User, Pencil, Users, FileText, Menu } from 'lucide-vue-next';
import { setStoredToken, getStoredToken } from '../router';
import { useLogout } from '../composables/useLogout';
import { fetchStudents, createStudent, createStudentWithFormData, updateStudent, updateStudentWithFormData, uploadStudentPhoto, bulkImportStudents } from '../services/studentService';
import TeacherLocatorSlipsPage from './TeacherLocatorSlipsPage.vue';

function titleCase(str) {
  if (!str || typeof str !== 'string') return '';
  return str.replace(/\w\S*/g, (t) => t.charAt(0).toUpperCase() + t.slice(1).toLowerCase());
}

const { logout } = useLogout();

// Logo served from Laravel public/logo
const depedLogo = '/logo/depedozamiz.png';

const students = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(10);
const searchQuery = ref('');
const searchInput = ref('');

const currentTab = ref('learners');
const isSidebarOpen = ref(false);

const pageTitle = computed(() => {
  if (currentTab.value === 'locator') return 'LOCATOR SLIPS';
  return 'LEARNERS';
});

const pageSubtitle = computed(() => {
  if (currentTab.value === 'locator') return 'File and track your travel slips';
  return 'Manage student records';
});

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
  guardian_email: '',
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

const bulkImportInput = ref(null);
const bulkImporting = ref(false);
const bulkImportError = ref('');
const bulkImportResult = ref(null);

const LRN_LENGTH = 12;
const isLrnValid = computed(() => {
  const v = String(form.value.student_number ?? '').trim();
  if (!v) return false;
  return /^\d{12}$/.test(v);
});
const canSaveForm = computed(() => {
  if (!form.value.first_name?.trim() || !form.value.last_name?.trim()) return false;
  if (!editingId.value && !isLrnValid.value) return false;
  return true;
});

let debounceTimer = null;

function triggerBulkImport() {
  bulkImportError.value = '';
  bulkImportResult.value = null;
  bulkImportInput.value?.click();
}

async function onBulkImportFile(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  bulkImportInput.value.value = '';
  bulkImporting.value = true;
  bulkImportError.value = '';
  bulkImportResult.value = null;
  try {
    const result = await bulkImportStudents(file);
    bulkImportResult.value = result;
    await load();
    setTimeout(() => { bulkImportResult.value = null; }, 5000);
  } catch (err) {
    bulkImportError.value = err.response?.data?.message || err.message || 'Import failed.';
  } finally {
    bulkImporting.value = false;
  }
}

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
    grade_section: '', guardian: '', guardian_email: '', contact_number: '', student_number: '',
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
    guardian_email: row.guardian_email ?? '',
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
  fd.append('guardian_email', form.value.guardian_email || '');
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
          guardian_email: form.value.guardian_email || '',
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
          guardian_email: form.value.guardian_email || '',
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
  const qrData = lrn;
  
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



load();
</script>
