<template>
  <transition
    enter-active-class="transition duration-150 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-120 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="close" />

      <div class="relative w-full max-w-lg rounded-xl overflow-hidden bg-white shadow-2xl">
        <div class="bg-[#050517] px-6 py-4 text-white">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h1 class="text-lg font-semibold tracking-tight">Admin Profile</h1>
              <p class="text-xs text-slate-300">Manage your account information and password.</p>
            </div>

            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-full overflow-hidden border-2 border-[#facc15] bg-slate-800 flex items-center justify-center text-base font-semibold"
              >
                <img
                  v-if="profile?.profile_photo && !avatarError"
                  :src="profile.profile_photo"
                  alt=""
                  class="w-full h-full object-cover"
                  @error="avatarError = true"
                />
                <span v-else>{{ profileInitial }}</span>
              </div>

              <button
                type="button"
                class="w-9 h-9 inline-flex items-center justify-center rounded-lg hover:bg-white/10 transition"
                aria-label="Close"
                @click="close"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <div class="border-b border-slate-200 flex">
          <button
            type="button"
            class="flex-1 px-4 py-3 text-sm font-medium flex items-center justify-center gap-2"
            :class="activeTab === 'profile'
              ? 'border-b-2 border-[#facc15] text-[#050517]'
              : 'text-slate-500 hover:text-slate-800'"
            @click="activeTab = 'profile'"
          >
            <span>Profile Info</span>
          </button>
          <button
            type="button"
            class="flex-1 px-4 py-3 text-sm font-medium flex items-center justify-center gap-2"
            :class="activeTab === 'password'
              ? 'border-b-2 border-[#facc15] text-[#050517]'
              : 'text-slate-500 hover:text-slate-800'"
            @click="activeTab = 'password'"
          >
            <span>Change Password</span>
          </button>
        </div>

        <div class="p-6">
          <div v-if="activeTab === 'profile'" class="space-y-6">
            <div class="flex flex-col sm:flex-row gap-6 items-start">
              <div class="flex flex-col items-center gap-3">
                <div
                  class="w-24 h-24 rounded-full overflow-hidden border-4 border-[#facc15] bg-slate-100 flex items-center justify-center shadow"
                >
                  <img
                    v-if="photoPreview && !avatarError"
                    :src="photoPreview"
                    alt="Profile photo preview"
                    class="w-full h-full object-cover"
                    @error="avatarError = true"
                  />
                  <span v-else class="text-3xl font-semibold text-[#050517]">
                    {{ profileInitial }}
                  </span>
                </div>
                <label
                  class="inline-flex items-center justify-center px-3 py-2 rounded-md text-xs font-medium cursor-pointer
                         bg-[#050517] text-white hover:bg-slate-900 transition"
                >
                  Change Photo
                  <input
                    ref="photoInput"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    class="sr-only"
                    @change="onPhotoSelected"
                  />
                </label>
                <p class="text-[11px] text-slate-500">JPG/PNG/WebP, max 2 MB.</p>
              </div>

              <form class="flex-1 space-y-4" @submit.prevent="handleSaveProfile">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name</label>
                    <input
                      v-model="form.name"
                      type="text"
                      required
                      class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#facc15]"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                    <input
                      v-model="form.email"
                      type="email"
                      required
                      class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#facc15]"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Phone</label>
                    <input
                      v-model="form.phone"
                      type="text"
                      class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#facc15]"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Position</label>
                    <input
                      v-model="form.position"
                      type="text"
                      class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#facc15]"
                    />
                  </div>
                </div>

                <div v-if="profileError" class="text-xs text-red-600 mt-1">
                  {{ profileError }}
                </div>

                <div class="flex justify-end mt-4">
                  <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium text-white
                           bg-[#050517] hover:bg-slate-900 disabled:opacity-60 disabled:cursor-not-allowed transition"
                    :disabled="loadingProfile"
                  >
                    <svg
                      v-if="loadingProfile"
                      class="animate-spin h-4 w-4 text-white"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                      />
                    </svg>
                    <span>Save Changes</span>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <div v-else class="space-y-5">
            <form class="space-y-4" @submit.prevent="handleChangePassword">
              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Current Password</label>
                <input
                  v-model="passwordForm.current_password"
                  type="password"
                  class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#facc15]"
                />
              </div>

              <div class="border-t border-slate-200" />

              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">New Password</label>
                <input
                  v-model="passwordForm.password"
                  type="password"
                  class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#facc15]"
                />
              </div>

              <div class="space-y-1">
                <div class="h-1.5 w-full rounded-full bg-slate-200 overflow-hidden">
                  <div
                    class="h-full transition-all"
                    :class="strengthBarClass"
                    :style="{ width: strengthBarWidth }"
                  ></div>
                </div>
                <p class="text-[11px]" :class="strengthTextClass">
                  Password strength: {{ strengthLabel }}
                </p>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Confirm New Password</label>
                <input
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#facc15]"
                />
              </div>

              <div v-if="passwordError" class="text-xs text-red-600">
                {{ passwordError }}
              </div>

              <div class="flex justify-end">
                <button
                  type="submit"
                  class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium text-white
                         bg-[#050517] hover:bg-slate-900 disabled:opacity-60 disabled:cursor-not-allowed transition"
                  :disabled="loadingPassword"
                >
                  <svg
                    v-if="loadingPassword"
                    class="animate-spin h-4 w-4 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                  </svg>
                  <span>Update Password</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <transition
        enter-active-class="transform ease-out duration-200"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform ease-in duration-150"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
      >
        <div
          v-if="toast.visible"
          class="fixed bottom-6 right-6 z-[60] max-w-xs rounded-lg shadow-lg px-4 py-3 flex items-start gap-3 text-sm"
          :class="toast.type === 'error'
            ? 'bg-red-600 text-white'
            : 'bg-emerald-600 text-white'"
        >
          <span class="font-semibold">{{ toast.type === 'error' ? 'Error' : 'Success' }}</span>
          <span class="flex-1">{{ toast.message }}</span>
          <button class="ml-2 text-xs opacity-80 hover:opacity-100" @click="toast.visible = false">×</button>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAdminProfile } from '../../composables/useAdminProfile';

const props = defineProps({
  modelValue: { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue']);

const {
  profile,
  loading,
  error,
  fetchProfile,
  updateProfile,
  uploadPhoto,
  changePassword,
} = useAdminProfile();

const activeTab = ref('profile');
const avatarError = ref(false);

const form = ref({
  name: '',
  email: '',
  phone: '',
  position: '',
});

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const photoInput = ref(null);
const selectedPhotoFile = ref(null);
const photoPreview = ref(null);

const profileError = ref('');
const passwordError = ref('');

const loadingProfile = computed(() => loading.value && activeTab.value === 'profile');
const loadingPassword = computed(() => loading.value && activeTab.value === 'password');

const toast = ref({
  visible: false,
  message: '',
  type: 'success',
});

function showToast(message, type = 'success') {
  toast.value = { visible: true, message, type };
  setTimeout(() => {
    toast.value.visible = false;
  }, 3000);
}

const profileInitial = computed(() => {
  if (profile.value?.name) {
    return profile.value.name.charAt(0).toUpperCase();
  }
  return 'A';
});

const strengthScore = computed(() => {
  const pwd = passwordForm.value.password || '';
  let score = 0;
  if (pwd.length >= 8) score += 1;
  if (/[A-Z]/.test(pwd)) score += 1;
  if (/[0-9]/.test(pwd)) score += 1;
  if (/[^A-Za-z0-9]/.test(pwd)) score += 1;
  return score;
});

const strengthLabel = computed(() => {
  const s = strengthScore.value;
  if (s <= 1) return 'Weak';
  if (s === 2 || s === 3) return 'Fair';
  return 'Strong';
});

const strengthBarWidth = computed(() => {
  const s = strengthScore.value;
  if (s <= 1) return '33%';
  if (s === 2 || s === 3) return '66%';
  return '100%';
});

const strengthBarClass = computed(() => {
  const label = strengthLabel.value;
  if (label === 'Weak') return 'bg-red-500';
  if (label === 'Fair') return 'bg-yellow-400';
  return 'bg-emerald-500';
});

const strengthTextClass = computed(() => {
  const label = strengthLabel.value;
  if (label === 'Weak') return 'text-red-600';
  if (label === 'Fair') return 'text-yellow-600';
  return 'text-emerald-600';
});

const isOpen = computed(() => props.modelValue);

function close() {
  emit('update:modelValue', false);
}

watch(
  () => profile.value,
  (val) => {
    if (!val) return;
    form.value = {
      name: val.name || '',
      email: val.email || '',
      phone: val.phone || '',
      position: val.position || '',
    };
    photoPreview.value = val.profile_photo || null;
  },
  { immediate: true },
);

async function handleSaveProfile() {
  profileError.value = '';
  try {
    await updateProfile({
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone || null,
      position: form.value.position || null,
    });

    if (selectedPhotoFile.value) {
      await uploadPhoto(selectedPhotoFile.value);
      selectedPhotoFile.value = null;
      if (photoInput.value) {
        photoInput.value.value = '';
      }
    }

    showToast('Profile updated successfully.');
  } catch {
    profileError.value = error.value || 'Failed to update profile.';
    showToast(profileError.value, 'error');
  }
}

function onPhotoSelected(e) {
  const file = e.target.files?.[0];
  if (!file) {
    selectedPhotoFile.value = null;
    photoPreview.value = profile.value?.profile_photo || null;
    return;
  }

  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    showToast('Only JPG, PNG, or WebP images are allowed.', 'error');
    if (photoInput.value) {
      photoInput.value.value = '';
    }
    return;
  }

  if (file.size > 2 * 1024 * 1024) {
    showToast('Photo must be 2 MB or less.', 'error');
    if (photoInput.value) {
      photoInput.value.value = '';
    }
    return;
  }

  selectedPhotoFile.value = file;
  const reader = new FileReader();
  reader.onload = (ev) => {
    photoPreview.value = ev.target?.result;
  };
  reader.readAsDataURL(file);
}

async function handleChangePassword() {
  passwordError.value = '';

  const hasAny =
    !!passwordForm.value.current_password ||
    !!passwordForm.value.password ||
    !!passwordForm.value.password_confirmation;

  if (!hasAny) {
    return;
  }

  if (
    !passwordForm.value.current_password ||
    !passwordForm.value.password ||
    !passwordForm.value.password_confirmation
  ) {
    passwordError.value = 'Fill in all password fields to change your password.';
    showToast(passwordError.value, 'error');
    return;
  }

  if (passwordForm.value.password.length < 8) {
    passwordError.value = 'New password must be at least 8 characters.';
    showToast(passwordError.value, 'error');
    return;
  }

  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = 'New password and confirmation do not match.';
    showToast(passwordError.value, 'error');
    return;
  }

  try {
    await changePassword({
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    });

    passwordForm.value.current_password = '';
    passwordForm.value.password = '';
    passwordForm.value.password_confirmation = '';

    showToast('Password updated successfully.');
  } catch {
    passwordError.value = error.value || 'Failed to change password.';
    showToast(passwordError.value, 'error');
  }
}

onMounted(async () => {
  try {
    await fetchProfile();
  } catch {
    showToast('Failed to load profile.', 'error');
  }
});
</script>

