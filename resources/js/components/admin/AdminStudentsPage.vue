<template>
  <div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold text-slate-800">Master Student List</h1>
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
import { fetchAdminStudents, deleteStudent } from '../../services/adminService';

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
