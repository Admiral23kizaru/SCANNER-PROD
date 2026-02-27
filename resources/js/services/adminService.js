import axios from 'axios';

function getAuthHeaders() {
    const token = localStorage.getItem('scan_up_token');
    return token ? { Authorization: `Bearer ${token}` } : {};
}

const base = '/api/admin';

export async function fetchStats() {
    const { data } = await axios.get(base + '/stats', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

export async function fetchTeachers() {
    const { data } = await axios.get(base + '/teachers', {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

export async function createTeacher(payload) {
    const { data } = await axios.post(base + '/teachers', payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

export async function updateTeacher(id, payload) {
    const { data } = await axios.put(base + '/teachers/' + id, payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

export async function deleteTeacher(id) {
    const { data } = await axios.delete(base + '/teachers/' + id, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

export async function fetchAdminStudents(params = {}) {
    const { data } = await axios.get(base + '/students', {
        params: { page: params.page, per_page: params.per_page, search: params.search },
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}

export async function createAdminStudent(payload) {
    const { data } = await axios.post(base + '/students', payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

export async function updateAdminStudent(id, payload) {
    const { data } = await axios.put(base + '/students/' + id, payload, {
        headers: { ...getAuthHeaders(), 'Content-Type': 'application/json', Accept: 'application/json' },
    });
    return data;
}

export async function deleteStudent(id) {
    const { data } = await axios.delete(base + '/students/' + id, {
        headers: { ...getAuthHeaders(), Accept: 'application/json' },
    });
    return data;
}
