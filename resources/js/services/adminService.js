/**
 * @fileoverview adminService.js
 * Handles all Axios API calls for the Admin Dashboard panel.
 *
 * All functions here require a valid Bearer token (Admin role).
 * The token is read from localStorage key 'scan_up_token'.
 *
 * Base API prefix: /api/admin
 */

import axios from 'axios';

// ─── Auth helper ─────────────────────────────────────────────────────────────

/**
 * Build the Authorization header from the current user's stored token.
 *
 * @returns {{ Authorization: string } | {}} Empty object if no token found.
 */
function getAuthHeaders() {
    const token = localStorage.getItem('scan_up_token');
    return token ? { Authorization: `Bearer ${token}` } : {};
}

const base = '/api/admin';

// ─── Dashboard & Stats ────────────────────────────────────────────────────────

/**
 * Fetch quick summary stats (total students, teachers, today's attendance).
 * Used by the admin dashboard header stat cards.
 *
 * @returns {Promise<{ total_students: number, total_teachers: number, todays_attendance: number }>}
 */
export async function fetchStats() {
    const { data } = await axios.get(base + '/stats', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Fetch detailed school-wide dashboard statistics (cached 3 minutes server-side).
 *
 * Returns student/teacher totals, today's attendance vs. historical average,
 * and per-grade attendance breakdown for the chart components.
 *
 * @returns {Promise<{
 *   totals: { students: number, teachers: number, attendance_today: number, is_above_average: boolean },
 *   attendance_by_grade: Array<{ grade: string, count: number }>,
 *   historical_average: number
 * }>}
 */
export async function fetchDashboardStats() {
    const { data } = await axios.get(base + '/dashboard/stats', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Fetch attendance trend data for the line/bar chart on the dashboard.
 *
 * Supports grouping by: 'day' (last 30 days), 'week' (last 12 weeks), 'month' (last 12 months).
 * Optionally filter by grade or section.
 *
 * @param {{ group_by?: 'day'|'week'|'month', grade?: string, section?: string }} [params={}]
 * @returns {Promise<Array<{ label: string, count: number }>>} Time-series data for Chart.js.
 */
export async function fetchAttendanceTrends(params = {}) {
    const { data } = await axios.get(base + '/attendance/trends', {
        params,
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Fetch the dashboard overview: stats + recent activity feed (attendance + new users).
 *
 * @returns {Promise<{
 *   stats: object,
 *   recent_activity: Array<{ type: string, title: string, subtitle: string, time: string }>
 * }>}
 */
export async function fetchDashboardOverview() {
    const { data } = await axios.get(base + '/dashboard/overview', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Download the daily attendance summary as a PDF Blob.
 *
 * The returned Blob can be converted to an Object URL and triggered as a browser download.
 *
 * @returns {Promise<Blob>} PDF file data.
 */
export async function fetchSummaryReportPdfBlob() {
    const res = await axios.get(base + '/reports/summary-pdf', {
        headers: { ...getAuthHeaders(), Accept: 'application/pdf' },
        responseType: 'blob',
    });
    return res.data;
}

// ─── Teacher Management ───────────────────────────────────────────────────────

/**
 * Fetch all teacher accounts ordered by first name.
 *
 * @returns {Promise<{ data: Array<{ id: number, name: string, employee_id: string, school_name: string, job_title: string, profile_photo: string|null }> }>}
 */
export async function fetchTeachers() {
    const { data } = await axios.get(base + '/teachers', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Create a new teacher account.
 *
 * Also creates a corresponding user record so the teacher can log in.
 * A placeholder @deped.local email is generated if none is provided.
 *
 * @param {{ name: string, employee_id: string, password: string, password_confirmation: string, school_name?: string, job_title?: string }} payload
 * @returns {Promise<{ message: string, teacher: object }>}
 */
export async function createTeacher(payload) {
    const { data } = await axios.post(base + '/teachers', payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

/**
 * Update an existing teacher's profile and sync changes to the users table.
 *
 * @param {number} id - Teacher's database ID.
 * @param {Partial<{ name: string, employee_id: string, password: string, school_name: string, job_title: string }>} payload
 * @returns {Promise<{ message: string, teacher: object }>}
 */
export async function updateTeacher(id, payload) {
    const { data } = await axios.put(base + '/teachers/' + id, payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

/**
 * Delete a teacher account (and the linked users row).
 *
 * Will return 422 Unprocessable if the teacher has created student records.
 *
 * @param {number} id - Teacher's database ID.
 * @returns {Promise<{ message: string }>}
 */
export async function deleteTeacher(id) {
    const { data } = await axios.delete(base + '/teachers/' + id, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Upload or replace a teacher's profile photo.
 *
 * @param {number} id - Teacher's database ID.
 * @param {File} file - Image file (jpg/jpeg/png, max 2 MB).
 * @returns {Promise<{ message: string, profile_photo: string }>}
 */
export async function uploadTeacherPhoto(id, file) {
    const formData = new FormData();
    formData.append('photo', file);
    const { data } = await axios.post(base + '/teachers/' + id + '/photo', formData, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Download all teachers as a UTF-8 CSV Blob.
 *
 * @returns {Promise<Blob>} CSV file data.
 */
export async function exportAdminTeachers() {
    const res = await axios.get(base + '/teachers/export', {
        headers: { ...getAuthHeaders(), Accept: 'text/csv' },
        responseType: 'blob',
    });
    return res.data;
}

// ─── Admin Student Management ─────────────────────────────────────────────────

/**
 * Fetch a paginated list of all students (admin sees all, no ownership filter).
 *
 * @param {{ page?: number, per_page?: number, search?: string }} [params={}]
 * @returns {Promise<{ data: Array<object>, current_page: number, last_page: number, total: number }>}
 */
export async function fetchAdminStudents(params = {}) {
    const { data } = await axios.get(base + '/students', {
        params: { page: params.page, per_page: params.per_page, search: params.search },
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Create a new student record as Admin.
 *
 * @param {{ first_name: string, last_name: string, student_number: string, [key: string]: any }} payload
 * @returns {Promise<{ message: string, student: object }>}
 */
export async function createAdminStudent(payload) {
    const { data } = await axios.post(base + '/students', payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

/**
 * Update a student record as Admin (no ownership restriction).
 *
 * @param {number} id - Student's database ID.
 * @param {object} payload - Fields to update.
 * @returns {Promise<{ message: string, student: object }>}
 */
export async function updateAdminStudent(id, payload) {
    const { data } = await axios.put(base + '/students/' + id, payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

/**
 * Permanently delete a student record.
 *
 * @param {number} id - Student's database ID.
 * @returns {Promise<{ message: string }>}
 */
export async function deleteStudent(id) {
    const { data } = await axios.delete(base + '/students/' + id, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

/**
 * Export all students (with optional search filter) as a UTF-8 CSV Blob.
 *
 * @param {{ search?: string }} [params={}]
 * @returns {Promise<Blob>} CSV file data.
 */
export async function exportAdminStudents(params = {}) {
    const res = await axios.get(base + '/students/export', {
        params: { search: params.search },
        headers: { ...getAuthHeaders(), Accept: 'text/csv' },
        responseType: 'blob',
    });
    return res.data;
}

// ─── ID Card URL Generators ───────────────────────────────────────────────────

/**
 * Request a time-limited signed URL to download a student ID card PDF.
 *
 * The signed URL expires after a short window (configured server-side).
 *
 * @param {number} id - Student's database ID.
 * @returns {Promise<{ url: string }>}
 */
export async function getAdminStudentIdUrl(id) {
    const { data } = await axios.get(base + '/students/' + id + '/id-url', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}
 HEAD

/**
 * Request a time-limited signed URL to download a teacher ID card PDF.
 *
 * @param {number} id - Teacher's database ID.
 * @returns {Promise<{ url: string }>}
 */
export async function getAdminTeacherIdUrl(id) {
    const { data } = await axios.get(base + '/teachers/' + id + '/id-url', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

a06b4cfd0b9a7b65157ff42ffec21f65f098031d
