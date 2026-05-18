<template>
  <div class="min-h-screen bg-[#f4f7fb] text-slate-900">
    <header class="border-b border-slate-800 bg-slate-950 text-white">
      <div class="mx-auto flex max-w-[1600px] flex-col gap-4 px-5 py-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
          <img :src="logoSrc" alt="DepEd Ozamiz" class="h-14 w-14 rounded-full bg-white object-contain p-1" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-blue-200">System Admin</p>
            <h1 class="text-xl font-bold">Division Monitoring Dashboard</h1>
            <p class="text-sm text-slate-300">Read-only view across Ozamiz City schools</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="text-right">
            <p class="text-sm font-semibold">{{ user?.name || 'System Admin' }}</p>
            <p class="text-xs uppercase tracking-wider text-slate-400">{{ user?.email || '' }}</p>
          </div>
          <button
            type="button"
            class="rounded-md border border-blue-500 px-3 py-2 text-sm font-semibold text-blue-100 hover:bg-blue-950"
            @click="downloadExport"
          >
            Export Status
          </button>
          <button
            type="button"
            class="rounded-md border border-slate-600 px-3 py-2 text-sm font-semibold text-slate-100 hover:bg-slate-800"
            @click="logout"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-[1600px] space-y-5 px-5 py-6">
      <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        {{ error }}
      </div>

      <SystemAdminOverviewCards :overview="overview" />

      <div class="grid gap-5 xl:grid-cols-[1fr_420px]">
        <SystemAdminSchoolTable
          :schools="schools"
          :selected-id="selectedSchool?.deped_school_id || ''"
          @select="selectSchool"
          @view-dashboard="openSchoolDashboard"
        />
        <SystemAdminSchoolDetail :school="selectedDetail" />
      </div>
    </main>

    <SystemAdminSchoolDashboardModal
      v-if="showDashboardModal"
      :dashboard="dashboardPreview"
      :loading="dashboardLoading"
      :error="dashboardError"
      @close="showDashboardModal = false"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { assetPath } from '../../composables/useAsset';
import { fetchUser } from '../../services/authService';
import { useLogout } from '../../composables/useLogout';
import {
  exportSystemAdminSchools,
  fetchSystemAdminOverview,
  fetchSystemAdminSchoolDashboard,
  fetchSystemAdminSchoolDetail,
  fetchSystemAdminSchools,
} from '../../services/systemAdminService';
import SystemAdminSchoolDashboardModal from './SystemAdminSchoolDashboardModal.vue';
import SystemAdminOverviewCards from './SystemAdminOverviewCards.vue';
import SystemAdminSchoolDetail from './SystemAdminSchoolDetail.vue';
import SystemAdminSchoolTable from './SystemAdminSchoolTable.vue';

const logoSrc = assetPath('/logo/depedozamiz.png');
const { logout } = useLogout();

const user = ref(null);
const error = ref('');
const schools = ref([]);
const overview = ref({});
const selectedSchool = ref(null);
const selectedDetail = ref(null);
const dashboardPreview = ref(null);
const dashboardError = ref('');
const dashboardLoading = ref(false);
const showDashboardModal = ref(false);

async function loadDashboard() {
  error.value = '';

  try {
    const [currentUser, overviewData, schoolRows] = await Promise.all([
      fetchUser(),
      fetchSystemAdminOverview(),
      fetchSystemAdminSchools(),
    ]);

    user.value = currentUser;
    overview.value = overviewData;
    schools.value = schoolRows;

    if (schoolRows.length > 0) {
      await selectSchool(schoolRows[0]);
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load the System Admin dashboard.';
  }
}

async function selectSchool(school) {
  selectedSchool.value = school;
  selectedDetail.value = {
    ...school,
    stats: {
      students: school.students,
      teachers: school.teachers,
      attendance_today: school.attendance_today,
      late_today: 0,
    },
  };

  try {
    selectedDetail.value = await fetchSystemAdminSchoolDetail(school.deped_school_id);
  } catch (_) {
    // Keep the table row detail visible if the drill-down request fails.
  }
}

async function openSchoolDashboard(school) {
  showDashboardModal.value = true;
  dashboardLoading.value = true;
  dashboardError.value = '';
  dashboardPreview.value = null;

  try {
    dashboardPreview.value = await fetchSystemAdminSchoolDashboard(school.deped_school_id);
  } catch (err) {
    dashboardError.value = err.response?.data?.message || 'Unable to load the selected school dashboard.';
  } finally {
    dashboardLoading.value = false;
  }
}

async function downloadExport() {
  try {
    const blob = await exportSystemAdminSchools();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'scanup-division-school-status.csv';
    link.click();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to export division status.';
  }
}

onMounted(loadDashboard);
</script>
