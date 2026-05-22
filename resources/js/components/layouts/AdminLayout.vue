<template>
  <div class="h-screen overflow-hidden text-slate-900 flex" :class="themeMode === 'dark' ? 'bg-slate-950' : 'bg-[#f4f7fb]'">
    <!-- Sidebar -->
    <AdminSidebar
      :currentPage="currentPage"
      @update:currentPage="onSidebarPage"
      v-model:isSidebarOpen="isSidebarOpen"
      :logoSrc="logoSrc"
      :user="user"
      :themeMode="themeMode"
    />

    <!-- Mobile sidebar overlay -->
    <div
      v-if="isSidebarOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="isSidebarOpen = false"
    ></div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden" :class="themeMode === 'dark' ? 'bg-slate-900' : 'bg-[#f4f7fb]'">
      <!-- Top navbar -->
      <AdminHeader
        :user="user"
        :pageTitle="pageTitle"
        :pageSubtitle="pageSubtitle"
        @open-sidebar="isSidebarOpen = true"
        @open-profile-modal="showProfileModal = true"
        @logout="logout"
        :themeMode="themeMode"
      />

      <!-- Page content -->
      <main class="flex-1 overflow-auto px-4 py-5 lg:px-8 lg:py-7">
        <div class="max-w-[1500px] mx-auto space-y-6">
          <AdminDashboardStats v-if="currentPage === 'dashboard'" @navigate="(page) => { currentPage = page; }" />
          <AdminSchoolPage v-else-if="currentPage === 'school'" />
          <AdminTeachersPage v-else-if="currentPage === 'teachers'" />
          <AdminStudentsPage v-else-if="currentPage === 'students'" />
          <AdminGuardiansPage v-else-if="currentPage === 'guardians' || currentPage === 'parents'" />
          <ManageSubjects v-else-if="currentPage === 'subjects'" />
          <ManageSections v-else-if="currentPage === 'sections'" />
          <AdminLeastMasteredSkillsPage v-else-if="currentPage === 'least-mastered-skills'" />
          <AdminAssessmentLogsPage v-else-if="currentPage === 'assessment'" />
          <AdminAttendancePage v-else-if="currentPage === 'attendance'" />
        </div>
      </main>
    </div>
    <!-- Profile Edit Modal -->
    <AdminProfileModal v-model="showProfileModal" @profile-updated="onProfileUpdated" />
  </div>
</template>

<script setup>
import { assetPath } from '../../composables/useAsset';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { fetchUser } from '../../services/authService';
import { useLogout } from '../../composables/useLogout';
import AdminHeader from '../admin/AdminHeader.vue';
import AdminSidebar from '../admin/AdminSidebar.vue';
import AdminDashboardStats from '../admin/AdminDashboardStats.vue';
import AdminTeachersPage from '../admin/AdminTeachersPage.vue';
import AdminStudentsPage from '../admin/AdminStudentsPage.vue';
import ManageSubjects from '../admin/ManageSubjects.vue';
import ManageSections from '../admin/ManageSections.vue';
import AdminProfileModal from '../admin/AdminProfileModal.vue';
import AdminSchoolPage from '../admin/AdminSchoolPage.vue';
import AdminGuardiansPage from '../admin/AdminGuardiansPage.vue';
import AdminLeastMasteredSkillsPage from '../admin/AdminLeastMasteredSkillsPage.vue';
import AdminAssessmentLogsPage from '../admin/AdminAssessmentLogsPage.vue';
import AdminAttendancePage from '../admin/AdminAttendancePage.vue';

const currentPage = ref('dashboard');
const isSidebarOpen = ref(false);
const logoSrc = assetPath('/logo/depedozamiz.png');
const user = ref(null);
const showProfileModal = ref(false);
const themeMode = ref(resolveTimeTheme());
let themeTimer = null;

const { logout } = useLogout();

function onProfileUpdated(updatedProfile) {
  if (user.value && updatedProfile) {
    user.value = { ...user.value, ...updatedProfile };
  }
}

const pageTitle = computed(() => {
  if (currentPage.value === 'teachers') return 'TEACHERS';
  if (currentPage.value === 'students') return 'LEARNERS';
  if (currentPage.value === 'school') return 'SCHOOL';
  if (currentPage.value === 'guardians') return 'GUARDIANS';
  if (currentPage.value === 'parents') return 'PARENTS';
  if (currentPage.value === 'least-mastered-skills') return 'LEAST MASTERED SKILLS';
  if (currentPage.value === 'assessment') return 'SEMESTRAL ASSESSMENT';
  if (currentPage.value === 'attendance') return 'ATTENDANCE';
  if (currentPage.value === 'subjects') return 'SUBJECTS';
  if (currentPage.value === 'sections') return 'SECTIONS';
  return 'DASHBOARD';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'teachers') return 'Manage teacher accounts and profiles';
  if (currentPage.value === 'students') return 'Master list and records for learners';
  if (currentPage.value === 'school') return 'Grade and section count with adviser assignment';
  if (currentPage.value === 'guardians') return 'Manage guardian contact records';
  if (currentPage.value === 'parents') return 'Manage parent contact records';
  if (currentPage.value === 'least-mastered-skills') return 'Assessment pie chart results by filters';
  if (currentPage.value === 'assessment') return 'View and update assessment result logs';
  if (currentPage.value === 'attendance') return "View today's attendance scans";
  if (currentPage.value === 'subjects') return 'Create and manage subjects';
  if (currentPage.value === 'sections') return 'Create and manage class sections';
  return 'Overview of Ozamiz Schools QR-ID System activity';
});

function onSidebarPage(page) {
  currentPage.value = page;
}

function resolveTimeTheme() {
  const hour = new Date().getHours();
  return hour >= 18 || hour < 6 ? 'dark' : 'light';
}

onMounted(async () => {
  themeTimer = window.setInterval(() => {
    themeMode.value = resolveTimeTheme();
  }, 60_000);
  try {
    const data = await fetchUser();
    user.value = data;
  } catch (_) {}
});

onBeforeUnmount(() => {
  if (themeTimer) window.clearInterval(themeTimer);
});
</script>
