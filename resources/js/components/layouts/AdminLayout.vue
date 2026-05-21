<template>
  <div class="h-screen overflow-hidden bg-[#f4f7fb] text-slate-900 flex">
    <!-- Sidebar -->
    <AdminSidebar
      :currentPage="currentPage"
      @update:currentPage="onSidebarPage"
      v-model:isSidebarOpen="isSidebarOpen"
      :logoSrc="logoSrc"
    />

    <!-- Mobile sidebar overlay -->
    <div
      v-if="isSidebarOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="isSidebarOpen = false"
    ></div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col bg-[#f4f7fb] min-w-0 h-full overflow-hidden">
      <!-- Top navbar -->
      <AdminHeader
        :user="user"
        :pageTitle="pageTitle"
        :pageSubtitle="pageSubtitle"
        @open-sidebar="isSidebarOpen = true"
        @open-profile-modal="showProfileModal = true"
        @logout="logout"
      />

      <!-- Page content -->
      <main class="flex-1 overflow-auto px-4 py-5 lg:px-8 lg:py-7">
        <div class="max-w-[1500px] mx-auto space-y-6">
          <AdminDashboardStats v-if="currentPage === 'dashboard'" @navigate="(page) => { currentPage = page; }" />
          <AdminTeachersPage v-else-if="currentPage === 'teachers'" />
          <AdminStudentsPage v-else-if="currentPage === 'students'" />
          <AttendanceMonitor v-else-if="currentPage === 'attendance'" api-endpoint="/api/admin/attendance/monitor" />
          <LearningAssessment v-else-if="currentPage === 'learningAssessment'" api-base="/api/admin/learning-assessment" />
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
import { ref, computed, onMounted } from 'vue';
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
import AttendanceMonitor from '../AttendanceMonitor.vue';
import LearningAssessment from '../teacher/LearningAssessment.vue';

const currentPage = ref('dashboard');
const isSidebarOpen = ref(false);
const logoSrc = assetPath('/logo/depedozamiz.png');
const user = ref(null);
const showProfileModal = ref(false);

const { logout } = useLogout();

function onProfileUpdated(updatedProfile) {
  if (user.value && updatedProfile) {
    user.value = { ...user.value, ...updatedProfile };
  }
}

const pageTitle = computed(() => {
  if (currentPage.value === 'teachers') return 'TEACHERS';
  if (currentPage.value === 'students') return 'LEARNERS';
  if (currentPage.value === 'attendance') return 'ATTENDANCE MONITOR';
  if (currentPage.value === 'learningAssessment') return 'LEARNING ASSESSMENT';
  if (currentPage.value === 'subjects') return 'SUBJECTS';
  if (currentPage.value === 'sections') return 'SECTIONS';
  return 'DASHBOARD';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'teachers') return 'Manage teacher accounts and profiles';
  if (currentPage.value === 'students') return 'Master list and records for learners';
  if (currentPage.value === 'attendance') return 'Monitor learner attendance across your school';
  if (currentPage.value === 'learningAssessment') return 'Export templates and analyze learner assessment results';
  if (currentPage.value === 'subjects') return 'Create and manage subjects';
  if (currentPage.value === 'sections') return 'Create and manage class sections';
  return 'Overview of Ozamiz Schools QR-ID System activity';
});

function onSidebarPage(page) {
  currentPage.value = page;
}

onMounted(async () => {
  try {
    const data = await fetchUser();
    user.value = data;
  } catch (_) {}
});
</script>
