<template>
  <aside
    class="w-72 shrink-0 flex flex-col h-full bg-[#07111f] text-white fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:relative lg:transform-none"
    :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <div class="px-5 py-4 border-b border-white/10">
      <div class="flex items-center gap-3">
        <img
          :src="logoSrc"
          alt="Ozamiz Schools QR-ID System"
          class="h-11 w-11 rounded-xl object-contain bg-white p-1 shadow-sm"
        />
        <div class="leading-tight">
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-200">Project TEA</p>
          <h1 class="text-sm font-semibold tracking-tight text-white">Tracking Engagement and Assessment</h1>
        </div>
      </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1.5 text-sm overflow-visible border-r border-white/10">
      <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Management</p>
      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          isDashboardActive
            ? 'bg-white text-slate-950 shadow-sm'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="toggleDashboard"
      >
        <LayoutDashboard class="h-4 w-4" />
        <span class="flex-1 text-left">Dashboard</span>
        <ChevronDown class="h-4 w-4 transition-transform duration-200" :class="isDashboardOpen ? 'rotate-180' : ''" />
      </button>

      <div v-if="isDashboardOpen" class="ml-6 space-y-1 border-l border-white/10 py-1.5 pl-4">
        <button
          v-for="item in dashboardItems"
          :key="item.key"
          type="button"
          class="group flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm font-medium transition"
          :class="currentPage === item.key ? 'text-white' : 'text-slate-300 hover:text-white'"
          @click="updatePage(item.key)"
        >
          <span
            class="h-2 w-2 rounded-full"
            :class="currentPage === item.key ? 'bg-white' : 'bg-slate-400 group-hover:bg-white'"
          ></span>
          <span>{{ item.label }}</span>
        </button>
      </div>

      <button
        v-for="item in mainItems"
        :key="item.key"
        type="button"
        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === item.key
            ? 'bg-white text-slate-950 shadow-sm'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage(item.key)"
      >
        <component :is="item.icon" class="h-4 w-4" />
        <span class="flex-1 text-left">{{ item.label }}</span>
        <ChevronRight class="h-4 w-4 opacity-70" />
      </button>
    </nav>
    <div class="border-r border-white/10 p-4">
      <div class="rounded-xl border border-white/10 bg-white/5 p-3">
        <p class="text-xs font-semibold text-white">School QR-ID System</p>
        <p class="mt-1 text-xs leading-5 text-slate-400">Tools for records, sections, teachers, and attendance.</p>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref } from 'vue';
import {
  BookOpen,
  CalendarCheck,
  ChevronDown,
  ChevronRight,
  ClipboardList,
  ClipboardPen,
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
  }
});

const emit = defineEmits(['update:currentPage', 'update:isSidebarOpen']);
const isDashboardOpen = ref(true);

const dashboardItems = [
  { key: 'school', label: 'School' },
  { key: 'students', label: 'Learner' },
  { key: 'teachers', label: 'Teacher' },
  { key: 'parents', label: 'Parent' },
  { key: 'least-mastered-skills', label: 'Least Mastered Skills' },
];

const mainItems = [
  { key: 'students', label: 'Learners', icon: GraduationCap },
  { key: 'teachers', label: 'Teachers', icon: Users },
  { key: 'guardians', label: 'Guardian', icon: UserRoundCheck },
  { key: 'sections', label: 'Classes', icon: FolderPlus },
  { key: 'assessment', label: 'Semestral Assessment', icon: ClipboardPen },
  { key: 'attendance', label: 'Attendance', icon: CalendarCheck },
  { key: 'subjects', label: 'Manage Subjects', icon: BookOpen },
  { key: 'learningAssessment', label: 'Learning Assessment', icon: ClipboardList },
];

const isDashboardActive = computed(() => ['dashboard', ...dashboardItems.map((item) => item.key)].includes(props.currentPage));

function toggleDashboard() {
  isDashboardOpen.value = !isDashboardOpen.value;
  updatePage('dashboard');
}

function updatePage(page) {
  emit('update:currentPage', page);
  emit('update:isSidebarOpen', false);
}
</script>
