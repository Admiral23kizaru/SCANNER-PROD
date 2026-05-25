<template>
  <TeacherLayout
    :user="user"
    :pageTitle="pageTitle"
    :pageSubtitle="pageSubtitle"
    :depedLogo="depedLogo"
    v-model:currentTab="currentTab"
    @open-profile-modal="showProfileModal = true"
    @logout="logout"
  >
        <div v-if="currentTab !== 'subjectHandled'" class="w-full px-4 pt-4 sm:px-6">
          <div class="mx-auto flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm lg:max-w-[1400px] sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Workspace context</p>
              <p class="text-sm font-semibold text-slate-900">{{ selectedContextLabel }}</p>
            </div>
            <label class="flex flex-col gap-1 text-xs font-medium text-slate-500 sm:min-w-[320px]">
              View as
              <select
                v-model="selectedContextKey"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100"
              >
                <option value="own">Advisory class</option>
                <option
                  v-for="context in handledContexts"
                  :key="contextKey(context)"
                  :value="contextKey(context)"
                >
                  {{ contextLabel(context) }}
                </option>
              </select>
            </label>
          </div>
        </div>
        <!-- ═══ Attendance Monitor Tab ═══ -->
        <div v-show="currentTab === 'monitor'" class="w-full">
          <AttendanceMonitor ref="attendanceMonitorRef" :requestParams="activeRequestParams" />
        </div>

        <div v-show="currentTab === 'learners'" class="w-full">
           <!-- Page Content Wrapper -->
           <div class="w-full mx-auto p-4 sm:p-6 lg:max-w-[1400px]">
      <!-- White card with subtle shadow -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-white">
          <div v-if="bulkImportResult" class="mb-3 p-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-800">
            Imported {{ bulkImportResult.imported }} learner(s). {{ bulkImportResult.skipped ? bulkImportResult.skipped + ' skipped (duplicate or invalid).' : '' }}
          </div>
          <div v-if="bulkImportError" class="mb-3 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
            {{ bulkImportError }}
          </div>
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Learner Master List</h2>
              <p class="mt-1 text-sm text-slate-500">Search, import, and maintain learner records for your assigned class.</p>
              <p class="mt-2 inline-flex items-center rounded-md border border-sky-100 bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-800">
                {{ selectedContextLabel }}
              </p>
            </div>
          </div>
        </div>
        <!-- Toolbar (match screenshot layout) -->
        <div class="p-4 border-b border-slate-200 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-slate-50/60">
          <div class="flex items-center gap-2 w-full md:w-auto">
            <label class="relative flex-1 md:flex-none md:w-[420px] max-w-full">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-400 pointer-events-none" />
              <input
                v-model="searchQuery"
                type="search"
                placeholder="Search learners by name or LRN..."
                class="w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-100 focus:border-sky-500"
                @input="debouncedFetch"
              />
            </label>

            <AppButton variant="secondary">
              <template #icon><Filter class="h-4 w-4 text-slate-500" /></template>
              <span class="hidden sm:inline">Filter</span>
            </AppButton>
          </div>

          <div class="flex flex-wrap items-center gap-2 w-full md:w-auto md:justify-end">
            <input
              ref="bulkImportInput"
              type="file"
              accept=".csv,.xlsx,.xls"
              class="sr-only"
              @change="onBulkImportFile"
            />
            <span v-if="bulkImporting" class="text-sm text-slate-500 mr-2">Importing...</span>
            <AppButton v-if="canManageLearners" variant="secondary" @click="triggerBulkImport">
              <template #icon><Upload class="h-4 w-4" /></template>
              Bulk Import
            </AppButton>
            <AppButton v-if="canManageLearners" @click="openAddModal">
              <template #icon><Plus class="h-4 w-4" /></template>
              Add Learner
            </AppButton>
          </div>

          <!-- Keep per-page control (UI hidden to match screenshot, logic unchanged) -->
          <div class="hidden">
            <select v-model.number="perPage" @change="currentPage = 1; load()">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wide">
              <tr>
                <th class="py-3 px-4 border-b border-slate-200">Last Name</th>
                <th class="py-3 px-4 border-b border-slate-200">First Name</th>
                <th class="py-3 px-4 border-b border-slate-200">Middle Name</th>
                <th class="py-3 px-4 border-b border-slate-200">Gender</th>
                <th class="py-3 px-4 border-b border-slate-200">Grade</th>
                <th class="py-3 px-4 border-b border-slate-200">Section</th>
                <th class="py-3 px-4 border-b border-slate-200">LRN</th>
                <th class="py-3 px-4 border-b border-slate-200 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in students"
                :key="row.id"
                class="border-b border-slate-100 hover:bg-sky-50/50 transition"
              >
                <td class="py-4 px-4 font-semibold text-slate-900 capitalize">{{ titleCase(row.last_name) }}</td>
                <td class="py-4 px-4 text-slate-700 capitalize">{{ titleCase(row.first_name) }}</td>
                <td class="py-4 px-4 text-slate-600 capitalize">{{ row.middle_name ? titleCase(row.middle_name) : '-' }}</td>
                <td class="py-4 px-4 text-slate-600 capitalize text-sm">{{ row.gender || '-' }}</td>
                <td class="py-4 px-4 text-slate-700">{{ row.grade || row.grade_section || '-' }}</td>
                <td class="py-4 px-4 text-slate-700">{{ row.section || '-' }}</td>
                <td class="py-4 px-4 font-mono text-slate-600 tabular-nums">{{ row.student_number }}</td>
                <td class="py-4 px-4 text-right">
                  <span class="inline-flex items-center justify-end gap-2">
                    <AppIconButton label="Profile" variant="primary" @click="openViewModal(row)">
                      <User class="h-5 w-5" />
                    </AppIconButton>
                    <AppIconButton v-if="canManageLearners" label="Edit Profile" @click="openEditModal(row)">
                      <Pencil class="h-5 w-5" />
                    </AppIconButton>
                  </span>
                </td>
              </tr>
              <tr v-if="loading && students.length === 0">
                <td colspan="8" class="py-12 text-center text-slate-500">Loading...</td>
              </tr>
              <tr v-if="!loading && students.length === 0">
                <td colspan="8">
                  <AppEmptyState title="No learners found." message="Try searching by learner name or LRN." />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50/60">
          <span class="text-sm text-slate-600">
            Showing {{ total ? (currentPage - 1) * perPage + 1 : 0 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} entries
          </span>
          <div class="flex items-center gap-2">
            <AppIconButton
              label="Previous"
              :disabled="currentPage <= 1"
              @click="goToPage(currentPage - 1)"
            >
              <ChevronLeft class="h-4 w-4" />
            </AppIconButton>
            <button
              type="button"
              class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-sky-700 text-white"
              disabled
              title="Current page"
            >
              {{ currentPage }}
            </button>
            <AppIconButton
              label="Next"
              :disabled="currentPage >= lastPage"
              @click="goToPage(currentPage + 1)"
            >
              <ChevronRight class="h-4 w-4" />
            </AppIconButton>
          </div>
          </div>
        </div>
      </div>
    </div>
    <!-- ═══ Semestral Assessment Tab ═══ -->
    <div v-show="currentTab === 'semestralAssessment'" class="w-full">
      <LearningAssessment :key="selectedContextKey" :requestParams="activeRequestParams" />
    </div>

    <div v-show="currentTab === 'subjectHandled'" class="w-full">
      <div class="w-full mx-auto p-4 sm:p-6 lg:max-w-5xl">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Subject Handled</h2>
            <p class="mt-1 text-sm text-slate-500">Add same-grade sections you handle as a subject teacher.</p>
          </div>

          <div class="grid gap-4 p-5 lg:grid-cols-[1fr_1.2fr]">
            <form class="rounded-lg border border-slate-200 bg-slate-50/60 p-4" @submit.prevent="submitHandledSubject">
              <div class="space-y-3">
                <div>
                  <label class="mb-1 block text-xs font-medium text-slate-600">Subject</label>
                  <select v-model.number="handledForm.subject_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                    <option :value="null" disabled>Select subject</option>
                    <option v-for="subject in handledOptions.subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-slate-600">Section</label>
                  <select v-model.number="handledForm.section_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                    <option :value="null" disabled>Select same-grade section</option>
                    <option v-for="section in handledOptions.sections" :key="section.id" :value="section.id">
                      {{ section.grade_level }} / {{ section.name }}
                    </option>
                  </select>
                </div>
                <p v-if="handledError" class="text-sm text-red-600">{{ handledError }}</p>
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-800 disabled:opacity-50" :disabled="savingHandled">
                  {{ savingHandled ? 'Saving...' : 'Add handled subject' }}
                </button>
              </div>
            </form>

            <div class="overflow-hidden rounded-lg border border-slate-200">
              <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 text-xs uppercase text-slate-600">
                  <tr>
                    <th class="border-b border-slate-200 px-3 py-2">Subject</th>
                    <th class="border-b border-slate-200 px-3 py-2">Grade / Section</th>
                    <th class="border-b border-slate-200 px-3 py-2 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="context in handledContexts" :key="context.id" class="border-b border-slate-100">
                    <td class="px-3 py-3 font-medium text-slate-900">{{ context.subject_name }}</td>
                    <td class="px-3 py-3 text-slate-600">{{ context.grade_level }} / {{ context.section_name }}</td>
                    <td class="px-3 py-3 text-right">
                      <button type="button" class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100" @click="removeHandledSubject(context)">
                        Remove
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!loadingHandled && handledContexts.length === 0">
                    <td colspan="3" class="px-3 py-10 text-center text-slate-400">No handled subjects yet.</td>
                  </tr>
                  <tr v-if="loadingHandled">
                    <td colspan="3" class="px-3 py-10 text-center text-slate-500">Loading...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- 
      Header Comment: Action: Implementing static backdrop to prevent accidental data loss during student editing.
    -->
    <div
      v-if="showFormModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold text-stone-800 p-6 pb-0">{{ editingId ? 'Edit Learner' : 'Add Learner' }}</h2>
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
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Gender</label>
              <select v-model="form.gender" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700 bg-white">
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
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
              <label class="block text-sm font-medium text-stone-700 mb-1">Notification Preference</label>
              <select
                v-model.number="form.notification_preference"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-blue-700 focus:ring-1 focus:ring-blue-700 bg-white"
              >
                <option :value="0">No SMS - Email only (free)</option>
                <option :value="1">Regular SMS - 1 SMS per day + Email</option>
                <option :value="2">VIP SMS - Every scan SMS + Email</option>
              </select>
              <p class="mt-1 text-xs text-stone-400">Email is always sent on every scan.</p>
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
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Middle name</dt><dd class="text-stone-700">{{ viewModalStudent?.middle_name || '-' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Grade</dt><dd class="text-stone-700">{{ viewModalStudent?.grade || '-' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Section</dt><dd class="text-stone-700">{{ viewModalStudent?.section || '-' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">LRN</dt><dd class="tabular-nums text-stone-700">{{ viewModalStudent?.student_number }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Guardian</dt><dd class="text-stone-700">{{ viewModalStudent?.guardian || '-' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-stone-500">Contact</dt><dd class="text-stone-700">{{ viewModalStudent?.contact_number || '-' }}</dd></div>
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
  </TeacherLayout>
  <TeacherProfileModal v-model="showProfileModal" :user="user" @profile-updated="onProfileUpdated" />
</template>

<script setup>
import { assetPath } from '../../composables/useAsset';
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { fetchUser, logoutUser } from '../../services/authService';
import QRCode from 'qrcode';
import { Search, Upload, Plus, User, Pencil, ChevronLeft, ChevronRight, Filter } from 'lucide-vue-next';
import router, { setStoredToken } from '../../router';
import {
  fetchStudents,
  createStudent,
  createStudentWithFormData,
  updateStudent,
  updateStudentWithFormData,
  uploadStudentPhoto,
  bulkImportStudents,
  fetchSubjectHandled,
  fetchSubjectHandledOptions,
  createSubjectHandled,
  deleteSubjectHandled,
} from '../../services/studentService';
import TeacherProfileModal from './TeacherProfileModal.vue';
import AttendanceMonitor from '../AttendanceMonitor.vue';
import TeacherLayout from '../layouts/TeacherLayout.vue';
import LearningAssessment from './LearningAssessment.vue';
import AppButton from '../ui/AppButton.vue';
import AppEmptyState from '../ui/AppEmptyState.vue';
import AppIconButton from '../ui/AppIconButton.vue';

const attendanceMonitorRef = ref(null);

function titleCase(str) {
  if (!str || typeof str !== 'string') return '';
  return str.replace(/\w\S*/g, (t) => t.charAt(0).toUpperCase() + t.slice(1).toLowerCase());
}

async function logout() {
  try {
    await logoutUser();
  } catch (_) {}
  setStoredToken(null);
  // Router imported via vue-router, or if not, window.location.href works
  window.location.href = '/login';
}

// Logo served from Laravel public/logo
const depedLogo = assetPath('/logo/depedozamiz.png');

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
const user = ref(null);
const userPhotoError = ref(false);
const isProfileOpen = ref(false);
const showProfileModal = ref(false);
const handledContexts = ref([]);
const handledOptions = ref({ subjects: [], sections: [] });
const handledForm = ref({ subject_id: null, section_id: null });
const handledError = ref('');
const loadingHandled = ref(false);
const savingHandled = ref(false);
const selectedContextKey = ref('own');

function onProfileUpdated(updatedProfile) {
  if (user.value && updatedProfile) {
    user.value = { ...user.value, ...updatedProfile };
  }
}

const pageTitle = computed(() => {
  if (currentTab.value === 'monitor') return 'ATTENDANCE MONITOR';
  if (currentTab.value === 'semestralAssessment') return 'SEMESTRAL ASSESSMENT';
  if (currentTab.value === 'subjectHandled') return 'SUBJECT HANDLED';
  return 'LEARNERS';
});

const pageSubtitle = computed(() => {
  if (currentTab.value === 'monitor') return 'Real-time attendance tracking';
  if (currentTab.value === 'semestralAssessment') return 'Roster export and Excel import / analysis';
  if (currentTab.value === 'subjectHandled') return 'Same-grade subject teacher assignments';
  if (isSubjectTeacher.value) return assignedClassLabel.value === 'Not assigned'
    ? 'View learner records'
    : `View ${assignedClassLabel.value} learner records`;
  return assignedClassLabel.value === 'Not assigned'
    ? 'Manage learner records'
    : `Manage ${assignedClassLabel.value} learner records`;
});

const userRole = computed(() => user.value?.role?.name || user.value?.role_name || '');
const isSubjectTeacher = computed(() => userRole.value === 'Subject Teacher');
const canManageLearners = computed(() => ['Teacher', 'Adviser'].includes(userRole.value) && !selectedHandledContext.value);

const assignedClassLabel = computed(() => {
  const grade = String(user.value?.grade_level || '').trim();
  const section = String(user.value?.section || '').trim();

  if (grade && section) return `${grade} / ${section}`;
  if (grade) return grade;
  if (section) return section;
  return 'Not assigned';
});

function contextKey(context) {
  return `handled:${context.id}`;
}

function contextLabel(context) {
  return `${context.subject_name || 'Subject'} - ${context.grade_level || ''} / ${context.section_name || ''}`;
}

const selectedHandledContext = computed(() => {
  if (!selectedContextKey.value.startsWith('handled:')) return null;
  const id = Number(selectedContextKey.value.split(':')[1]);
  return handledContexts.value.find((context) => Number(context.id) === id) || null;
});

const selectedContextLabel = computed(() => {
  const handled = selectedHandledContext.value;
  if (handled) return `Subject handled: ${contextLabel(handled)}`;
  return `Advisory class: ${assignedClassLabel.value}`;
});

const activeRequestParams = computed(() => {
  const handled = selectedHandledContext.value;
  if (!handled) return {};
  return {
    handled_section_id: handled.section_id,
    subject_id: handled.subject_id,
  };
});

const showFormModal = ref(false);
const showViewModal = ref(false);
const showQrModal = ref(false);
const viewModalStudent = ref(null);
const editingId = ref(null);
const form = ref({
  first_name: '',
  last_name: '',
  gender: '',
  middle_name: '',
  guardian: '',
  guardian_email: '',
  contact_number: '',
  student_number: '',
  notification_preference: 0,
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

watch(selectedContextKey, async () => {
  currentPage.value = 1;
  await load();
  attendanceMonitorRef.value?.loadData?.();
});

function triggerBulkImport() {
  if (!canManageLearners.value) return;
  bulkImportError.value = '';
  bulkImportResult.value = null;
  bulkImportInput.value?.click();
}

async function onBulkImportFile(e) {
  if (!canManageLearners.value) return;
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
      ...activeRequestParams.value,
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

async function loadHandledSubjects() {
  loadingHandled.value = true;
  handledError.value = '';
  try {
    const [contexts, options] = await Promise.all([
      fetchSubjectHandled(),
      fetchSubjectHandledOptions(),
    ]);
    handledContexts.value = contexts;
    handledOptions.value = {
      subjects: options?.subjects || [],
      sections: options?.sections || [],
    };
    if (selectedHandledContext.value === null && selectedContextKey.value !== 'own') {
      selectedContextKey.value = 'own';
    }
  } catch (err) {
    handledError.value = err?.response?.data?.message || 'Could not load handled subjects.';
  } finally {
    loadingHandled.value = false;
  }
}

async function submitHandledSubject() {
  handledError.value = '';
  savingHandled.value = true;
  try {
    await createSubjectHandled(handledForm.value);
    handledForm.value = { subject_id: null, section_id: null };
    await loadHandledSubjects();
  } catch (err) {
    handledError.value = err?.response?.data?.message || 'Could not save handled subject.';
  } finally {
    savingHandled.value = false;
  }
}

async function removeHandledSubject(context) {
  if (!window.confirm(`Remove ${contextLabel(context)}?`)) return;
  try {
    await deleteSubjectHandled(context.id);
    if (selectedContextKey.value === contextKey(context)) {
      selectedContextKey.value = 'own';
    }
    await loadHandledSubjects();
  } catch (err) {
    handledError.value = err?.response?.data?.message || 'Could not remove handled subject.';
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
  if (!canManageLearners.value) return;
  editingId.value = null;
  form.value = {
    first_name: '', last_name: '', middle_name: '', gender: '',
    guardian: '', guardian_email: '', contact_number: '', student_number: '', notification_preference: 0,
  };
  formError.value = '';
  photoFile.value = null;
  photoFileName.value = '';
  photoError.value = '';
  if (photoInputRef.value) photoInputRef.value.value = '';
  showFormModal.value = true;
}

/**
 * Target Role: Attendance Guard / Parent.
 * Source: Student Preference Data
 * Destination: Semaphore API / PHPMailer
 * Function: Routing the alert based on the parent's chosen method (SMS vs Email).
 */
function openEditModal(row) {
  if (!canManageLearners.value) return;
  editingId.value = row.id;
  form.value = {
    first_name: row.first_name ?? '',
    last_name: row.last_name ?? '',
    gender: row.gender ?? '',
    middle_name: row.middle_name ?? '',
    guardian: row.guardian ?? '',
    guardian_email: row.guardian_email ?? '',
    contact_number: row.contact_number ?? '',
    student_number: row.student_number ?? '',
    notification_preference: row.notification_preference ?? 0,
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
  fd.append('gender', form.value.gender || '');
  fd.append('middle_name', form.value.middle_name || '');
  fd.append('student_number', form.value.student_number);
  fd.append('guardian', form.value.guardian || '');
  fd.append('guardian_email', form.value.guardian_email || '');
  fd.append('contact_number', form.value.contact_number || '');
  fd.append('notification_preference', form.value.notification_preference ?? 0);
  if (photoFile.value) fd.append('photo', photoFile.value);
  return fd;
}

async function submitForm() {
  if (!canManageLearners.value) {
    formError.value = 'Subject Teachers can view learners but cannot add or edit learner records.';
    return;
  }
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
          gender: form.value.gender || '',
          middle_name: form.value.middle_name || '',
          student_number: form.value.student_number,
          guardian: form.value.guardian || '',
          guardian_email: form.value.guardian_email || '',
          contact_number: form.value.contact_number || '',
          notification_preference: form.value.notification_preference ?? 0,
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
          gender: form.value.gender || '',
          middle_name: form.value.middle_name || '',
          student_number: form.value.student_number,
          guardian: form.value.guardian || '',
          guardian_email: form.value.guardian_email || '',
          contact_number: form.value.contact_number || '',
          notification_preference: form.value.notification_preference ?? 0,
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



onMounted(async () => {
  try {
    const data = await fetchUser();
    user.value = data;
  } catch (_) {}
  await loadHandledSubjects();
  await load();
});</script>
