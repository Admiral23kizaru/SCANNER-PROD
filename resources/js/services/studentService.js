/**
 * @fileoverview studentService.js
 * Handles all Axios API calls for student CRUD, photo uploads, and bulk import.
 *
 * These endpoints are scoped to the Teacher role.
 * Teachers may only access students they own (teacher_id) or created (created_by).
 *
 * Base API prefix: /api/teacher
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

const base    = '/api/teacher';
const apiBase = '/api';

// ─── Listing ──────────────────────────────────────────────────────────────────

/**
 * Fetch a paginated list of students belonging to the current teacher.
 *
 * @param {{ page?: number, per_page?: number, search?: string }} [params={}]
 * @returns {Promise<{
 *   data: Array<{ id: number, student_number: string, full_name: string, grade_section: string, photo_path: string|null }>,
 *   current_page: number, last_page: number, total: number
 * }>}
 */
export async function fetchStudents(params = {}) {
    const { data } = await axios.get(base + '/students', {
        params: { page: params.page, per_page: params.per_page, search: params.search },
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

// ─── Create ───────────────────────────────────────────────────────────────────

/**
 * Create a new student with a plain JSON payload (no photo).
 *
 * @param {{ first_name: string, last_name: string, student_number: string, [key: string]: any }} payload
 * @returns {Promise<{ message: string, student: object }>}
 */
export async function createStudent(payload) {
    const { data } = await axios.post(base + '/students', payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

/**
 * Create a new student using multipart/form-data (includes photo upload).
 *
 * Use this variant when the create form includes a file upload.
 *
 * @param {FormData} formData - Must include 'first_name', 'last_name', 'student_number', and optionally 'photo'.
 * @returns {Promise<{ message: string, student: object }>}
 */
export async function createStudentWithFormData(formData) {
    const { data } = await axios.post(base + '/students', formData, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

// ─── Update ───────────────────────────────────────────────────────────────────

/**
 * Update a student record with a plain JSON payload (no file).
 *
 * @param {number} id - Student's database ID.
 * @param {object} payload - Fields to update.
 * @returns {Promise<{ message: string, student: object }>}
 */
export async function updateStudent(id, payload) {
    const { data } = await axios.put(base + '/students/' + id, payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

/**
 * Update a student record using multipart/form-data (includes photo upload).
 *
 * Laravel does not support multipart/form-data via HTTP PUT natively.
 * This function appends `_method=PUT` to the FormData so Laravel's method
 * spoofing converts it to a PUT request server-side.
 *
 * @param {number} id - Student's database ID.
 * @param {FormData} formData - Must include `_method: PUT` (appended automatically).
 * @returns {Promise<{ message: string, student: object }>}
 */
export async function updateStudentWithFormData(id, formData) {
    // Append Laravel method spoofing so the route matches PUT but allows file upload
    formData.append('_method', 'PUT');
    const { data } = await axios.post(base + '/students/' + id, formData, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

// ─── Photo ────────────────────────────────────────────────────────────────────

/**
 * Upload or replace a student's profile photo.
 *
 * @param {number} id - Student's database ID.
 * @param {File} file - Image file (jpg/jpeg/png, max 5 MB).
 * @returns {Promise<void>}
 */
export async function uploadStudentPhoto(id, file) {
    const formData = new FormData();
    formData.append('photo', file);
    await axios.post(base + '/students/' + id + '/photo', formData, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
}

// ─── ID Card ──────────────────────────────────────────────────────────────────

/**
 * Build the URL for the student ID card PDF (TCPDF-generated, inline).
 *
 * Note: The token is passed as a query param because the PDF is rendered server-side
 * and cannot use an Authorization header in a browser tab.
 *
 * @param {number} id - Student's database ID.
 * @returns {string} Fully-formed URL to the PDF endpoint.
 */
export function getIdPdfUrl(id) {
    const token = localStorage.getItem('scan_up_token');
    return `${apiBase}/teacher/students/${encodeURIComponent(id)}/id${token ? '?token=' + encodeURIComponent(token) : ''}`;
}

/**
 * Open the student ID card PDF in a new browser tab.
 *
 * @param {number} id - Student's database ID.
 * @returns {void}
 */
export function openIdPdfInBrowser(id) {
    window.open(getIdPdfUrl(id), '_blank', 'noopener,noreferrer');
}

// ─── Bulk Import ──────────────────────────────────────────────────────────────

/**
 * Bulk-import students from an Excel or CSV spreadsheet.
 *
 * The file is parsed server-side using PhpSpreadsheet.
 * Rows with invalid/duplicate LRNs or missing names are skipped.
 * The request timeout is extended to 120 seconds to handle large files.
 *
 * @param {File} file - Excel (.xlsx/.xls) or CSV file. Max 10 MB.
 * @returns {Promise<{ message: string, imported: number, skipped: number }>}
 */
export async function bulkImportStudents(file) {
    const formData = new FormData();
    formData.append('file', file);
    const { data } = await axios.post(base + '/students/import', formData, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
        timeout: 120_000, // 2-minute timeout for large spreadsheets
    });
    return data;
}
