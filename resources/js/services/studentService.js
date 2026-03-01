import axios from 'axios';

function getAuthHeaders() {
    const token = localStorage.getItem('scan_up_token');
    return token ? { Authorization: `Bearer ${token}` } : {};
}

const base = '/api/teacher';
const apiBase = '/api';

export async function fetchStudents(params = {}) {
    const { data } = await axios.get(base + '/students', {
        params: { page: params.page, per_page: params.per_page, search: params.search },
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

export async function createStudent(payload) {
    const { data } = await axios.post(base + '/students', payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

export async function createStudentWithFormData(formData) {
    const { data } = await axios.post(base + '/students', formData, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

export async function updateStudent(id, payload) {
    const { data } = await axios.put(base + '/students/' + id, payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

export async function updateStudentWithFormData(id, formData) {
    // Laravel doesn't support multipart/form-data via PUT.
    // Use POST with _method=PUT (method spoofing) so file uploads work.
    formData.append('_method', 'PUT');
    const { data } = await axios.post(base + '/students/' + id, formData, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

export async function uploadStudentPhoto(id, file) {
    const formData = new FormData();
    formData.append('photo', file);
    await axios.post(base + '/students/' + id + '/photo', formData, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
}

export function getIdPdfUrl(id) {
    const token = localStorage.getItem('scan_up_token');
    return `${apiBase}/teacher/students/${encodeURIComponent(id)}/id${token ? '?token=' + encodeURIComponent(token) : ''}`;
}

export function openIdPdfInBrowser(id) {
    window.open(getIdPdfUrl(id), '_blank', 'noopener,noreferrer');
}

export async function bulkImportStudents(file) {
    const formData = new FormData();
    formData.append('file', file);
    const { data } = await axios.post(base + '/students/import', formData, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
        timeout: 120000,
    });
    return data;
}
