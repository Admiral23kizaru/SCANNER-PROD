import { ref } from 'vue';
import { fetchAdminProfile, updateAdminProfile, uploadAdminProfilePhoto, changeAdminPassword } from '../services/adminProfileService';

export function useAdminProfile() {
  const profile = ref(null);
  const loading = ref(false);
  const error = ref(null);

  /** Fetch admin profile safely. */
  async function fetchProfile() {
    loading.value = true;
    error.value = null;
    try {
      const data = await fetchAdminProfile();
      profile.value = data;
      return data;
    } catch (err) {
      error.value = err?.response?.data?.message || 'Failed to load admin profile.';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /** Update admin basic info. */
  async function updateProfile(payload) {
    loading.value = true;
    error.value = null;
    try {
      const data = await updateAdminProfile(payload);
      profile.value = data;
      return data;
    } catch (err) {
      const msg = err?.response?.data?.message;
      const errors = err?.response?.data?.errors;
      error.value = errors ? Object.values(errors).flat().join(' ') : (msg || 'Failed to update profile.');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /** Upload new admin profile picture. */
  async function uploadPhoto(file) {
    if (!file) return;
    loading.value = true;
    error.value = null;
    try {
      const data = await uploadAdminProfilePhoto(file);
      if (profile.value && data?.profile_photo) {
        profile.value = { ...profile.value, profile_photo: data.profile_photo };
      }
      return data;
    } catch (err) {
      const msg = err?.response?.data?.message;
      const errors = err?.response?.data?.errors;
      error.value = errors ? Object.values(errors).flat().join(' ') : (msg || 'Failed to upload profile photo.');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /** Change admin password. */
  async function changePassword(payload) {
    loading.value = true;
    error.value = null;
    try {
      const data = await changeAdminPassword(payload);
      return data;
    } catch (err) {
      const msg = err?.response?.data?.message;
      const errors = err?.response?.data?.errors;
      error.value = errors ? Object.values(errors).flat().join(' ') : (msg || 'Failed to change password.');
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
