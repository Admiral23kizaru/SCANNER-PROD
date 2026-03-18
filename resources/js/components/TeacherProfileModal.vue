<template>
  <transition
    enter-active-class="transition duration-150 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-120 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <div class="absolute inset-0 bg-black/50" @click="onBackdropClick" />

      <div class="relative w-full max-w-lg rounded-xl overflow-hidden bg-white shadow-2xl">
        <!-- Header -->
        <div class="bg-[#0f1f3d] px-5 py-4 text-white relative">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-base font-semibold">Teacher Profile</div>
              <div class="text-xs text-white/80">Manage your account information and password.</div>
            </div>

            <div class="flex items-center gap-3">
              <button
                type="button"
                class="absolute top-3 right-3 w-8 h-8 inline-flex items-center justify-center rounded-md text-white/90 hover:bg-white/10 transition"
                aria-label="Close"
                :disabled="isBusy"
                :class="isBusy ? 'opacity-60 cursor-not-allowed' : ''"
                @click="close"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- Body -->
        <div class="px-5 py-5">
          <div class="space-y-5">
            <div class="flex flex-col sm:flex-row gap-5 items-start">
              <div class="flex flex-col items-center gap-2 shrink-0">
                <div
                  class="w-24 h-24 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center border-4 border-[#e8a020]"
                >
                  <img
                    v-if="displayPhotoUrl && !avatarError"
                    :src="displayPhotoUrl"
                    alt=""
                    class="w-full h-full object-cover"
                    @error="avatarError = true"
                  />
                  <span v-else class="text-2xl font-semibold text-[#0f1f3d]">{{ initials }}</span>
                </div>

                <button
                  type="button"
                  class="mt-1 inline-flex items-center justify-center px-3 py-2 rounded-md text-xs font-medium bg-white border border-slate-200 hover:bg-slate-50 text-[#0f1f3d] transition"
                  :disabled="isBusy"
                  :class="isBusy ? 'opacity-60 cursor-not-allowed' : ''"
                  @click="triggerFile"
                >
                  Change Photo
                </button>
                <div class="text-[11px] text-slate-500">JPG/PNG/WebP, max 2 MB.</div>
                <input
                  ref="fileInput"
                  type="file"
                  class="hidden"
                  accept="image/jpeg,image/png,image/webp"
                  :disabled="isBusy"
                  @change="onFileChange"
                />
              </div>

              <div class="flex-1 space-y-5">
                <form class="space-y-4" @submit.prevent="saveAll">
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Full Name</label>
                    <input
                      v-model="teacherForm.name"
                      type="text"
                      class="w-full rounded-md border border-[#d1d5db] px-3 py-2 text-sm focus:outline-none focus:ring-0 focus:border-[#0f1f3d]"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Email</label>
                    <input
                      v-model="teacherForm.email"
                      type="email"
                      class="w-full rounded-md border border-[#d1d5db] px-3 py-2 text-sm focus:outline-none focus:ring-0 focus:border-[#0f1f3d]"
                      required
                    />
                  </div>
                </div>

                <div class="border-t border-slate-200" />

                  <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Current Password</label>
                    <input
                      v-model="teacherPwd.current_password"
                      type="password"
                      class="w-full rounded-md border border-[#d1d5db] px-3 py-2 text-sm focus:outline-none focus:ring-0 focus:border-[#0f1f3d]"
                    />
                  </div>

                  <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">New Password</label>
                    <input
                      v-model="teacherPwd.password"
                      type="password"
                      class="w-full rounded-md border border-[#d1d5db] px-3 py-2 text-sm focus:outline-none focus:ring-0 focus:border-[#0f1f3d]"
                    />
                    <div class="mt-2 space-y-1">
                      <div class="h-1.5 w-full rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full transition-all" :class="strengthBarClass" :style="{ width: strengthWidth }" />
                      </div>
                      <div class="text-[11px] text-slate-600">
                        Password strength: <span class="font-medium">{{ strengthLabel }}</span>
                      </div>
                    </div>
                  </div>

                  <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Confirm New Password</label>
                    <input
                      v-model="teacherPwd.password_confirmation"
                      type="password"
                      class="w-full rounded-md border border-[#d1d5db] px-3 py-2 text-sm focus:outline-none focus:ring-0 focus:border-[#0f1f3d]"
                    />
                  </div>

                  <div v-if="errorText" class="text-xs text-red-600">{{ errorText }}</div>

                  <div class="flex justify-end pt-3">
                    <button
                      type="submit"
                      class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium bg-[#0f1f3d] text-white disabled:opacity-60 disabled:cursor-not-allowed"
                      :disabled="isBusy"
                    >
                      <span
                        v-if="isBusy"
                        class="inline-block h-4 w-4 rounded-full border-2 border-white/50 border-t-white animate-spin"
                      />
                      Save Changes
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Toast -->
      <transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-120 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
      >
        <div
          v-if="toast.visible"
          class="fixed bottom-6 right-6 z-[60] max-w-xs rounded-lg shadow-lg px-4 py-3 flex items-start gap-3 text-sm bg-[#0f1f3d] text-white"
        >
          <span class="flex-1">{{ toast.message }}</span>
          <button class="ml-2 text-xs opacity-80 hover:opacity-100" @click="toast.visible = false">×</button>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { useTeacherProfile } from '../composables/useTeacherProfile';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'profile-updated']);

