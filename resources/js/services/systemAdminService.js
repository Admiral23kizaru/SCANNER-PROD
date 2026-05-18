import axios from 'axios';

const base = '/api/system-admin';

function authHeaders() {
  const token = localStorage.getItem('scan_up_token');
  return token ? { Authorization: `Bearer ${token}`, Accept: 'application/json' } : { Accept: 'application/json' };
}

export async function fetchSystemAdminOverview() {
  const { data } = await axios.get(`${base}/overview`, { headers: authHeaders() });
  return data.data || {};
}

export async function fetchSystemAdminSchools() {
  const { data } = await axios.get(`${base}/schools`, { headers: authHeaders() });
  return data.data || [];
}

export async function fetchSystemAdminScannerMonitor() {
  const { data } = await axios.get(`${base}/scanner-monitor`, { headers: authHeaders() });
  return data.data || [];
}

export async function fetchSystemAdminSubjects() {
  const { data } = await axios.get(`${base}/subjects`, { headers: authHeaders() });
  return data.data || [];
}

export async function fetchSystemAdminSchoolDetail(depedSchoolId) {
  const { data } = await axios.get(`${base}/schools/${encodeURIComponent(depedSchoolId)}`, {
    headers: authHeaders(),
  });
  return data.data;
}

export async function fetchSystemAdminSchoolDashboard(depedSchoolId, params = {}) {
  const { data } = await axios.get(`${base}/schools/${encodeURIComponent(depedSchoolId)}/dashboard`, {
    headers: authHeaders(),
    params,
  });
  return data.data;
}

export async function exportSystemAdminSchools() {
  const response = await axios.get(`${base}/schools/export`, {
    headers: authHeaders(),
    responseType: 'blob',
  });

  return response.data;
}
