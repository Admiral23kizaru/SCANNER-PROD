<template>
  <div class="min-h-screen w-full flex flex-col md:flex-row bg-slate-950">
    <!-- Left panel: logo + system name -->
    <section
      class="relative flex-1 flex items-center justify-center px-8 py-10 bg-gradient-to-br from-[#fdfaf2] via-[#f2fbf9] to-[#b9e4df]"
    >
      <div
        class="absolute inset-0 pointer-events-none opacity-50"
        style="
          background-image: radial-gradient(circle at 1px 1px, rgba(15, 23, 42, 0.05) 1px, transparent 0);
          background-size: 18px 18px;
        "
      ></div>

      <div class="relative max-w-md w-full text-center space-y-6">
        <div class="flex justify-center">
          <div
            class="h-48 w-48 md:h-56 md:w-56 rounded-full shadow-xl shadow-slate-400/40 ring-4 ring-white/80 bg-white flex items-center justify-center overflow-hidden"
          >
            <img
              :src="depedLogo"
              alt="Department of Education Ozamiz City"
              class="h-full w-full object-contain"
            />
          </div>
        </div>
        <div>
          <p class="text-xs md:text-sm tracking-[0.3em] uppercase text-slate-500">
            Department of Education · Ozamiz City
          </p>
          <h1 class="mt-3 text-2xl md:text-3xl font-semibold text-slate-900">
            Ozamiz Schools QR-ID System
          </h1>
        </div>
      </div>
    </section>

    <!-- Right panel: administrative login -->
    <section
      class="flex-1 flex items-center justify-center px-6 py-10 bg-gradient-to-br from-[#020617] via-[#020617] to-[#0f172a]"
    >
      <div class="max-w-sm w-full text-slate-50">
        <div class="mb-8">
          <p class="text-xs uppercase tracking-[0.32em] text-slate-400">Administrative</p>
          <h2 class="mt-2 text-2xl md:text-3xl font-semibold">Log in</h2>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label for="email" class="block text-sm font-medium text-slate-200 mb-1">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              class="w-full rounded-md border border-slate-600/70 bg-slate-950/40 px-3 py-2.5 text-sm text-slate-50 placeholder:text-slate-500 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 shadow-sm"
              placeholder="username@example.com"
            />
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-slate-200 mb-1">
              Password
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                class="w-full rounded-md border border-slate-600/70 bg-slate-950/40 px-3 py-2.5 pr-10 text-sm text-slate-50 placeholder:text-slate-500 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 shadow-sm"
                placeholder="********"
              />
              <button
                type="button"
                @click="togglePassword"
                class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-200"
                aria-label="Toggle password visibility"
              >
                <svg
                  v-if="!showPassword"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                  <circle cx="12" cy="12" r="3.5" />
                </svg>
                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                  <path d="M10.58 10.58A3 3 0 0 0 13.42 13.4" />
                  <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
              </button>
            </div>
          </div>

          <div v-if="error" class="text-xs text-red-400 bg-red-950/40 border border-red-900/60 rounded px-3 py-2">
            {{ error }}
          </div>

          <button
            type="submit"
            class="w-full rounded-md bg-gradient-to-r from-sky-500 via-teal-400 to-emerald-400 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-sky-900/40 hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-400 focus:ring-offset-slate-950 disabled:opacity-60 disabled:cursor-not-allowed"
            :disabled="loading"
          >
            {{ loading ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>

        <div class="mt-4 flex justify-end">
          <button
            type="button"
            class="text-xs text-slate-400 hover:text-slate-200 underline underline-offset-4"
            @click="openResetModal"
          >
            Forgot Password?
          </button>
        </div>
      </div>
    </section>
  </div>

  <!-- Password reset modal -->
  <div
    v-if="showResetModal"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur"
  >
    <div
      class="w-full max-w-md mx-4 rounded-2xl bg-slate-900 border border-slate-700 shadow-2xl overflow-hidden"
    >
      <!-- Modal header -->
      <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
        <div>
          <p class="text-xs uppercase tracking-[0.28em] text-slate-400">Security</p>
          <h3 class="mt-1 text-sm font-semibold text-slate-50">
            {{
              resetStep === 1
                ? 'Request reset code'
                : resetStep === 2
                  ? 'Verify your code'
                  : 'Set a new password'
            }}
          </h3>
        </div>
        <button
          type="button"
          class="h-8 w-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-slate-800 transition"
          @click="closeResetModal"
        >
          <span class="sr-only">Close</span>
          ✕
        </button>
      </div>

      <!-- Modal body -->
      <div class="px-6 py-5 space-y-4">
        <!-- Step indicator -->
        <div class="flex items-center gap-2 text-[11px] text-slate-400">
          <span
            v-for="step in 3"
            :key="step"
            class="flex-1 h-1 rounded-full"
            :class="
              step <= resetStep
                ? 'bg-gradient-to-r from-sky-500 via-teal-400 to-emerald-400'
                : 'bg-slate-700'
            "
          ></span>
        </div>

        <p class="text-xs text-slate-400">
          {{
            resetStep === 1
              ? 'Enter your registered email and we will send a one-time code to verify your identity.'
              : resetStep === 2
                ? 'Check your inbox for the 6-digit code and enter it below.'
                : 'Choose a strong new password to secure your account.'
          }}
        </p>

        <div v-if="resetError" class="text-xs text-red-400 bg-red-950/40 border border-red-900/60 rounded px-3 py-2">
          {{ resetError }}
        </div>
        <div
          v-if="resetSuccess"
          class="text-xs text-emerald-300 bg-emerald-900/30 border border-emerald-600/60 rounded px-3 py-2"
        >
          {{ resetSuccess }}
        </div>

        <!-- Step 1: Email -->
        <div v-if="resetStep === 1" class="space-y-3">
          <label class="block text-xs font-medium text-slate-200 mb-1" for="reset-email">
            Email address
          </label>
          <input
            id="reset-email"
            v-model="resetEmail"
            type="email"
            autocomplete="email"
            class="w-full rounded-md border border-slate-700 bg-slate-950/60 px-3 py-2.5 text-sm text-slate-50 placeholder:text-slate-500 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400"
            placeholder="username@example.com"
          />
          <button
            type="button"
            class="mt-4 w-full rounded-md bg-gradient-to-r from-sky-500 via-teal-400 to-emerald-400 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-sky-900/40 hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-400 focus:ring-offset-slate-900 disabled:opacity-60 disabled:cursor-not-allowed"
            @click="requestOtp"
            :disabled="resetLoading || !resetEmail"
          >
            {{ resetLoading ? 'Sending code…' : 'Send OTP' }}
          </button>
        </div>
        
        <!-- Step 2: OTP -->
        <div v-else-if="resetStep === 2" class="space-y-3">
          <label class="block text-xs font-medium text-slate-200 mb-1" for="reset-otp">
            6-digit code
          </label>
          <input
            id="reset-otp"
            v-model="resetOtp"
            type="text"
            inputmode="numeric"
            maxlength="6"
            class="w-full tracking-[0.5em] text-center rounded-md border border-slate-700 bg-slate-950/60 px-3 py-2.5 text-sm text-slate-50 placeholder:text-slate-500 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400"
            placeholder="••••••"
          />
          <button
            type="button"
            class="mt-4 w-full rounded-md bg-gradient-to-r from-sky-500 via-teal-400 to-emerald-400 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-sky-900/40 hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-400 focus:ring-offset-slate-900 disabled:opacity-60 disabled:cursor-not-allowed"
            @click="verifyOtp"
            :disabled="resetLoading || resetOtp.length !== 6"
          >
            {{ resetLoading ? 'Verifying…' : 'Verify' }}
          </button>
        </div>

        <!-- Step 3: New password -->
        <div v-else class="space-y-3">
          <label class="block text-xs font-medium text-slate-200 mb-1" for="reset-password">
            New password
          </label>
          <input
            id="reset-password"
            v-model="resetPassword"
            type="password"
            autocomplete="new-password"
            class="w-full rounded-md border border-slate-700 bg-slate-950/60 px-3 py-2.5 text-sm text-slate-50 placeholder:text-slate-500 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400"
            placeholder="Enter a strong password"
          />
          <button
            type="button"
            class="mt-4 w-full rounded-md bg-gradient-to-r from-sky-500 via-teal-400 to-emerald-400 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-sky-900/40 hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-400 focus:ring-offset-slate-900 disabled:opacity-60 disabled:cursor-not-allowed"
            @click="resetPasswordSubmit"
            :disabled="resetLoading || resetPassword.length < 6"
          >
            {{ resetLoading ? 'Resetting…' : 'Reset Password' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { setStoredToken } from '../router';

const depedLogo = '/logo/depedozamiz.png';

const router = useRouter();
const form = reactive({ email: '', password: '' });
const loading = ref(false);
const error = ref('');
const showPassword = ref(false);

function togglePassword() {
  showPassword.value = !showPassword.value;
}

const showResetModal = ref(false);
const resetStep = ref(1);
const resetEmail = ref('');
const resetOtp = ref('');
const resetPassword = ref('');
const resetLoading = ref(false);
const resetError = ref('');
const resetSuccess = ref('');

function openResetModal() {
  resetStep.value = 1;
  resetEmail.value = form.email || '';
  resetOtp.value = '';
  resetPassword.value = '';
  resetError.value = '';
  resetSuccess.value = '';
  showResetModal.value = true;
}

function closeResetModal() {
  showResetModal.value = false;
}

async function requestOtp() {
  resetError.value = '';
  resetSuccess.value = '';
  if (!resetEmail.value) return;
  resetLoading.value = true;
  try {
    const { data } = await axios.post('/api/password/request-otp', {
      email: resetEmail.value,
    });
    resetSuccess.value = data.message || 'We have sent a verification code if the email exists in our system.';
    resetStep.value = 2;
  } catch (err) {
    resetError.value =
      err.response?.data?.message ||
      err.response?.data?.errors?.email?.[0] ||
      'Unable to send reset code. Please try again.';
  } finally {
    resetLoading.value = false;
  }
}

async function verifyOtp() {
  resetError.value = '';
  resetSuccess.value = '';
  if (!resetEmail.value || resetOtp.value.length !== 6) return;
  resetLoading.value = true;
  try {
    const { data } = await axios.post('/api/password/verify-otp', {
      email: resetEmail.value,
      otp: resetOtp.value,
    });
    resetSuccess.value = data.message || 'Code verified. You can now set a new password.';
    resetStep.value = 3;
  } catch (err) {
    resetError.value =
      err.response?.data?.message ||
      err.response?.data?.errors?.otp?.[0] ||
      'The code you entered is invalid or has expired.';
  } finally {
    resetLoading.value = false;
  }
}

async function resetPasswordSubmit() {
  resetError.value = '';
  resetSuccess.value = '';
  if (!resetEmail.value || !resetOtp.value || resetPassword.value.length < 6) return;
  resetLoading.value = true;
  try {
    const { data } = await axios.post('/api/password/reset', {
      email: resetEmail.value,
      otp: resetOtp.value,
      password: resetPassword.value,
      password_confirmation: resetPassword.value,
    });
    resetSuccess.value = data.message || 'Your password has been reset. You can now sign in.';
    setTimeout(() => {
      showResetModal.value = false;
    }, 1200);
  } catch (err) {
    resetError.value =
      err.response?.data?.message ||
      err.response?.data?.errors?.password?.[0] ||
      'Unable to reset password. Please try again.';
  } finally {
    resetLoading.value = false;
  }
}

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    const { data } = await axios.post('/api/login', {
      email: form.email,
      password: form.password,
    });
    const token = data.token;
    if (token) {
      setStoredToken(token);
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
    const roleName = data.user?.role?.name || data.user?.role_name || '';
    if (roleName === 'Admin') {
      await router.replace('/admin');
    } else if (roleName === 'Teacher') {
      await router.replace('/teacher');
    } else if (roleName === 'Guard') {
      window.location.href = '/guard';
      return;
    } else {
      await router.replace('/login');
    }
  } catch (err) {
    const msg =
      err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Login failed.';
    error.value = msg;
    alert(msg);
  } finally {
    loading.value = false;
  }
}
</script>
