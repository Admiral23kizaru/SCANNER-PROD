/**
 * @fileoverview teacherProfileService.js
 * 
 * Target Role: Teacher
 * Source: TeacherProfileModal.vue
 * Destination: Laravel /api/teacher/update-profile
 * Function: Dedicated Axios service for Teacher Profile management. Isolated from Admin state to ensure security and prevent data overlap.
 */

import axios from 'axios';
import { getStoredToken } from '../router';

const base = '/api/teacher';

/**
 * Target Role: Teacher
 * Source: TeacherProfileModal.vue
 * Destination: Axios request headers
 * Function: Builds the Authorization header from the stored Bearer token.
 *
 * @returns {{ Authorization: string } | {}} Auth header object (or empty object).
 */
function getAuthHeaders() {
    const token = getStoredToken();
    return token ? { Authorization: `Bearer ${token}` } : {};
}

/**
 * Fetch the Teacher profile data.
 * 
 * Target Role: Teacher
 * Source: TeacherProfileModal.vue -> useTeacherProfile()
 * Destination: GET /api/teacher/profile 
 * Function: Pulls the active Teacher's account data.
 * 
 * @returns {Promise<Object>} Formatted user profile block 
 */
export async function fetchTeacherProfile() {
    const { data } = await axios.get(`${base}/profile`, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    // Data Flow: Axios resolves the active Teacher's profile parameters
    return data;
}

/**
 * Update the basic profile (Name, Email).
 * 
 * Target Role: Teacher
 * Source: Save Changes button in modal (if name/email modified).
 * Destination: PUT /api/teacher/update-profile (Users table)
 * Function: Saves the updated basic string fields independently.
 * 
 * @param {Object} payload - { name: '...', email: '...' }
 * @returns {Promise<Object>} Returns newly updated record
 */
export async function updateTeacherProfile(payload) {
    const { data } = await axios.put(`${base}/update-profile`, payload, {
        headers: {
            ...getAuthHeaders(),
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
    });
    // Data Flow: Validations pass in Laravel; returns the updated User model JSON
    return data;
}

/**
 * Upload a picture for the teacher avatar.
 * 
 * Target Role: Teacher
 * Source: Change Photo -> onFileChange
 * Destination: POST /api/teacher/update-profile/photo
 * Function: Posts a File Blob as multipart/form-data specifically for Teacher.
 * 
 * @param {File} file - Validated < 2MB Image (JPG/PNG/WebP)
 * @returns {Promise<Object>} Contains `{ profile_photo: "..." }`
 */
export async function uploadTeacherProfilePhoto(file) {
    // Data Flow: Wrap the raw browser File blob in FormData for multipart upload.
    const formData = new FormData();
    // Data Flow: Field name MUST match Laravel's expected request key (`photo`).
    formData.append('photo', file);

    const { data } = await axios.post(`${base}/update-profile/photo`, formData, {
        headers: {
            ...getAuthHeaders(),
            Accept: 'application/json',
            // Data Flow: Force multipart so PHP/Laravel reads the file from $_FILES.
            'Content-Type': 'multipart/form-data',
        },
    });
    return data;
}

/**
 * Change the teacher account password.
 * 
 * Target Role: Teacher
 * Source: Save Changes button (if password filled)
 * Destination: PUT /api/teacher/update-profile/password
 * Function: Updates the hash for Teacher in the DB securely.
 * 
 * @param {Object} payload - { current_password, password, password_confirmation }
 * @returns {Promise<Object>} Success boolean / message
 */
export async function changeTeacherPassword(payload) {
    const { data } = await axios.put(`${base}/update-profile/password`, payload, {
        headers: {
            ...getAuthHeaders(),
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
    });
    // Data Flow: Laravel Hash facade checks old password, then hashes and saves new one.
    return data;
}