const { teacherProfile, teacherLoading, teacherError, fetchTeacherData, updateTeacherData, uploadTeacherPhotoData, changeTeacherPasswordData } = useTeacherProfile();

const avatarError = ref(false);
const fileInput = ref(null);
const selectedFile = ref(null);
const photoPreview = ref(null);
const uploading = ref(false);
const errorText = ref('');

// Independent State Isolation: Using teacher-specific names to avoid overriding any Admin logic.
const teacherForm = ref({ name: '', email: '' });
const teacherPwd = ref({ current_password: '', password: '', password_confirmation: '' });

const toast = ref({ visible: false, message: '' });

/**
 * Target Role: Teacher
 * Source: TeacherProfileModal.vue
 * Destination: N/A (client only)
 * Function: Shows a short-lived message.
 */
function showToast(message) {
  toast.value = { visible: true, message };
  setTimeout(() => (toast.value.visible = false), 2800);
}

const initials = computed(() => {
  const name = (teacherProfile.value?.name || '').trim();
  if (!name) return 'T';
  const parts = name.split(/\s+/).filter(Boolean);
  const first = parts[0]?.[0] || '';
  const last = parts.length > 1 ? parts[parts.length - 1]?.[0] : '';
  return (first + last).toUpperCase() || 'T';
});

const isBusy = computed(() => teacherLoading.value || uploading.value);

