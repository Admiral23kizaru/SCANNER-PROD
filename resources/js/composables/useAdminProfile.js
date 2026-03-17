import { ref } from 'vue';
import axios from 'axios';

function getAuthHeaders() {
  // This app historically uses scan_up_token; keep compatibility with "token" too.
  const token = localStorage.getItem('scan_up_token') || localStorage.getItem('token');
  return token ? { Authorization: `Bearer ${token}` } : {};
}

const base = '/api/admin';

export function useAdminProfile() {
  const profile = ref(null);
  const loading = ref(false);
  const error = ref(null);

  async function fetchProfile() {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(`${base}/profile`, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
      });
      profile.value = data;
      return data;
    } catch (err) {
      error.value =
        err?.response?.data?.message || 'Failed to load admin profile.';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateProfile(payload) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.put(`${base}/profile`, payload, {
        headers: {
          ...getAuthHeaders(),
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      });
      profile.value = data;
      return data;
    } catch (err) {
      const msg = err?.response?.data?.message;
      const errors = err?.response?.data?.errors;
      error.value = errors
        ? Object.values(errors).flat().join(' ')
        : msg || 'Failed to update profile.';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function uploadPhoto(file) {
    if (!file) return;
    loading.value = true;
    error.value = null;
    try {
      const formData = new FormData();
      formData.append('photo', file);

      const { data } = await axios.post(`${base}/profile/photo`, formData, {
        headers: {
          ...getAuthHeaders(),
          Accept: 'application/json',
        },
      });

      if (profile.value && data?.profile_photo) {
        profile.value = { ...profile.value, profile_photo: data.profile_photo };
      }

      return data;
    } catch (err) {
      const msg = err?.response?.data?.message;
      const errors = err?.response?.data?.errors;
      error.value = errors
        ? Object.values(errors).flat().join(' ')
        : msg || 'Failed to upload profile photo.';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function changePassword(payload) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.put(`${base}/profile/password`, payload, {
        headers: {
          ...getAuthHeaders(),
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
      });
      return data;
    } catch (err) {
      const msg = err?.response?.data?.message;
      const errors = err?.response?.data?.errors;
      error.value = errors
        ? Object.values(errors).flat().join(' ')
        : msg || 'Failed to change password.';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    profile,
    loading,
    error,
    fetchProfile,
    updateProfile,
    uploadPhoto,
    changePassword,
  };
}

