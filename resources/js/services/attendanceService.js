/**
 * @fileoverview attendanceService.js
 * Handles all Axios API calls related to QR scan attendance events.
 *
 * Two layers of API access exist:
 *   - PUBLIC  → No authentication. Used by the Guard Terminal (/guard route).
 *   - PRIVATE → Requires Bearer token. Used by teachers on the teacher dashboard.
 */

import axios from 'axios';

// ─── Shared helpers ──────────────────────────────────────────────────────────

/**
 * Build the Authorization header from the token stored in localStorage.
 * Returns an empty object if the user is not authenticated (public scanner).
 *
 * @returns {{ Authorization?: string }} Axios-compatible header object.
 */
function getAuthHeaders() {
    const token = localStorage.getItem('scan_up_token');
    return token ? { Authorization: `Bearer ${token}` } : {};
}

/**
 * getStoredDepedId
 *
 * PURPOSE: Reads the DepEd school ID from localStorage instead of the URL.
 *
 * WHY localStorage NOT window.location.search:
 * GuardScanner.vue calls history.replaceState() on mount to clean the URL
 * after storing query params, so reading from window.location.search returns null.
 *
 * @returns {string|null} deped_id or null
 */
function getStoredDepedId() {
    return localStorage.getItem('scan_up_deped_id') || null;
}

/** JSON content-type header, shared by all POST/PUT requests. */
const jsonHeaders = { 'Content-Type': 'application/json', Accept: 'application/json' };

// Direct API target for the scanner, allows local scanner to hit remote DB
const API_BASE = import.meta.env.VITE_API_SERVER || '';

// ─── Public scanner (Guard Terminal) ─────────────────────────────────────────

/**
 * Record a QR scan at the Guard Terminal.
 *
 * Called immediately after `html5-qrcode` decodes a barcode.
 * The backend resolves whether the QR contains a student LRN or teacher employee_id,
 * checks for duplicate scans (5-second cooldown + session guard), determines attendance
 * status (on_time / late) using school settings, writes the attendance row, and
 * dispatches an async SMS to the student's guardian via the Semaphore job queue.
 *
 * @param {string} studentId - Raw decoded text from the QR code (LRN or employee_id).
 * @returns {Promise<{
 *   status: string,
 *   message: string,
 *   student: object,
 *   attendance: { id: string|number, status: string, scanned_at: string },
 *   stats: { total_today: number, present_count: number, late_count: number, absent_count: number }
 * }>} Full scan response from the backend.
 */
export async function scanAttendancePublic(data) {
    const sessionName = new Date().getHours() < 12 ? 'morning' : 'dismissal';
    const res = await axios.post(
        `${API_BASE}/api/attendance/scan`,
        { 
            student_id: data.student_id,
            deped_id: data.deped_id,
            school_name: data.school_name,
            session: sessionName
        },
        { 
            headers: jsonHeaders,
            withCredentials: false 
        }
    );
    return res.data;
}

/**
 * Fetch today's 100 most recent attendance records for the public live feed.
 *
 * Used to populate the "Recent Activity" sidebar on the Guard Terminal.
 * Does not require authentication.
 *
 * @returns {Promise<{ data: Array<{
 *   id: number, full_name: string, grade_section: string,
 *   time_in: string, status: string, photo_path: string|null
 * }> }>}
 */
export async function fetchRecentAttendancePublic(depedIdOverride = null) {
    const depedId = depedIdOverride || getStoredDepedId();

    const { data } = await axios.get(`${API_BASE}/api/attendance/public/recent`, {
        params: { deped_id: depedId },
        headers: { Accept: 'application/json' },
        withCredentials: false,
    });
    return data;
}

/**
 * Fetch live attendance stats for the Guard Terminal stat cards.
 *
 * Returns total enrolled, present, late, and absent counts for today.
 * The Guard middleware accepts this endpoint for all authenticated roles.
 *
 * @returns {Promise<{
 *   total_today: number, present_count: number, late_count: number, absent_count: number
 * }>}
 */
export async function fetchGuardStatsPublic(depedIdOverride = null) {
    const depedId = depedIdOverride || getStoredDepedId();

    const { data } = await axios.get(`${API_BASE}/api/attendance/public/stats`, {
        params: { deped_id: depedId },
        headers: { Accept: 'application/json' },
        withCredentials: false,
    });
    return data;
}

/**
 * Send a lightweight scanner heartbeat for the System Admin live monitor.
 */
export async function sendScannerHeartbeat(cameraStatus = 'active', depedIdOverride = null) {
    const depedId = depedIdOverride || getStoredDepedId();
    if (!depedId) return null;

    const { data } = await axios.post(
        `${API_BASE}/api/scanner/heartbeat`,
        {
            deped_id: depedId,
            scanner_key: 'main-terminal',
            camera_status: cameraStatus,
        },
        {
            headers: jsonHeaders,
            withCredentials: false,
        }
    );

    return data;
}

// ─── Teacher-side scanner (authenticated) ─────────────────────────────────────

/**
 * Record a scan from the Teacher Dashboard scanner (requires auth token).
 *
 * Unlike the public scanner, this does not trigger the SMS job and uses
 * a shorter 2-second duplicate cooldown rather than the full session guard.
 *
 * @param {string} studentId - LRN or numeric student ID.
 * @returns {Promise<{ message: string, attendance: object, student: object }>}
 */
export async function scanAttendance(studentId) {
    const { data } = await axios.post(
        '/api/teacher/attendance/scan',
        { student_id: studentId },
        { headers: { ...getAuthHeaders(), ...jsonHeaders } }
    );
    return data;
}

/**
 * Fetch the authenticated teacher's personal attendance log (last 100 entries).
 *
 * @returns {Promise<{ data: Array<{ id: number, full_name: string, grade_section: string, time_in: string }> }>}
 */
export async function fetchRecentAttendance() {
    const { data } = await axios.get('/api/teacher/attendance/recent', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

// ─── Teacher Attendance Monitor (split-view) ─────────────────────────────────

/**
 * Fetch the teacher's student attendance status for today.
 *
 * Action: Implementing Teacher-specific Attendance Monitoring with Split-View UI.
 * Source: AttendanceMonitor.vue polling / manual refresh.
 * Destination: GET /api/teacher/attendance/monitor (authenticated).
 * Function: Returns presentStudents and absentStudents arrays with counts.
 *
 * @returns {Promise<{
 *   status: string,
 *   presentStudents: Array<{ id: number, full_name: string, time_in: string, status: string }>,
 *   absentStudents: Array<{ id: number, full_name: string }>,
 *   presentCount: number,
 *   absentCount: number,
 *   totalStudents: number,
 *   date: string
 * }>}
 */
export async function fetchTeacherMonitor() {
    const { data } = await axios.get('/api/teacher/attendance/monitor', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}
