/**
 * @fileoverview adminProfileService.js
 * Handles Axios API calls for viewing and updating the Admin Profile / Password.
 */

import axios from 'axios';
import { getStoredToken } from '../router';

const base = '/api/admin';

function getAuthHeaders() {
    const token = getStoredToken();
    return token ? { Authorization: `Bearer ${token}` } : {};
}

/**
 * Fetch the currently authenticated admin's profile data.
 * @returns {Promise<Object>}
 */
export async function fetchAdminProfile() {
    const { data } = await axios.get(`${base}/profile`, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Update the admin's basic profile details (Name, Email).
 * @param {Object} payload - Includes { name, email }.
 * @returns {Promise<Object>} Updated profile data.
 */
export async function updateAdminProfile(payload) {
    const { data } = await axios.put(`${base}/profile`, payload, {
        headers: {
            ...getAuthHeaders(),
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
    });
    return data;
}

/**
 * Upload a new profile photo for the admin.
 * @param {File} file - Image file (JPG/PNG/WebP, < 2MB).
 * @returns {Promise<Object>} Returns { profile_photo: 'path/to/img.jpg' }
 */
export async function uploadAdminProfilePhoto(file) {
    const formData = new FormData();
    formData.append('photo', file);

    const { data } = await axios.post(`${base}/profile/photo`, formData, {
        headers: {
            ...getAuthHeaders(),
            Accept: 'application/json',
        },
    });
    return data;
}

/**
 * Change the admin's password.
 * @param {Object} payload - Includes { current_password, password, password_confirmation }.
 * @returns {Promise<Object>}
 */
export async function changeAdminPassword(payload) {
    const { data } = await axios.put(`${base}/profile/password`, payload, {
        headers: {
            ...getAuthHeaders(),
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
    });
    return data;
}
