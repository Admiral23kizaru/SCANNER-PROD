<template>
  <div class="flex h-screen overflow-hidden text-slate-900" :class="themeMode === 'dark' ? 'admin-theme-dark' : 'admin-theme-light'">
    <SystemAdminSidebar
      :current-page="currentPage"
      :logo-src="logoSrc"
      @update:current-page="currentPage = $event"
    />

    <div class="admin-main-surface flex min-w-0 flex-1 flex-col">
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

        <section v-else-if="schoolTablePages.includes(currentPage)">
          <SystemAdminSchoolTable
            :schools="schools"
            :selected-id="selectedSchool?.deped_school_id || ''"
            @select="selectSchool"
            @view-dashboard="openSchoolDashboard"
          />
        </section>

        <SystemAdminAccountsDirectory v-else-if="currentPage === 'accounts'" :schools="schools" />

        <SystemAdminLearningAreas v-else-if="currentPage === 'subjects'" />

        <SystemAdminLearners v-else-if="currentPage === 'learners'" />

        <SystemAdminTeachers v-else-if="currentPage === 'teachers'" />

        <SystemAdminGuardians v-else-if="currentPage === 'parents' || currentPage === 'guardians'" />

        <SystemAdminClasses v-else-if="currentPage === 'classes'" />

        <SystemAdminLeastMasteredSkills v-else-if="currentPage === 'least-mastered-skills'" />

        <SystemAdminAttendance v-else-if="currentPage === 'attendance'" />

        <SystemAdminLearningAssessment
          v-else-if="currentPage === 'semestralAssessment'"
          :schools="schools"
        />

        <section v-else class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
          <p class="text-sm font-semibold text-slate-900">{{ pageTitle }}</p>
          <p class="mt-1 text-sm text-slate-500">{{ pageSubtitle }}</p>
        </section>
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
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
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
import SystemAdminAttendance from './SystemAdminAttendance.vue';
import SystemAdminClasses from './SystemAdminClasses.vue';
import SystemAdminDivisionCharts from './SystemAdminDivisionCharts.vue';
import SystemAdminGuardians from './SystemAdminGuardians.vue';
import SystemAdminLearningAreas from './SystemAdminLearningAreas.vue';
import SystemAdminLearningAssessment from './SystemAdminLearningAssessment.vue';
import SystemAdminLearners from './SystemAdminLearners.vue';
import SystemAdminLeastMasteredSkills from './SystemAdminLeastMasteredSkills.vue';
import SystemAdminTeachers from './SystemAdminTeachers.vue';
import SystemAdminOverviewCards from './SystemAdminOverviewCards.vue';
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
const schoolTablePages = ['school', 'schools'];
const themeMode = ref(resolveTimeTheme());
let themeTimer = null;

const pageTitle = computed(() => {
  if (currentPage.value === 'school' || currentPage.value === 'schools') return 'Schools Monitor';
  if (currentPage.value === 'learners') return 'Learners';
  if (currentPage.value === 'teachers') return 'Teachers';
  if (currentPage.value === 'parents') return 'Parents';
  if (currentPage.value === 'guardians') return 'Guardian';
  if (currentPage.value === 'classes') return 'Classes';
  if (currentPage.value === 'attendance') return 'Attendance';
  if (currentPage.value === 'least-mastered-skills') return 'Least Mastered Skills';
  if (currentPage.value === 'semestralAssessment') return 'Semestral Assessment';
  if (currentPage.value === 'accounts') return 'School Admins';
  if (currentPage.value === 'subjects') return 'Learning Areas';
  return 'Division Dashboard';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'school' || currentPage.value === 'schools') return 'Monitor each school dashboard in read-only mode';
  if (currentPage.value === 'learners') return 'Division-wide learner counts by school';
  if (currentPage.value === 'teachers') return 'Division-wide teacher counts by school';
  if (currentPage.value === 'parents') return 'Parent records will be grouped by school';
  if (currentPage.value === 'guardians') return 'Guardian records will be grouped by school';
  if (currentPage.value === 'classes') return 'Grade and section overview by school';
  if (currentPage.value === 'attendance') return 'Attendance overview across all connected schools';
  if (currentPage.value === 'least-mastered-skills') return 'Pie-chart analysis filtered by school year, subject, grade, and section';
  if (currentPage.value === 'semestralAssessment') return 'Export templates and analyze semestral assessment files for any school';
  if (currentPage.value === 'accounts') return 'School head and assigned admin directory';
  if (currentPage.value === 'subjects') return 'Review learning areas configured by each school';
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

function resolveTimeTheme() {
  const hour = new Date().getHours();
  return hour >= 18 || hour < 6 ? 'dark' : 'light';
}

onMounted(() => {
  themeTimer = window.setInterval(() => {
    themeMode.value = resolveTimeTheme();
  }, 60_000);
  loadDashboard();
});

onBeforeUnmount(() => {
  if (themeTimer) window.clearInterval(themeTimer);
});
</script>
