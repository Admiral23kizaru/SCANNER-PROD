<template>
  <div class="h-screen overflow-hidden bg-slate-50 text-slate-900 flex">
    <!-- Sidebar -->
    <AdminSidebar 
      v-model:currentPage="currentPage"
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
    <div class="flex-1 flex flex-col bg-slate-50 min-w-0 h-full overflow-hidden">
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
      <main class="flex-1 overflow-auto px-4 py-6 lg:px-10 lg:py-8">
        <div class="max-w-7xl mx-auto space-y-6">
          <AdminDashboardStats v-if="currentPage === 'dashboard'" @navigate="(page) => { currentPage = page; }" />
          <AdminTeachersPage v-else-if="currentPage === 'teachers'" />
          <AdminStudentsPage v-else-if="currentPage === 'students'" />
          <ManageSections v-else-if="currentPage === 'sections'" />
        </div>
      </main>
    </div>
    <!-- Profile Edit Modal -->
    <AdminProfileModal v-model="showProfileModal" @profile-updated="onProfileUpdated" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { fetchUser } from '../../services/authService';
import { useLogout } from '../../composables/useLogout';
import AdminHeader from '../admin/AdminHeader.vue';
import AdminSidebar from '../admin/AdminSidebar.vue';
import AdminDashboardStats from '../admin/AdminDashboardStats.vue';
import AdminTeachersPage from '../admin/AdminTeachersPage.vue';
import AdminStudentsPage from '../admin/AdminStudentsPage.vue';
import ManageSections from '../admin/ManageSections.vue';
import AdminProfileModal from '../admin/AdminProfileModal.vue';

const router = useRouter();
const currentPage = ref('dashboard');
const isSidebarOpen = ref(false);
const logoSrc = '/logo/depedozamiz.png';
const user = ref(null);
const userPhotoError = ref(false);
const isProfileOpen = ref(false);
const showProfileModal = ref(false);

const { logout } = useLogout();

function onProfileUpdated(updatedProfile) {
  if (user.value && updatedProfile) {
    user.value = { ...user.value, ...updatedProfile };
  }
}

const pageTitle = computed(() => {
  if (currentPage.value === 'teachers') return 'TEACHERS';
  if (currentPage.value === 'students') return 'STUDENTS';
  if (currentPage.value === 'sections') return 'SECTIONS';
  return 'DASHBOARD';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'teachers') return 'Manage teacher accounts and profiles';
  if (currentPage.value === 'students') return 'Master list and records for students';
  if (currentPage.value === 'sections') return 'Create and manage class sections';
  return 'Overview of Ozamiz Schools QR-ID System activity';
});



onMounted(async () => {
  try {
    const data = await fetchUser();
    user.value = data;
  } catch (_) {}
});
</script>
