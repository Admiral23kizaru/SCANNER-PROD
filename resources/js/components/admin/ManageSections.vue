<!--
  Action: Implementing Section Management and fixing school-level data scoping.
  // Description: ManageSections.vue - Admin page for creating, viewing, and
  //   managing class sections. Supports teacher assignment and bulk student enrollment.
  // Author: Antigravity System Agent
-->
<template>
  <div class="space-y-6">
    <!-- Page header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Manage Sections</h1>
        <p class="text-sm text-slate-500 mt-1">Create sections, assign teachers, and enroll students</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 transition shadow-sm"
        @click="openCreateModal"
      >
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        Create Section
      </button>
    </div>

    <!-- Sections Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
            <tr>
              <th class="py-3 px-5 border-b border-slate-200">Section Name</th>
              <th class="py-3 px-5 border-b border-slate-200">Grade Level</th>
              <th class="py-3 px-5 border-b border-slate-200">Assigned Teacher</th>
              <th class="py-3 px-5 border-b border-slate-200 text-center">Students</th>
              <th class="py-3 px-5 border-b border-slate-200 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="sec in sections"
              :key="sec.id"
              class="border-b border-slate-100 hover:bg-slate-50 transition"
            >
              <td class="py-3 px-5 font-semibold text-slate-900">{{ sec.name }}</td>
              <td class="py-3 px-5 text-slate-600">{{ sec.grade_level }}</td>
              <td class="py-3 px-5 text-slate-600">{{ sec.teacher?.name || 'Unassigned' }}</td>
              <td class="py-3 px-5 text-center">
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 border border-indigo-200">
                  {{ sec.students_count || 0 }}
                </span>
              </td>
              <td class="py-3 px-5 text-right">
                <span class="inline-flex items-center gap-2">
                  <button @click="openAssignModal(sec)" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition" title="Assign Students">+ Assign</button>
                  <button @click="openSectionStudentsModal(sec)" class="text-xs font-medium text-slate-700 hover:text-slate-900 transition" title="Manage Students">Students</button>
                  <button @click="openEditModal(sec)" class="text-xs font-medium text-slate-600 hover:text-slate-900 transition" title="Edit">Edit</button>
                  <button @click="confirmDelete(sec)" class="text-xs font-medium text-red-500 hover:text-red-700 transition" title="Delete">Delete</button>
                </span>
              </td>
            </tr>
            <tr v-if="loading && sections.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-500">
                <div class="flex flex-col items-center gap-2">
                  <div class="w-6 h-6 border-3 border-slate-300 border-t-slate-700 rounded-full animate-spin"></div>
                  Loading sections…
                </div>
              </td>
            </tr>
            <tr v-if="!loading && sections.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-400 italic">No sections created yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══ CREATE / EDIT SECTION MODAL ═══ -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl w-full max-w-md shadow-xl" @click.stop>
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
          <h2 class="text-lg font-semibold text-slate-900">{{ editTarget ? 'Edit Section' : 'Create Section' }}</h2>
          <button @click="closeModal" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 transition">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form @submit.prevent="submitSection" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Section Name</label>
            <input v-model="form.name" type="text" required placeholder="e.g. Section A" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Grade Level</label>
            <select v-model="form.grade_level" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-indigo-200">
              <option value="" disabled>Select grade</option>
              <option v-for="g in gradeLevels" :key="g" :value="g">{{ g }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Assign Teacher</label>
            <select v-model="form.teacher_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-indigo-200">
              <option :value="null">— No teacher —</option>
              <option v-for="t in teacherList" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div v-if="formError" class="text-sm text-red-600">{{ formError }}</div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Cancel</button>
            <button type="submit" :disabled="submitting" class="px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition disabled:opacity-50">
              {{ submitting ? 'Saving…' : (editTarget ? 'Update' : 'Create') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ═══ BULK ASSIGN STUDENTS MODAL ═══ -->
    <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl w-full max-w-lg shadow-xl flex flex-col max-h-[85vh]" @click.stop>
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
          <h2 class="text-lg font-semibold text-slate-900">Assign Students to {{ assignTarget?.name }}</h2>
          <button @click="showAssignModal = false" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 transition">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <div class="flex-1 overflow-auto p-5">
          <div v-if="assignLoading" class="flex flex-col items-center py-10">
            <div class="w-7 h-7 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            <p class="mt-3 text-sm text-slate-500">Loading unassigned students…</p>
          </div>
          <div v-else-if="unassignedStudents.length === 0" class="text-center py-10 text-slate-400 italic">
            All students are already assigned to a section.
          </div>
          <div v-else class="space-y-2">
            <label
              v-for="s in unassignedStudents"
              :key="s.id"
              class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
              :class="selectedStudentIds.includes(s.id) ? 'border-indigo-300 bg-indigo-50' : 'border-slate-100 bg-slate-50 hover:bg-slate-100'"
            >
              <input type="checkbox" :value="s.id" v-model="selectedStudentIds" class="rounded text-indigo-600 focus:ring-indigo-200" />
              <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900 truncate">{{ s.last_name }}, {{ s.first_name }}</p>
                <p class="text-xs text-slate-500">{{ s.student_number }}</p>
              </div>
            </label>
          </div>
        </div>
        <div class="p-5 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs text-slate-500">{{ selectedStudentIds.length }} selected</span>
          <button
            type="button"
            :disabled="selectedStudentIds.length === 0 || assignSubmitting"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
            @click="submitAssign"
          >
            {{ assignSubmitting ? 'Assigning…' : 'Assign Selected' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══ MANAGE SECTION STUDENTS MODAL ═══ -->
    <div v-if="showSectionStudentsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl w-full max-w-lg shadow-xl flex flex-col max-h-[85vh]" @click.stop>
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
          <h2 class="text-lg font-semibold text-slate-900">Students in {{ sectionStudentsTarget?.name }}</h2>
          <button @click="showSectionStudentsModal = false" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 transition">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <div class="flex-1 overflow-auto p-5">
          <div v-if="sectionStudentsLoading" class="flex flex-col items-center py-10">
            <div class="w-7 h-7 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            <p class="mt-3 text-sm text-slate-500">Loading section students…</p>
          </div>

          <div v-else-if="sectionStudents.length === 0" class="text-center py-10 text-slate-400 italic">
            No students assigned yet.
          </div>

          <div v-else class="space-y-2">
            <label
              v-for="s in sectionStudents"
              :key="s.id"
              class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
              :class="selectedSectionStudentIds.includes(s.id) ? 'border-indigo-300 bg-indigo-50' : 'border-slate-100 bg-slate-50 hover:bg-slate-100'"
            >
              <input
                type="checkbox"
                :value="s.id"
                v-model="selectedSectionStudentIds"
                class="rounded text-indigo-600 focus:ring-indigo-200"
              />
              <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900 truncate">{{ s.last_name }}, {{ s.first_name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ s.student_number }}</p>
                <p class="text-[11px] text-slate-400 truncate">{{ s.grade && s.section ? s.grade + ' / ' + s.section : '—' }}</p>
              </div>
            </label>
          </div>
        </div>

        <div class="p-5 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
          <div class="text-xs text-slate-500">{{ selectedSectionStudentIds.length }} selected</div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition disabled:opacity-50"
              @click="openAssignModal(sectionStudentsTarget); showSectionStudentsModal = false"
            >
              + Add more
            </button>
            <button
              type="button"
              :disabled="selectedSectionStudentIds.length === 0 || unassignSectionSubmitting"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
              @click="submitUnassignSelected"
            >
              {{ unassignSectionSubmitting ? 'Unassigning…' : 'Unassign Selected' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ DELETE CONFIRM MODAL ═══ -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl w-full max-w-sm shadow-xl p-6 text-center" @click.stop>
        <p class="text-sm text-slate-700">Delete section <strong>{{ deleteTarget?.name }}</strong>?</p>
        <p class="text-xs text-slate-400 mt-1">Students will be un-assigned from this section.</p>
        <div class="flex justify-center gap-3 mt-5">
          <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Cancel</button>
          <button @click="executeDelete" :disabled="deleting" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition disabled:opacity-50">
            {{ deleting ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * // Description: ManageSections - Full admin page for section CRUD.
 * //   - Lists all sections with teacher and student count
 * //   - Create/Edit modal with grade level and teacher dropdown
 * //   - Bulk student assignment from unassigned pool
 * // Author: Antigravity System Agent
 */

import { ref, onMounted } from 'vue';
import axios from 'axios';

// ─── State ──────────────────────────────────────────────────────────────────

const sections = ref([]);
const teacherList = ref([]);
const loading = ref(false);

const showModal = ref(false);
const editTarget = ref(null);
const form = ref({ name: '', grade_level: '', teacher_id: null });
const formError = ref('');
const submitting = ref(false);

const showAssignModal = ref(false);
const assignTarget = ref(null);
const unassignedStudents = ref([]);
const selectedStudentIds = ref([]);
const assignLoading = ref(false);
const assignSubmitting = ref(false);

const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

const showSectionStudentsModal = ref(false);
const sectionStudentsTarget = ref(null);
const sectionStudents = ref([]);
const sectionStudentsLoading = ref(false);
const selectedSectionStudentIds = ref([]);
const unassignSectionSubmitting = ref(false);

// Grade levels matching the Philippine K-12 curriculum
const gradeLevels = [
  'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
  'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12',
];

// ─── Data Fetching ──────────────────────────────────────────────────────────

/**
 * // Description: loadSections - Fetches all sections with teacher info and student counts.
 */
async function loadSections() {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/sections');
    sections.value = res.data.data || [];
  } catch { sections.value = []; }
  finally { loading.value = false; }
}

/**
 * // Description: loadTeachers - Fetches all teacher-role users for the dropdown.
 */
async function loadTeachers() {
  try {
    const res = await axios.get('/api/admin/sections/teachers-list');
    teacherList.value = res.data.data || [];
  } catch { teacherList.value = []; }
}

// ─── Create / Edit Logic ────────────────────────────────────────────────────

function openCreateModal() {
  editTarget.value = null;
  form.value = { name: '', grade_level: '', teacher_id: null };
  formError.value = '';
  showModal.value = true;
}

function openEditModal(sec) {
  editTarget.value = sec;
  form.value = { name: sec.name, grade_level: sec.grade_level, teacher_id: sec.teacher_id || null };
  formError.value = '';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editTarget.value = null;
}

/**
 * // Description: submitSection - Creates or updates a section via POST/PUT then reloads.
 */
async function submitSection() {
  submitting.value = true;
  formError.value = '';
  try {
    if (editTarget.value) {
      await axios.put(`/api/admin/sections/${editTarget.value.id}`, form.value);
    } else {
      await axios.post('/api/admin/sections', form.value);
    }
    closeModal();
    await loadSections();
  } catch (err) {
    formError.value = err?.response?.data?.message || 'Failed to save section.';
  } finally { submitting.value = false; }
}

// ─── Bulk Assign Logic ──────────────────────────────────────────────────────

/**
 * // Description: openAssignModal - Loads unassigned students and opens the modal.
 */
async function openAssignModal(sec) {
  assignTarget.value = sec;
  selectedStudentIds.value = [];
  showAssignModal.value = true;
  assignLoading.value = true;
  try {
    const res = await axios.get('/api/admin/sections/unassigned-students');
    unassignedStudents.value = res.data.data || [];
  } catch { unassignedStudents.value = []; }
  finally { assignLoading.value = false; }
}

/**
 * // Description: submitAssign - Bulk-assigns selected students to the target section.
 */
async function submitAssign() {
  if (!assignTarget.value || selectedStudentIds.value.length === 0) return;
  assignSubmitting.value = true;
  try {
    await axios.post(`/api/admin/sections/${assignTarget.value.id}/assign-students`, {
      student_ids: selectedStudentIds.value,
    });
    showAssignModal.value = false;
    await loadSections();
  } catch (err) {
    console.error('Assign failed', err);
  } finally { assignSubmitting.value = false; }
}

// ─── Manage Assigned Students (Unassign) ────────────────────────────────────

async function openSectionStudentsModal(sec) {
  sectionStudentsTarget.value = sec;
  selectedSectionStudentIds.value = [];
  showSectionStudentsModal.value = true;
  sectionStudentsLoading.value = true;
  try {
    const res = await axios.get(`/api/admin/sections/${sec.id}/students`);
    sectionStudents.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to load section students', err);
    sectionStudents.value = [];
  } finally {
    sectionStudentsLoading.value = false;
  }
}

async function submitUnassignSelected() {
  if (!sectionStudentsTarget.value || selectedSectionStudentIds.value.length === 0) return;
  unassignSectionSubmitting.value = true;
  try {
    await axios.post(`/api/admin/sections/${sectionStudentsTarget.value.id}/unassign-students`, {
      student_ids: selectedSectionStudentIds.value,
    });
    showSectionStudentsModal.value = false;
    sectionStudentsTarget.value = null;
    await loadSections();
  } catch (err) {
    console.error('Unassign failed', err);
  } finally {
    unassignSectionSubmitting.value = false;
  }
}

// ─── Delete Logic ───────────────────────────────────────────────────────────

function confirmDelete(sec) {
  deleteTarget.value = sec;
  showDeleteModal.value = true;
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  deleting.value = true;
  try {
    await axios.delete(`/api/admin/sections/${deleteTarget.value.id}`);
    showDeleteModal.value = false;
    await loadSections();
  } catch (err) {
    console.error('Delete failed', err);
  } finally {
    deleting.value = false;
    deleteTarget.value = null;
  }
}

// ─── Lifecycle ──────────────────────────────────────────────────────────────

onMounted(async () => {
  await Promise.all([loadSections(), loadTeachers()]);
});
</script>
