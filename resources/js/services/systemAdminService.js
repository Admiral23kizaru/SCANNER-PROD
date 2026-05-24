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

export async function fetchSystemAdminLearners() {
  const { data } = await axios.get(`${base}/learners`, { headers: authHeaders() });
  return data.data || [];
}

export async function fetchSystemAdminTeachers() {
  const { data } = await axios.get(`${base}/teachers`, { headers: authHeaders() });
  return data.data || [];
}

export async function fetchSystemAdminSubjects() {
  const { data } = await axios.get(`${base}/subjects`, { headers: authHeaders() });
  return data.data || [];
}

export async function fetchSystemAdminClasses() {
  const { data } = await axios.get(`${base}/classes`, { headers: authHeaders() });
  return data.data || [];
}

export async function fetchSystemAdminAttendance() {
  const { data } = await axios.get(`${base}/attendance`, { headers: authHeaders() });
  return data.data || { summary: [], recent: [] };
}

export async function fetchSystemAdminGuardians() {
  const { data } = await axios.get(`${base}/guardians`, { headers: authHeaders() });
  return data.data || [];
}

export async function fetchSystemAdminAssessmentLogs() {
  const { data } = await axios.get(`${base}/assessment-logs`, { headers: authHeaders() });
  return data.data || [];
}

export async function fetchSystemAdminLeastMasteredSkills(params = {}) {
  const { data } = await axios.get(`${base}/least-mastered-skills`, {
    headers: authHeaders(),
    params,
  });
  return data || { filters: {}, data: [] };
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
