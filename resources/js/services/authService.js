/**
 * @fileoverview authService.js
 * Handles all Axios API calls related to authentication and user state.
 *
 * All functions here (except login) require a valid Bearer token.
 * The token is read from localStorage key 'scan_up_token' via getStoredToken().
 *
 * Base API prefix: /api
 */

import axios from 'axios';
import { getStoredToken } from '../router'; // Assuming router exports this or has a centralized token manager

const base = '/api';

/**
 * Common headers for auth endpoints. Includes the Bearer token.
 * @returns {object} Headers object.
 */
function getAuthHeaders() {
    const token = getStoredToken();
    return token ? { Authorization: `Bearer ${token}` } : {};
}

/**
 * Fetch the currently authenticated user details.
 * Also configures the global axios authorization header for convenience.
 *
 * @returns {Promise<object>} The user object.
 */
export async function fetchUser() {
    const headers = getAuthHeaders();
    if (headers.Authorization) {
        axios.defaults.headers.common['Authorization'] = headers.Authorization;
    }
    const { data } = await axios.get(`${base}/user`, {
        headers: { ...headers, Accept: 'application/json' },
    });
    return data;
}

/**
 * Revoke the current access token (logout).
 *
 * @returns {Promise<void>}
 */
export async function logoutUser() {
    await axios.post(`${base}/logout`, {}, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
}
