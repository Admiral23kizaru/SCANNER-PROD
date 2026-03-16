<template>
  <div>
    <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
      <!-- Toolbar (match screenshot layout) -->
      <div class="p-4 sm:p-5 border-b border-slate-200 bg-white flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 shadow-sm transition inline-flex items-center gap-2"
            @click="openCreateModal"
          >
            <Plus class="h-4 w-4" />
            Create Teacher
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
              type="search"
              placeholder="Search teachers..."
              class="w-64 max-w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
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
              <th class="py-3 px-4 border-b border-slate-200">Name</th>
              <th class="py-3 px-4 border-b border-slate-200">Employee ID</th>
              <th class="py-3 px-4 border-b border-slate-200">School</th>
              <th class="py-3 px-4 border-b border-slate-200">Created</th>
              <th class="py-3 px-4 text-right border-b border-slate-200">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(t, idx) in teachers"
              :key="t.id"
              class="border-b border-slate-100 hover:bg-slate-50 transition"
            >
              <td class="py-3 px-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0 shadow-sm"
                  >
                    <img
                      v-if="t.profile_photo && !photoLoadError[t.id]"
                      :src="getPhotoUrl(t.profile_photo)"
                      alt=""
                      class="w-full h-full object-cover"
                      @error="handlePhotoError(t.id)"
                    />
                    <div
                      v-else
                      class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-400 to-slate-500 text-white font-semibold text-sm"
                    >
                      <img v-if="photoLoadError[t.id]" :src="'/images/default-avatar.png'" class="w-full h-full object-cover" @error="photoLoadError[t.id] = 'failed_twice'" />
                      <span v-else>{{ t.name?.charAt(0) || 'T' }}</span>
                    </div>
                  </div>
                  <div class="min-w-0">
                    <div class="font-semibold text-slate-900 truncate">{{ t.name }}</div>
                    <div class="text-xs text-slate-500 truncate">{{ t.job_title || 'Teacher' }}</div>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4 text-slate-700 whitespace-nowrap">
                {{ t.employee_id || '—' }}
              </td>
              <td class="py-3 px-4 text-slate-600 truncate max-w-[150px]">{{ t.school_name || '—' }}</td>
              <td class="py-3 px-4 text-slate-600">{{ formatDate(t.created_at) }}</td>
              <td class="py-3 px-4 text-right">
                <span class="inline-flex items-center justify-end gap-3">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                    title="Print ID"
                    @click="printTeacherId(t)"
                  >
                    <IdCard class="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                    title="Edit teacher"
                    @click="openEditModal(t)"
                  >
                    <PencilLine class="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-red-600 hover:bg-slate-100 transition"
                    title="Delete teacher"
                    @click="confirmDelete(t)"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </span>
              </td>
            </tr>
            <tr v-if="loading && teachers.length === 0">
              <td colspan="4" class="py-12 text-center text-slate-500">Loading…</td>
            </tr>
            <tr v-if="!loading && teachers.length === 0">
              <td colspan="4" class="py-12 text-center text-slate-500">No teachers yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50/60">
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
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold p-6 pb-0">Create Teacher Account</h2>
        <form @submit.prevent="submitCreate" class="p-6 overflow-y-auto flex-1">
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Name</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Employee ID</label>
              <input
                v-model="form.employee_id"
                type="text"
                required
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">School Name</label>
              <input
                v-model="form.school_name"
                type="text"
                placeholder="e.g. Ozamiz City Central"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Position</label>
              <select
                v-model="form.job_title"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm bg-white"
              >
                <option value="" disabled>Select position</option>
                <option v-for="opt in jobTitleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Password</label>
              <input
                v-model="form.password"
                type="password"
                required
                minlength="8"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Confirm Password</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-3">
              <label class="block text-sm font-medium text-stone-700 mb-2">
                Profile photo
                <span class="text-xs text-stone-500 font-normal">(optional, JPG/PNG, max 2&nbsp;MB)</span>
              </label>
              <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-stone-300 text-sm font-medium text-stone-700 cursor-pointer hover:bg-stone-50">
                  Choose file
                  <input
                    ref="createPhotoInput"
                    type="file"
                    accept="image/png,image/jpeg"
                    class="sr-only"
                    @change="onCreatePhotoChange"
                  />
                </label>
                <span class="text-xs text-stone-600 truncate">
                  {{ createPhotoFileName || 'No file chosen' }}
                </span>
              </div>
              <p v-if="createPhotoError" class="mt-1 text-xs text-red-600">{{ createPhotoError }}</p>
            </div>
          </div>
          <div v-if="formError" class="mt-2 text-sm text-red-600">{{ formError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button
              type="button"
              class="rounded-md border border-stone-300 px-4 py-2 text-sm"
              @click="showCreateModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-md bg-blue-800 px-4 py-2 text-sm font-medium text-white hover:bg-blue-900"
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
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold p-6 pb-0">Edit Teacher</h2>
        <form @submit.prevent="submitEdit" class="p-6 overflow-y-auto flex-1">
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Name</label>
              <input v-model="editForm.name" type="text" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Employee ID</label>
              <input v-model="editForm.employee_id" type="text" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>

            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">School Name</label>
              <input
                v-model="editForm.school_name"
                type="text"
                placeholder="e.g. Ozamiz City Central"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Position</label>
              <select
                v-model="editForm.job_title"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm bg-white"
              >
                <option value="" disabled>Select position</option>
                <option v-for="opt in jobTitleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-3">
              <p class="text-xs text-stone-500 mb-2">Optional: set a new password for this teacher.</p>
              <div class="space-y-2">
                <div>
                  <label class="block text-sm font-medium text-stone-700 mb-1">New Password</label>
                  <input v-model="editForm.password" type="password" minlength="8" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-stone-700 mb-1">Confirm New Password</label>
                  <input v-model="editForm.password_confirmation" type="password" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
                </div>
              </div>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-3">
              <label class="block text-sm font-medium text-stone-700 mb-2">
                Profile photo
                <span class="text-xs text-stone-500 font-normal">(optional, JPG/PNG, max 2&nbsp;MB)</span>
              </label>
              <div class="flex items-center gap-4 mb-3" v-if="editForm.profile_photo">
                <div class="w-16 h-16 rounded-lg overflow-hidden border border-stone-200 bg-stone-100 shadow-sm">
                  <img :src="getPhotoUrl(editForm.profile_photo)" class="w-full h-full object-cover" />
                </div>
                <div class="text-[10px] text-stone-400">Current photo</div>
              </div>
              <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-stone-300 text-sm font-medium text-stone-700 cursor-pointer hover:bg-stone-50">
                  Choose file
                  <input
                    ref="editPhotoInput"
                    type="file"
                    accept="image/png,image/jpeg"
                    class="sr-only"
                    @change="onEditPhotoChange"
                  />
                </label>
                <span class="text-xs text-stone-600 truncate">
                  {{ editPhotoFileName || 'No file chosen' }}
                </span>
              </div>
              <p v-if="editPhotoError" class="mt-1 text-xs text-red-600">{{ editPhotoError }}</p>
            </div>
          </div>
          <div v-if="editError" class="mt-2 text-sm text-red-600">{{ editError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="rounded-md border border-stone-300 px-4 py-2 text-sm" @click="showEditModal = false">Cancel</button>
            <button type="submit" class="rounded-md bg-blue-800 px-4 py-2 text-sm font-medium text-white hover:bg-blue-900">Save</button>
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
        <h2 class="text-lg font-semibold text-stone-800 mb-2">Delete Teacher</h2>
        <p class="text-sm text-stone-600 mb-4">
          Are you sure you want to delete <strong>{{ deleteTarget?.name }}</strong> ({{ deleteTarget?.employee_id }})?
        </p>
        <div v-if="deleteError" class="mb-3 text-sm text-red-600">{{ deleteError }}</div>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-md border border-stone-300 px-4 py-2 text-sm" @click="showDeleteModal = false">Cancel</button>
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
import { PencilLine, Trash2, IdCard, Plus, Download, Search, Filter } from 'lucide-vue-next';
import { fetchTeachers, createTeacher, updateTeacher, deleteTeacher, uploadTeacherPhoto, getAdminTeacherIdUrl, exportAdminTeachers } from '../../services/adminService';

const teachers = ref([]);
const loading = ref(false);
const exporting = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);
const deleteError = ref('');

const form = ref({
  name: '',
  employee_id: '',
  school_name: '',
  job_title: '',
  password: '',
  password_confirmation: '',
});
const formError = ref('');

const editTargetId = ref(null);
const editForm = ref({ name: '', employee_id: '', school_name: '', job_title: '', password: '', password_confirmation: '' });
const editError = ref('');

const createPhotoFile = ref(null);
const createPhotoFileName = ref('');
const createPhotoError = ref('');
const createPhotoInput = ref(null);

const editPhotoFile = ref(null);
const editPhotoFileName = ref('');
const editPhotoError = ref('');
const editPhotoInput = ref(null);

const photoLoadError = ref({});

function getPhotoUrl(path) {
  if (!path) return '/images/default-avatar.png';
  // Strip 'public/' or 'storage/' or leading slashes
  const cleanPath = path.replace(/^(public\/|storage\/|\/storage\/|\/public\/)/, '').replace(/^\//, '');
  return '/storage/' + cleanPath;
}

function handlePhotoError(id) {
  photoLoadError.value[id] = true;
}

const jobTitleOptions = [
  { label: 'Accountant III',                            value: 'ACTIII'  },
  { label: 'Administrative Aide VI',                    value: 'AIDVI'   },
  { label: 'Administrative Assistant II',               value: 'ADASII'  },
  { label: 'Administrative Assistant III',              value: 'ADASIII' },
  { label: 'Administrative Officer II',                 value: 'AOII'    },
  { label: 'Administrative Officer IV',                 value: 'AOIV'    },
  { label: 'Administrative Officer V',                  value: 'AOV'     },
  { label: 'Administrative Supervisor II',              value: 'ASPII'   },
  { label: 'Assistant Schools Division Superintendent', value: 'ASDS'    },
  { label: 'Attorney III',                              value: 'ATTYIII' },
  { label: 'Chief Education Supervisor',                value: 'CES'     },
  { label: 'Dentist II',                                value: 'DENTII'  },
  { label: 'Education Program Supervisor',              value: 'EPS'     },
  { label: 'Education Program Supervisor II',           value: 'EPSII'   },
  { label: 'Engineer III',                              value: 'ENGIII'  },
  { label: 'Guidance Coordinator III',                  value: 'GCOIII'  },
  { label: 'Guidance Counselor I',                      value: 'GCI'     },
  { label: 'Guidance Counselor III',                    value: 'GCIII'   },
  { label: 'Health Teacher I',                          value: 'HTI'     },
  { label: 'Health Teacher II',                         value: 'HTII'    },
  { label: 'Health Teacher III',                        value: 'HTIII'   },
  { label: 'Health Teacher IV',                         value: 'HTIV'    },
  { label: 'Health Teacher V',                          value: 'HTV'     },
  { label: 'Information Technology Officer I',          value: 'ITOI'    },
  { label: 'Librarian I',                               value: 'LIBI'    },
  { label: 'Librarian II',                              value: 'LIBII'   },
  { label: 'Librarian III',                             value: 'LIBIII'  },
  { label: 'Master Teacher I',                          value: 'MTI'     },
  { label: 'Master Teacher II',                         value: 'MTII'    },
  { label: 'Medical Officer III',                       value: 'MDOIII'  },
  { label: 'Nurse II',                                  value: 'NRSII'   },
  { label: 'Senior Education Program Supervisor',       value: 'SEPS'    },
];

function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString();
}

function openCreateModal() {
  form.value = { name: '', employee_id: '', school_name: '', job_title: '', password: '', password_confirmation: '' };
  formError.value = '';
  createPhotoFile.value = null;
  createPhotoFileName.value = '';
  createPhotoError.value = '';
  if (createPhotoInput.value) {
    createPhotoInput.value.value = '';
  }
  showCreateModal.value = true;
}

function openEditModal(t) {
  editTargetId.value = t.id;
  editForm.value = {
    name: t.name || '',
    employee_id: t.employee_id || '',
    school_name: t.school_name || '',
    job_title: t.job_title || '',
    profile_photo: t.profile_photo || null,
    password: '',
    password_confirmation: '',
  };
  editError.value = '';
  editPhotoFile.value = null;
  editPhotoFileName.value = '';
  editPhotoError.value = '';
  if (editPhotoInput.value) {
    editPhotoInput.value.value = '';
  }
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
    photoLoadError.value = {};
  } catch {
    teachers.value = [];
  } finally {
    loading.value = false;
  }
}

async function handleExport() {
  if (exporting.value) return;
  exporting.value = true;
  try {
    const blob = await exportAdminTeachers();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = 'teachers_export.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    alert('Failed to export teachers.');
  } finally {
    exporting.value = false;
  }
}

async function submitCreate() {
  formError.value = '';
  if (form.value.password !== form.value.password_confirmation) {
    formError.value = 'Passwords do not match.';
    return;
  }
  try {
    const payload = {
      name: form.value.name,
      employee_id: form.value.employee_id,
      school_name: form.value.school_name || null,
      job_title: form.value.job_title || null,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    };

    const res = await createTeacher(payload);
    const createdId = res?.teacher?.id;

    if (createdId && createPhotoFile.value) {
      try {
        await uploadTeacherPhoto(createdId, createPhotoFile.value);
      } catch (err) {
        createPhotoError.value = err.response?.data?.message || 'Photo upload failed.';
      }
    }

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
    employee_id: editForm.value.employee_id,
    school_name: editForm.value.school_name || null,
    job_title: editForm.value.job_title || null,
  };
  if (editForm.value.password) {
    payload.password = editForm.value.password;
    payload.password_confirmation = editForm.value.password_confirmation;
  }

  try {
    await updateTeacher(editTargetId.value, payload);

    if (editTargetId.value && editPhotoFile.value) {
      try {
        await uploadTeacherPhoto(editTargetId.value, editPhotoFile.value);
      } catch (err) {
        editPhotoError.value = err.response?.data?.message || 'Photo upload failed.';
      }
    }

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

function onCreatePhotoChange(e) {
  const file = e.target.files?.[0];
  createPhotoError.value = '';
  if (file) {
    if (!['image/png', 'image/jpeg'].includes(file.type)) {
      createPhotoError.value = 'Only JPG or PNG images are accepted.';
      createPhotoFile.value = null;
      createPhotoFileName.value = '';
      if (createPhotoInput.value) createPhotoInput.value.value = '';
      return;
    }
    createPhotoFile.value = file;
    createPhotoFileName.value = file.name;
  } else {
    createPhotoFile.value = null;
    createPhotoFileName.value = '';
  }
}

function onEditPhotoChange(e) {
  const file = e.target.files?.[0];
  editPhotoError.value = '';
  if (file) {
    if (!['image/png', 'image/jpeg'].includes(file.type)) {
      editPhotoError.value = 'Only JPG or PNG images are accepted.';
      editPhotoFile.value = null;
      editPhotoFileName.value = '';
      if (editPhotoInput.value) editPhotoInput.value.value = '';
      return;
    }
    editPhotoFile.value = file;
    editPhotoFileName.value = file.name;
  } else {
    editPhotoFile.value = null;
    editPhotoFileName.value = '';
  }
}

async function printTeacherId(t) {
  try {
    const res = await getAdminTeacherIdUrl(t.id);
    const url = res?.url;
    if (!url) {
      throw new Error('No URL returned.');
    }
    window.open(url, '_blank', 'noopener');
  } catch (err) {
    const msg = err?.response?.data?.message || err?.message || 'Failed to generate ID.';
    alert(msg);
  }
}

onMounted(async () => {
  await load();
  const flag = sessionStorage.getItem('admin_open_create_teacher');
  if (flag) {
    sessionStorage.removeItem('admin_open_create_teacher');
    openCreateModal();
  }
});
</script>
