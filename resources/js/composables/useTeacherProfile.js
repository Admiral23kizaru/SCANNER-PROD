/**
 * @fileoverview useTeacherProfile.js
 * 
 * Target Role: Teacher
 * Source: TeacherProfileModal.vue
 * Destination: Vue 3 Composition Context
 * Function: Manages reactive data states (loading, errors) tracking the Teacher profile forms independently from the Admin forms.
 */

import { ref } from 'vue';
// Use the dedicated teacherProfileService to naturally hit /api/teacher/...
import { fetchTeacherProfile, updateTeacherProfile, uploadTeacherProfilePhoto, changeTeacherPassword } from '../services/teacherProfileService';

/**
 * useTeacherProfile
 * 
 * Target Role: Teacher
 * Source: TeacherProfileModal.vue setup script
 * Destination: Exported ref bindings for the template
 * Function: Returns reactive objects and handler functions explicitly bound to a Teacher's scope.
 * 
 * @returns {Object} { teacherProfile, teacherLoading, teacherError, fetchTeacherData, updateTeacherData, uploadTeacherPhotoData, changeTeacherPasswordData }
 */
export function useTeacherProfile() {
  // Independent State Isolation: Using teacher_ prefix to prevent any global pollution.
  const teacherProfile = ref(null);
  const teacherLoading = ref(false);
  const teacherError = ref(null);

  /**
   * Safe data fetch fetching real Teacher data via service.
   * 
   * Target Role: Teacher
   * Source: Modal instance opens
   * Destination: teacherProfileService.js /api/teacher/profile
   * Function: Populates the isolated `teacherProfile` reactive store.
   * 
   * @returns {Promise<Object>} The downloaded object.
   */
  async function fetchTeacherData() {
    teacherLoading.value = true;
    teacherError.value = null;
    try {
      // Data Flow: Await JSON response mapped from standard Axios Promise strictly for Teacher
      const data = await fetchTeacherProfile();
      teacherProfile.value = data;
      return data;
    } catch (err) {
      teacherError.value = err?.response?.data?.message || 'Failed to load teacher profile.';
      throw err;
    } finally {
      teacherLoading.value = false;
    }
  }

  /**
   * Sends the text inputs to be updated securely.
   * 
   * Target Role: Teacher
   * Source: Modal standard save trigger
   * Destination: teacherProfileService.js /api/teacher/update-profile (PUT)
   * Function: Update reactive store if successful.
   * 
   * @param {Object} payload 
   * @returns {Promise<Object>}
   */
  async function updateTeacherData(payload) {
    teacherLoading.value = true;
    teacherError.value = null;
    try {
      // Data Flow: Server responds with updated User object fields
      const data = await updateTeacherProfile(payload);
      teacherProfile.value = data;
      return data;
    } catch (err) {
      const msg = err?.response?.data?.message;
      const errors = err?.response?.data?.errors;
      teacherError.value = errors ? Object.values(errors).flat().join(' ') : (msg || 'Failed to update teacher profile.');
      throw err;
    } finally {
      teacherLoading.value = false;
    }
  }

  /**
   * Pass binary File to the API and update the Avatar URL locally.
   * 
   * Target Role: Teacher
   * Source: onFileChange File Input box
   * Destination: teacherProfileService.js /api/teacher/update-profile/photo (POST)
   * Function: Patches the active string bound to the `profile_photo` reactive prop.
   * 
   * @param {File} file 
   * @returns {Promise<Object>}
   */
  async function uploadTeacherPhotoData(file) {
    if (!file) return;
    teacherLoading.value = true;
    teacherError.value = null;
    try {
      const data = await uploadTeacherProfilePhoto(file);
      // Data Flow: Ensure we do not overwrite other fields when patching just photo string
      if (teacherProfile.value && data?.profile_photo) {
        teacherProfile.value = { ...teacherProfile.value, profile_photo: data.profile_photo };
      }
      return data;
    } catch (err) {
      const msg = err?.response?.data?.message;
      const errors = err?.response?.data?.errors;
      teacherError.value = errors ? Object.values(errors).flat().join(' ') : (msg || 'Failed to upload photo.');
      throw err;
    } finally {
      teacherLoading.value = false;
    }
  }

  /**
   * Submits a payload to replace the active Teacher's password.
   * 
   * Target Role: Teacher
   * Source: Custom Password sub-form modal panel.
   * Destination: teacherProfileService.js /api/teacher/update-profile/password (PUT)
   * Function: Standard boolean success handler.
   * 
   * @param {Object} payload 
   * @returns {Promise<Object>}
   */
  async function changeTeacherPasswordData(payload) {
    teacherLoading.value = true;
    teacherError.value = null;
    try {
      const data = await changeTeacherPassword(payload);
      return data;
    } catch (err) {
      const msg = err?.response?.data?.message;
      const errors = err?.response?.data?.errors;
      teacherError.value = errors ? Object.values(errors).flat().join(' ') : (msg || 'Failed to change password.');
      throw err;
    } finally {
      teacherLoading.value = false;
    }
  }

  return {
    teacherProfile,
    teacherLoading,
    teacherError,
    fetchTeacherData,
    updateTeacherData,
    uploadTeacherPhotoData,
    changeTeacherPasswordData,
  };
}
