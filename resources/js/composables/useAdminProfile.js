/**
 * @fileoverview useAdminProfile.js
 * 
 * Source: AdminProfileModal.vue
 * Destination: Vue 3 Composition Context
 * Function: Manages reactive data states (loading, errors) tracking the Admin profile forms.
 */

import { ref } from 'vue';
import { fetchAdminProfile, updateAdminProfile, uploadAdminProfilePhoto, changeAdminPassword } from '../services/adminProfileService';

/**
 * useAdminProfile
 * 
 * Source: AdminProfileModal.vue setup script
 * Destination: Exported ref bindings for the template
 * Function: Returns reactive objects and handler functions.
 * 
 * @returns {Object} { profile, loading, error, fetchProfile, updateProfile, uploadPhoto, changePassword }
 */
export function useAdminProfile() {
  const profile = ref(null);
  const loading = ref(false);
  const error = ref(null);

  /**
   * Safe data fetch fetching real "System Admin" data via service.
   * 
   * Source: Modal open triggers load.
   * Destination: adminProfileService.js /api/admin/profile
   * Function: Populates the reactive `profile` store.
   * 
   * @returns {Promise<Object>} The downloaded object.
   */
  async function fetchProfile() {
    loading.value = true;
    error.value = null;
    try {
      // Data Flow: Await JSON response mapped from standard Axios Promise
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

  /**
   * Sends the text inputs to be updated in MySQL.
   * 
   * Source: Modal standard save trigger
   * Destination: adminProfileService.js /api/admin/profile (PUT)
   * Function: Update reactive store if successful.
   * 
   * @param {Object} payload 
   * @returns {Promise<Object>}
   */
  async function updateProfile(payload) {
    loading.value = true;
    error.value = null;
    try {
      // Data Flow: Server responds with updated User object fields
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

  /**
   * Pass binary File to the API and update the Avatar URL locally.
   * 
   * Source: onFileChange File Input box
   * Destination: adminProfileService.js /api/admin/profile/photo
   * Function: Patches the active string bound to the `profile_photo` reactive prop.
   * 
   * @param {File} file 
   * @returns {Promise<Object>}
   */
  async function uploadPhoto(file) {
    if (!file) return;
    loading.value = true;
    error.value = null;
    try {
      const data = await uploadAdminProfilePhoto(file);
      // Data Flow: Ensure we do not overwrite the other model fields when patching just photo string
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

  /**
   * Submits a payload to replace the active Bearer Auth target user's password.
   * 
   * Source: Custom Password sub-form modal panel.
   * Destination: adminProfileService.js (PUT password endpoint)
   * Function: Standard boolean success handler.
   * 
   * @param {Object} payload 
   * @returns {Promise<Object>}
   */
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
