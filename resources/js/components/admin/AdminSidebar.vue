<template>
  <aside
    class="w-72 shrink-0 flex flex-col h-full bg-[#1f2937] text-white fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:relative lg:transform-none"
    :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <div class="px-5 py-4 border-b border-white/10 bg-[#243044]">
      <div class="flex items-center gap-3">
        <img
          :src="logoSrc"
          alt="Ozamiz Schools QR-ID System"
          class="h-11 w-11 rounded-xl object-contain bg-white p-1 shadow-sm"
        />
        <div class="leading-tight">
          <p class="text-xs font-semibold text-white">Project</p>
          <h1 class="text-sm font-semibold tracking-tight text-teal-100">ScanUp Admin</h1>
        </div>
      </div>
    </div>

    <div class="px-3 pt-4">
      <div class="flex items-center gap-3 rounded-lg bg-[#172033] px-3 py-3">
        <div class="h-10 w-10 overflow-hidden rounded-full border border-white/20 bg-teal-600">
          <img
            v-if="user?.profile_photo && !userPhotoError"
            :src="getPhotoUrl(user.profile_photo)"
            class="h-full w-full object-cover"
            @error="userPhotoError = true"
          />
          <div v-else class="flex h-full w-full items-center justify-center text-sm font-bold text-white">
            {{ userInitial }}
          </div>
        </div>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-white">{{ user?.name || 'Admin User' }}</p>
          <p class="text-xs text-slate-400">{{ user?.role?.name || 'Admin' }}</p>
        </div>
        <ChevronRight class="h-4 w-4 text-slate-400" />
      </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1.5 text-sm overflow-y-auto border-r border-white/10">
      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'dashboard'
            ? 'bg-teal-500 text-white shadow-lg shadow-teal-950/20'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="isDashboardOpen = !isDashboardOpen; updatePage('dashboard')"
      >
        <LayoutDashboard class="h-4 w-4" />
        <span class="flex-1 text-left">Dashboard</span>
        <ChevronDown class="h-4 w-4 transition-transform" :class="isDashboardOpen ? 'rotate-180' : ''" />
      </button>

      <div v-if="isDashboardOpen" class="ml-5 space-y-1 border-l border-white/10 py-1 pl-3">
        <button
          type="button"
          class="block w-full rounded-md px-2 py-1.5 text-left text-xs font-medium transition"
          :class="currentPage === 'dashboard' ? 'text-teal-300' : 'text-slate-400 hover:text-white'"
          @click="updatePage('dashboard')"
        >
          Overview
        </button>
        <button
          v-for="item in dashboardChildren"
          :key="item.key"
          type="button"
          class="block w-full rounded-md px-2 py-1.5 text-left text-xs font-medium transition"
          :class="currentPage === item.key ? 'text-teal-300' : 'text-slate-400 hover:text-white'"
          @click="updatePage(item.key)"
        >
          {{ item.label }}
        </button>
      </div>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'teachers'
            ? 'bg-teal-500 text-white shadow-lg shadow-teal-950/20'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('teachers')"
      >
        <Users class="h-4 w-4" />
        <span class="flex-1 text-left">Teachers</span>
        <ChevronRight class="h-4 w-4 text-slate-500" />
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'students'
            ? 'bg-teal-500 text-white shadow-lg shadow-teal-950/20'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('students')"
      >
        <GraduationCap class="h-4 w-4" />
        <span class="flex-1 text-left">Learners</span>
        <ChevronRight class="h-4 w-4 text-slate-500" />
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'subjects'
            ? 'bg-teal-500 text-white shadow-lg shadow-teal-950/20'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('subjects')"
      >
        <BookOpen class="h-4 w-4" />
        <span class="flex-1 text-left">Manage Subjects</span>
        <ChevronRight class="h-4 w-4 text-slate-500" />
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'sections'
            ? 'bg-teal-500 text-white shadow-lg shadow-teal-950/20'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('sections')"
      >
        <FolderPlus class="h-4 w-4" />
        <span class="flex-1 text-left">Classes</span>
        <ChevronRight class="h-4 w-4 text-slate-500" />
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'guardians'
            ? 'bg-teal-500 text-white shadow-lg shadow-teal-950/20'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('guardians')"
      >
        <UserRoundCheck class="h-4 w-4" />
        <span class="flex-1 text-left">Guardian</span>
        <ChevronRight class="h-4 w-4 text-slate-500" />
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'assessment'
            ? 'bg-teal-500 text-white shadow-lg shadow-teal-950/20'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('assessment')"
      >
        <ClipboardPenLine class="h-4 w-4" />
        <span class="flex-1 text-left">Semestral Assessment</span>
        <ChevronRight class="h-4 w-4 text-slate-500" />
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'attendance'
            ? 'bg-teal-500 text-white shadow-lg shadow-teal-950/20'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('attendance')"
      >
        <CalendarCheck class="h-4 w-4" />
        <span class="flex-1 text-left">Attendance</span>
        <ChevronRight class="h-4 w-4 text-slate-500" />
      </button>
    </nav>
    <div class="border-r border-white/10 p-4">
      <div class="rounded-lg border border-white/10 bg-[#172033] p-3">
        <p class="text-xs font-semibold text-white">School QR-ID System</p>
        <p class="mt-1 text-xs leading-5 text-slate-400">Tools for records, sections, teachers, and attendance.</p>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref } from 'vue';
import { assetPath } from '../../composables/useAsset';
import {
  BookOpen,
  CalendarCheck,
  ChevronDown,
  ChevronRight,
  ClipboardPenLine,
  FolderPlus,
  GraduationCap,
  LayoutDashboard,
  UserRoundCheck,
  Users,
} from 'lucide-vue-next';

const props = defineProps({
  currentPage: {
    type: String,
    required: true
  },
  isSidebarOpen: {
    type: Boolean,
    required: true
  },
  logoSrc: {
    type: String,
    required: true
  },
  user: {
    type: Object,
    default: null
  },
  themeMode: {
    type: String,
    default: 'light'
  }
});

const emit = defineEmits(['update:currentPage', 'update:isSidebarOpen']);
const isDashboardOpen = ref(true);
const userPhotoError = ref(false);
const dashboardChildren = [
  { key: 'school', label: 'School' },
  { key: 'students', label: 'Learner' },
  { key: 'teachers', label: 'Teacher' },
  { key: 'parents', label: 'Parent' },
  { key: 'least-mastered-skills', label: 'Least Mastered Skills' },
];

const userInitial = computed(() => props.user?.name?.charAt(0)?.toUpperCase() || 'A');

function getPhotoUrl(path) {
  if (!path) return assetPath('/images/default-avatar.png');
  if (/^https?:\/\//i.test(path)) return path;
  const cleanPath = path.replace(/^(public\/|storage\/|\/storage\/|\/public\/)/, '').replace(/^\//, '');
  return assetPath('/storage/' + cleanPath);
}

function updatePage(page) {
  emit('update:currentPage', page);
  emit('update:isSidebarOpen', false);
}
</script>
