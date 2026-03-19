<template>
  <div class="h-screen overflow-hidden bg-slate-50 text-slate-900 flex">
    <!-- Sidebar -->
    <aside
      class="w-64 shrink-0 flex flex-col h-full bg-white fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:relative lg:transform-none"
      :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <!-- Brand -->
      <div class="px-6 py-3 border-b border-r border-slate-700" style="background-color: #050517;">
        <div class="flex items-center gap-3">
          <img
            :src="logoSrc"
            alt="Ozamiz Schools QR-ID System"
            class="h-10 w-auto rounded-full object-contain bg-white"
          />
          <div class="leading-tight">
            <h1 class="text-sm font-semibold tracking-tight text-white">
              
            </h1>
            <p class="text-[17px] text-white uppercase tracking-[0.18em]">
              Admin Panel
            </p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto border-r border-slate-200">
        <button
          type="button"
          class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors border-l-2 border-transparent cursor-pointer"
          :class="
            currentPage === 'dashboard'
              ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm border-l-blue-600'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          @click="currentPage = 'dashboard'; isSidebarOpen = false"
        >
          <LayoutDashboard class="h-4 w-4" />
          <span>Dashboard</span>
        </button>

        <button
          type="button"
          class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors border-l-2 border-transparent cursor-pointer"
          :class="
            currentPage === 'teachers'
              ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm border-l-blue-600'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          @click="currentPage = 'teachers'; isSidebarOpen = false"
        >
          <Users class="h-4 w-4" />
          <span>Manage Teachers</span>
        </button>

        <button
          type="button"
          class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors border-l-2 border-transparent cursor-pointer"
          :class="
            currentPage === 'students'
              ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm border-l-blue-600'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          @click="currentPage = 'students'; isSidebarOpen = false"
        >
          <GraduationCap class="h-4 w-4" />
          <span>Students</span>
        </button>
      </nav>

      <!-- Sidebar Footer: Logout -->
    </aside>

    <!-- Mobile sidebar overlay -->
    <div
      v-if="isSidebarOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="isSidebarOpen = false"
    ></div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col bg-slate-50 min-w-0 h-full overflow-hidden">
      <!-- Top navbar -->
      <header class="sticky top-0 z-10" style="background-color: #050517;">
        <div class="h-16 flex items-center justify-between px-4 lg:px-10 border-b border-slate-700/80">
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="lg:hidden p-2 text-white hover:bg-white/10 rounded-lg transition"
              @click="isSidebarOpen = true"
            >
              <Menu class="h-5 w-5" />
            </button>
            <div>
              <p class="text-xs font-medium tracking-[0.25em] text-slate-400 uppercase">
                {{ pageTitle }}
              </p>
              <p class="text-sm font-semibold text-white">
                {{ pageSubtitle }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <!-- Profile dropdown -->
            <div v-if="user" class="relative">
              <!-- Trigger button -->
              <button
                type="button"
                class="hidden sm:flex items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-white/10 transition-colors cursor-pointer"
                @click.stop="isProfileOpen = !isProfileOpen"
              >
                <div class="text-right">
                  <p class="text-xs font-medium text-white">{{ user.name }}</p>
                  <p class="text-[10px] text-slate-400 uppercase tracking-wider">{{ user.role?.name || 'Admin' }}</p>
                </div>
                <div class="w-9 h-9 rounded-full overflow-hidden border border-white/20 bg-slate-800 shrink-0">
                  <img
                    v-if="user.profile_photo && !userPhotoError"
                    :src="getPhotoUrl(user.profile_photo)"
                    class="w-full h-full object-cover"
                    @error="userPhotoError = true"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center bg-blue-600 text-white text-xs font-bold">
                    <img v-if="userPhotoError" :src="'/images/default-avatar.png'" class="w-full h-full object-cover" />
                    <span v-else>{{ user.name?.charAt(0) }}</span>
                  </div>
                </div>
                <ChevronDown
                  class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                  :class="isProfileOpen ? 'rotate-180' : ''"
                />
              </button>

              <!-- Dropdown panel -->
              <transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 scale-95 -translate-y-1"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 -translate-y-1"
              >
                <div
                  v-if="isProfileOpen"
                  class="absolute right-0 mt-2 w-52 rounded-xl bg-white border border-slate-200 shadow-xl z-50 overflow-hidden"
                >
                  <!-- User info header -->
                  <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">
                    <p class="text-xs font-semibold text-slate-800 truncate">{{ user.name }}</p>
                    <p class="text-[10px] text-slate-500 truncate">{{ user.email }}</p>
                  </div>

                  <!-- Menu items -->
                  <div class="py-1">
                    <button
                      type="button"
                      class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                      @click="isProfileOpen = false; showProfileModal = true"
                    >
                      <UserCircle class="h-4 w-4" />
                      <span>My Profile</span>
                    </button>
                  </div>

                  <div class="border-t border-slate-100 py-1">
                    <button
                      type="button"
                      class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                      @click="isProfileOpen = false; logout()"
                    >
                      <LogOut class="h-4 w-4" />
                      <span>Log out</span>
                    </button>
                  </div>
                </div>
              </transition>

              <!-- Click-outside overlay -->
              <div
                v-if="isProfileOpen"
                class="fixed inset-0 z-40"
                @click.stop="isProfileOpen = false"
              />
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-auto px-4 py-6 lg:px-10 lg:py-8">
        <div class="max-w-7xl mx-auto space-y-6">
          <AdminDashboardStats v-if="currentPage === 'dashboard'" @navigate="(page) => { currentPage = page; }" />
          <AdminTeachersPage v-else-if="currentPage === 'teachers'" />
          <AdminStudentsPage v-else-if="currentPage === 'students'" />
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
import { LayoutDashboard, Users, GraduationCap, LogOut, Menu, ChevronDown, UserCircle, Settings } from 'lucide-vue-next';
import { fetchUser } from '../../services/authService';
import { useLogout } from '../../composables/useLogout';
import AdminDashboardStats from './AdminDashboardStats.vue';
import AdminTeachersPage from './AdminTeachersPage.vue';
import AdminStudentsPage from './AdminStudentsPage.vue';
import AdminProfileModal from '../AdminProfileModal.vue';

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


function getPhotoUrl(path) {
  if (!path) return '/images/default-avatar.png';
  // Target Role: Admin
  // Source: Storage/Database
  // Destination: Profile Header
  // Function: Identifies if path is already absolute URL, or formats local storage paths.
  if (/^https?:\/\//i.test(path)) return path;
  const cleanPath = path.replace(/^(public\/|storage\/|\/storage\/|\/public\/)/, '').replace(/^\//, '');
  return '/storage/' + cleanPath;
}

const pageTitle = computed(() => {
  if (currentPage.value === 'teachers') return 'TEACHERS';
  if (currentPage.value === 'students') return 'STUDENTS';
  return 'DASHBOARD';
});

const pageSubtitle = computed(() => {
  if (currentPage.value === 'teachers') return 'Manage teacher accounts and profiles';
  if (currentPage.value === 'students') return 'Master list and records for students';
  return 'Overview of Ozamiz Schools QR-ID System activity';
});



onMounted(async () => {
  try {
    const data = await fetchUser();
    user.value = data;
  } catch (_) {}
});
</script>
