import axios from 'axios';

function getAuthHeaders() {
    const token = localStorage.getItem('scan_up_token');
    return token ? { Authorization: `Bearer ${token}` } : {};
}

const jsonHeaders = { 'Content-Type': 'application/json', Accept: 'application/json' };

/** Public Guard Scanner: no auth. Use on /guard page. */
export async function scanAttendancePublic(studentId) {
    const { data } = await axios.post(
        '/api/attendance/scan',
        { student_id: studentId },
        { headers: jsonHeaders }
    );
    return data;
}

/** Public Guard Scanner: today's attendance list. No auth. */
export async function fetchRecentAttendancePublic() {
    const { data } = await axios.get('/api/attendance/public/recent', {
        headers: { Accept: 'application/json' },
    });
    return data;
}

export async function scanAttendance(studentId) {
    const { data } = await axios.post(
        '/api/attendance/scan',
        { student_id: studentId },
        { headers: { ...getAuthHeaders(), ...jsonHeaders } }
    );
    return data;
}

export async function fetchRecentAttendance() {
    const { data } = await axios.get('/api/attendance/recent', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}
