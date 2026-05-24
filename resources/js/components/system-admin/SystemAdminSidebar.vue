<template>
  <aside class="flex h-full w-72 flex-col border-r border-slate-800 bg-slate-950 text-white">
    <div class="flex items-center gap-3 border-b border-slate-800 p-5">
      <img :src="logoSrc" alt="DepEd Ozamiz" class="h-12 w-12 rounded-full bg-white object-contain p-1" />
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-300">Project TEA</p>
        <h1 class="text-sm font-bold leading-tight">Tracking Engagement and Assessment</h1>
      </div>
    </div>

    <nav class="flex-1 space-y-1.5 p-4">
      <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Management</p>
      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
        :class="isDashboardActive ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        @click="toggleDashboard"
      >
        <LayoutDashboard class="h-4 w-4" />
        <span class="flex-1 text-left">Dashboard</span>
        <ChevronDown class="h-4 w-4 transition-transform" :class="isDashboardOpen ? 'rotate-180' : ''" />
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
        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
        :class="currentPage === item.key ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        @click="updatePage(item.key)"
      >
        <component :is="item.icon" class="h-4 w-4" />
        <span class="flex-1 text-left">{{ item.label }}</span>
        <ChevronRight class="h-4 w-4 opacity-70" />
      </button>
    </nav>

    <div class="border-t border-slate-800 p-4 text-xs text-slate-400">
      Division control center for all DepEd Ozamiz schools.
    </div>
  </aside>
</template>

<script setup>
import { computed, ref } from 'vue';
import {
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
    required: true,
  },
  logoSrc: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['update:currentPage']);
const isDashboardOpen = ref(true);

const dashboardItems = [
  { key: 'school', label: 'School' },
  { key: 'learners', label: 'Learner' },
  { key: 'teachers', label: 'Teacher' },
  { key: 'parents', label: 'Parent' },
  { key: 'least-mastered-skills', label: 'Least Mastered Skills' },
];

const mainItems = [
  { key: 'learners', label: 'Learners', icon: GraduationCap },
  { key: 'teachers', label: 'Teachers', icon: Users },
  { key: 'guardians', label: 'Guardian', icon: UserRoundCheck },
  { key: 'classes', label: 'Classes', icon: FolderPlus },
  { key: 'assessment', label: 'Semestral Assessment', icon: ClipboardPen },
  { key: 'attendance', label: 'Attendance', icon: CalendarCheck },
  { key: 'learningAssessment', label: 'Learning Assessment', icon: ClipboardList },
];

const isDashboardActive = computed(() => ['dashboard', ...dashboardItems.map((item) => item.key)].includes(props.currentPage));

function toggleDashboard() {
  isDashboardOpen.value = !isDashboardOpen.value;
  updatePage('dashboard');
}

function updatePage(page) {
  emit('update:currentPage', page);
}
</script>