function resolveProfilePhotoUrl(rawPath) {
  if (!rawPath) return null;
  const str = String(rawPath).trim();
  if (!str) return null;

  if (/^https?:\/\//i.test(str)) return str;

  const clean = str
    .replace(/^public\//, '')
    .replace(/^storage\//, '')
    .replace(/^\/storage\//, '')
    .replace(/^\//, '');

  return '/storage/' + clean;
}

const displayPhotoUrl = computed(() => {
  return photoPreview.value || resolveProfilePhotoUrl(teacherProfile.value?.profile_photo);
});

const strengthScore = computed(() => {
  const p = teacherPwd.value.password || '';
  let s = 0;
  if (p.length >= 8) s += 1;
  if (/[A-Z]/.test(p)) s += 1;
  if (/[0-9]/.test(p)) s += 1;
  if (/[^A-Za-z0-9]/.test(p)) s += 1;
  return s;
});

const strengthLabel = computed(() => {
  const s = strengthScore.value;
  if (s <= 1) return 'Weak';
  if (s <= 3) return 'Fair';
  return 'Strong';
});

const strengthWidth = computed(() => {
  const l = strengthLabel.value;
  if (l === 'Weak') return '30%';
  if (l === 'Fair') return '60%';
  return '100%';
});

const strengthBarClass = computed(() => {
  const l = strengthLabel.value;
  if (l === 'Weak') return 'bg-red-500';
  if (l === 'Fair') return 'bg-amber-500';
  return 'bg-emerald-500';
});

function close() {
  if (isBusy.value) {
    showToast('Please wait… saving is in progress.');
    return;
  }
  emit('update:modelValue', false);
}

function onBackdropClick() {
  close();
}

function triggerFile() {
  if (fileInput.value) fileInput.value.click();
}

function onFileChange(e) {
  const file = e.target.files?.[0];
  if (!file) {
    selectedFile.value = null;
    photoPreview.value = null;
    return;
  }

  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    showToast('Only JPG, PNG, or WebP images are allowed.');
    e.target.value = '';
    return;
  }

  if (file.size > 2 * 1024 * 1024) {
    showToast('Photo must be 2 MB or less.');
    e.target.value = '';
    return;
  }

  selectedFile.value = file;
  const reader = new FileReader();
  reader.onload = (ev) => {
    photoPreview.value = ev.target?.result || null;
  };
  reader.readAsDataURL(file);
}

// Data Flow: Safely populate state from the reactive composable object after DB response.
async function hydrateFromProfile() {
  if (teacherProfile.value) {
    teacherForm.value = {
      name: teacherProfile.value.name || '',
      email: teacherProfile.value.email || '',
    };
  }
  photoPreview.value = null;
  avatarError.value = false;
}

// Fixed Data Loss: Ensures fields are ONLY reset if the API update is 100% successful.
async function saveProfile() {
  errorText.value = '';
  try {
    const updated = await updateTeacherData({
      name: teacherForm.value.name,
      email: teacherForm.value.email,
    });
    teacherProfile.value = updated;

    if (selectedFile.value) {
      uploading.value = true;
      try {
        const res = await uploadTeacherPhotoData(selectedFile.value);
        if (res?.profile_photo) {
          teacherProfile.value = { ...(teacherProfile.value || {}), profile_photo: res.profile_photo };
        }
      } finally {
        uploading.value = false;
        selectedFile.value = null;
        photoPreview.value = null;
        if (fileInput.value) fileInput.value.value = '';
        avatarError.value = false;
      }
    }

    emit('profile-updated', teacherProfile.value);
    showToast('Profile updated successfully.');
  } catch (err) {
    errorText.value = teacherError.value || err?.response?.data?.message || 'Failed to update profile.';
    showToast(errorText.value);
  }
}

async function savePassword() {
  errorText.value = '';
  const hasAny =
    !!teacherPwd.value.current_password || !!teacherPwd.value.password || !!teacherPwd.value.password_confirmation;

  if (!hasAny) {
    return;
  }

  if (!teacherPwd.value.current_password || !teacherPwd.value.password || !teacherPwd.value.password_confirmation) {
    errorText.value = 'Fill in all password fields to change your password.';
    showToast(errorText.value);
    return;
  }

  if (teacherPwd.value.password.length < 8) {
    errorText.value = 'New password must be at least 8 characters.';
    showToast(errorText.value);
    return;
  }

  if (teacherPwd.value.password !== teacherPwd.value.password_confirmation) {
    errorText.value = 'New password and confirmation do not match.';
    showToast(errorText.value);
    return;
  }

  try {
    await changeTeacherPasswordData({
      current_password: teacherPwd.value.current_password,
      password: teacherPwd.value.password,
      password_confirmation: teacherPwd.value.password_confirmation,
    });
    // Data flow context reset: ONLY wipe passwords after a successful 200 server response.
    teacherPwd.value = { current_password: '', password: '', password_confirmation: '' };
    showToast('Password changed successfully.');
  } catch (err) {
    errorText.value = teacherError.value || err?.response?.data?.message || 'Failed to change password.';
    showToast(errorText.value);
  }
}

async function saveAll() {
  await saveProfile();

  if (teacherPwd.value.current_password || teacherPwd.value.password || teacherPwd.value.password_confirmation) {
    await savePassword();
  }
}

// Pre-Hydrate immediately when Modal mounts so it's not blank while loading
onMounted(() => {
  hydrateFromProfile();
});

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return;
    errorText.value = '';
    try {
      await fetchTeacherData();
      await hydrateFromProfile();
    } catch (err) {
      errorText.value = teacherError.value || err?.response?.data?.message || 'Failed to load profile.';
      showToast(errorText.value);
    }
  },
);
</script>
