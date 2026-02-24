<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4">
    <div class="w-full max-w-sm bg-white rounded-lg shadow-md border border-slate-200 p-6">
      <h1 class="text-xl font-semibold text-slate-800 text-center mb-6">ScanUp</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            placeholder="email@example.com"
          />
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          />
        </div>
        <div v-if="error" class="text-sm text-red-600">{{ error }}</div>
        <button
          type="submit"
          class="w-full rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
          :disabled="loading"
        >
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { setStoredToken } from '../router';

const router = useRouter();
const form = reactive({ email: '', password: '' });
const loading = ref(false);
const error = ref('');

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
    const msg = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Login failed.';
    error.value = msg;
    alert(msg);
  } finally {
    loading.value = false;
  }
}
</script>
