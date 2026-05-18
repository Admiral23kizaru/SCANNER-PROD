<template>
  <div class="flex h-screen overflow-hidden bg-[#f4f7fb] text-slate-900">
    <SystemAdminSidebar
      :current-page="currentPage"
      :logo-src="logoSrc"
      @update:current-page="currentPage = $event"
    />

    <div class="flex min-w-0 flex-1 flex-col">
      <header class="border-b border-slate-200 bg-white">
        <div class="flex flex-col gap-4 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-600">System Admin</p>
            <h1 class="text-xl font-bold text-slate-950">{{ pageTitle }}</h1>
            <p class="text-sm text-slate-500">{{ pageSubtitle }}</p>
          </div>

          <div class="flex items-center gap-3">
            <div class="text-right">
              <p class="text-sm font-semibold">{{ user?.name || 'System Admin' }}</p>
              <p class="text-xs uppercase tracking-wider text-slate-400">{{ user?.email || '' }}</p>
            </div>
            <button
              type="button"
              class="rounded-md border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50"
              @click="downloadExport"
            >
              Export Status
            </button>
            <button
              type="button"
              class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              @click="logout"
            >
              Logout
            </button>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-auto px-6 py-6">
        <div v-if="error" class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {{ error }}
        </div>

        <section v-if="currentPage === 'dashboard'" class="space-y-5">
          <SystemAdminOverviewCards :overview="overview" />
          <SystemAdminDivisionCharts :schools="schools" />
        </section>

        <section v-else-if="currentPage === 'schools'">
          <SystemAdminSchoolTable
            :schools="schools"
            :selected-id="selectedSchool?.deped_school_id || ''"
            @select="selectSchool"
            @view-dashboard="openSchoolDashboard"
          />
        </section>

        <SystemAdminAccountsDirectory v-else-if="currentPage === 'accounts'" :schools="schools" />

        <SystemAdminScannerMonitor v-else-if="currentPage === 'scanners'" />

        <SystemAdminReports
          v-else-if="currentPage === 'reports'"
          :overview="overview"
          :schools="schools"
          @download="downloadExport"
          @navigate="currentPage = $event"
        />
      </main>
    </div>

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
import { computed, onMounted, ref } from 'vue';
import { assetPath } from '../../composables/useAsset';
import { fetchUser } from '../../services/authService';
import { useLogout } from '../../composables/useLogout';
import {
  exportSystemAdminSchools,
  fetchSystemAdminOverview,
  fetchSystemAdminSchoolDashboard,
  fetchSystemAdminSchools,
} from '../../services/systemAdminService';
import SystemAdminAccountsDirectory from './SystemAdminAccountsDirectory.vue';
import SystemAdminDivisionCharts from './SystemAdminDivisionCharts.vue';
import SystemAdminOverviewCards from './SystemAdminOverviewCards.vue';
import SystemAdminReports from './SystemAdminReports.vue';
import SystemAdminScannerMonitor from './SystemAdminScannerMonitor.vue';
import SystemAdminSchoolDashboardModal from './SystemAdminSchoolDashboardModal.vue';
import SystemAdminSchoolTable from './SystemAdminSchoolTable.vue';
import SystemAdminSidebar from './SystemAdminSidebar.vue';

const logoSrc = assetPath('/logo/depedozamiz.png');
const { logout } = useLogout();

const currentPage = ref('dashboard');
const user = ref(null);
const error = ref('');
const schools = ref([]);
const overview = ref({});
const selectedSchool = ref(null);
const dashboardPreview = ref(null);
const dashboardError = ref('');
const dashboardLoading = ref(false);
const showDashboardModal = ref(false);

const pageTitle = computed(() => {
  if (currentPage.value === 'schools') return 'Schools Monitor';
  if (currentPage.value === 'accounts') return 'School Admins';
  if (currentPage.value === 'scanners') return 'Live Scanners';
  if (currentPage.value === 'reports') return 'Reports';
  return 'Division Dashboard';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'schools') return 'Monitor each school dashboard in read-only mode';
  if (currentPage.value === 'accounts') return 'School head and assigned admin directory';
  if (currentPage.value === 'scanners') return 'Watch live scanner heartbeats and scan activity';
  if (currentPage.value === 'reports') return 'Export division readiness reports';
  return 'Overview of Ozamiz Schools QR-ID activity';
});

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

    selectedSchool.value = schoolRows[0] || null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load the System Admin dashboard.';
  }
}

function selectSchool(school) {
  selectedSchool.value = school;
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
