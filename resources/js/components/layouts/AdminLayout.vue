<template>
  <div class="h-screen overflow-hidden bg-slate-50 text-slate-900 flex">
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
          <AdminCreateSchoolPage v-else-if="currentPage === 'create-school'" />
        </div>
      </main>
    </div>
    <!-- Profile Edit Modal -->
    <AdminProfileModal v-model="showProfileModal" @profile-updated="onProfileUpdated" />
  </div>
</template>

<script setup>
import { assetPath } from '../../composables/useAsset';
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { fetchUser } from '../../services/authService';
import { useLogout } from '../../composables/useLogout';
import AdminHeader from '../admin/AdminHeader.vue';
import AdminSidebar from '../admin/AdminSidebar.vue';
import AdminDashboardStats from '../admin/AdminDashboardStats.vue';
import AdminTeachersPage from '../admin/AdminTeachersPage.vue';
import AdminStudentsPage from '../admin/AdminStudentsPage.vue';
import ManageSections from '../admin/ManageSections.vue';
import AdminProfileModal from '../admin/AdminProfileModal.vue';
import AdminCreateSchoolPage from '../admin/AdminCreateSchoolPage.vue';

const router = useRouter();
const route = useRoute();
const currentPage = ref('dashboard');
const isSidebarOpen = ref(false);
const logoSrc = assetPath('/logo/depedozamiz.png');
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
  if (currentPage.value === 'create-school') return 'CREATE SCHOOL';
  return 'DASHBOARD';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'teachers') return 'Manage teacher accounts and profiles';
  if (currentPage.value === 'students') return 'Master list and records for students';
  if (currentPage.value === 'sections') return 'Create and manage class sections';
  if (currentPage.value === 'create-school') return 'Register a new school and its admin account';
  return 'Overview of Ozamiz Schools QR-ID System activity';
});

function syncRouteToCurrentPage() {
  if (route.name === 'AdminCreateSchool') {
    currentPage.value = 'create-school';
  }
}

function onSidebarPage(page) {
  currentPage.value = page;
  if (page === 'create-school') {
    router.push({ name: 'AdminCreateSchool' });
    return;
  }
  if (route.name === 'AdminCreateSchool') {
    router.replace({ name: 'Admin' });
  }
}

watch(
  () => route.name,
  () => {
    syncRouteToCurrentPage();
  },
  { immediate: true },
);



onMounted(async () => {
  try {
    const data = await fetchUser();
    user.value = data;
  } catch (_) {}
  syncRouteToCurrentPage();
});
</script>
