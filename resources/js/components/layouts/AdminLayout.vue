<template>
  <div class="h-screen overflow-hidden flex" :class="themeMode === 'dark' ? 'admin-theme-dark' : 'admin-theme-light'">
    <!-- Sidebar -->
    <AdminSidebar
      :currentPage="currentPage"
      @update:currentPage="onSidebarPage"
      v-model:isSidebarOpen="isSidebarOpen"
      :logoSrc="logoSrc"
      :user="user"
    />

    <!-- Mobile sidebar overlay -->
    <div
      v-if="isSidebarOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="isSidebarOpen = false"
    ></div>

    <!-- Main content -->
    <div class="admin-main-surface flex-1 flex flex-col min-w-0 h-full overflow-hidden">
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
          <AdminLearnerAnalytics v-else-if="currentPage === 'learner-analytics'" />
          <AdminTeacherAnalytics v-else-if="currentPage === 'teacher-analytics'" />
          <AdminTeachersPage v-else-if="currentPage === 'teachers'" />
          <AdminStudentsPage v-else-if="currentPage === 'students'" />
          <AdminGuardiansPage v-else-if="currentPage === 'guardians' || currentPage === 'parents'" />
          <AttendanceMonitor v-else-if="currentPage === 'attendance'" api-endpoint="/api/admin/attendance/monitor" />
          <LearningAssessment v-else-if="currentPage === 'semestralAssessment'" api-base="/api/admin/learning-assessment" />
          <AdminLeastMasteredSkillsPage v-else-if="currentPage === 'least-mastered-skills'" />
          <ManageSubjects v-else-if="currentPage === 'subjects'" />
          <ManageSections v-else-if="currentPage === 'sections'" />
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
import AdminLearnerAnalytics from '../admin/AdminLearnerAnalytics.vue';
import AdminTeacherAnalytics from '../admin/AdminTeacherAnalytics.vue';
import AdminTeachersPage from '../admin/AdminTeachersPage.vue';
import AdminStudentsPage from '../admin/AdminStudentsPage.vue';
import ManageSubjects from '../admin/ManageSubjects.vue';
import ManageSections from '../admin/ManageSections.vue';
import AdminProfileModal from '../admin/AdminProfileModal.vue';
import AttendanceMonitor from '../AttendanceMonitor.vue';
import LearningAssessment from '../teacher/LearningAssessment.vue';
import AdminSchoolPage from '../admin/AdminSchoolPage.vue';
import AdminGuardiansPage from '../admin/AdminGuardiansPage.vue';
import AdminLeastMasteredSkillsPage from '../admin/AdminLeastMasteredSkillsPage.vue';

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
  if (currentPage.value === 'learner-analytics') return 'LEARNER ANALYTICS';
  if (currentPage.value === 'teacher-analytics') return 'TEACHER ANALYTICS';
  if (currentPage.value === 'school') return 'SCHOOL';
  if (currentPage.value === 'parents') return 'PARENTS';
  if (currentPage.value === 'guardians') return 'GUARDIANS';
  if (currentPage.value === 'least-mastered-skills') return 'LEAST MASTERED SKILLS';
  if (currentPage.value === 'attendance') return 'ATTENDANCE MONITOR';
  if (currentPage.value === 'semestralAssessment') return 'SEMESTRAL ASSESSMENT';
  if (currentPage.value === 'subjects') return 'SUBJECTS';
  if (currentPage.value === 'sections') return 'SECTIONS';
  return 'Project TEA - Tracking Engagement and Assessment';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'teachers') return 'Manage teacher accounts and profiles';
  if (currentPage.value === 'students') return 'Master list and records for learners';
  if (currentPage.value === 'learner-analytics') return 'Pie-chart analysis of learners in your school';
  if (currentPage.value === 'teacher-analytics') return 'Pie-chart analysis of teachers in your school';
  if (currentPage.value === 'school') return 'Grade and section count with adviser assignment';
  if (currentPage.value === 'parents') return 'Parent contact records';
  if (currentPage.value === 'guardians') return 'Guardian contact records';
  if (currentPage.value === 'least-mastered-skills') return 'Assessment pie chart results by filters';
  if (currentPage.value === 'attendance') return 'Monitor learner attendance across your school';
  if (currentPage.value === 'semestralAssessment') return 'Export templates and analyze semestral assessment results';
  if (currentPage.value === 'subjects') return 'Create and manage subjects';
  if (currentPage.value === 'sections') return 'Create and manage class sections';
  return 'Dashboard overview for engagement, attendance, and assessment activity';
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
