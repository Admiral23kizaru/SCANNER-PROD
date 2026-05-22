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
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-200">ScanUp</p>
          <h1 class="text-sm font-semibold tracking-tight text-white">Admin Workspace</h1>
        </div>
      </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1.5 text-sm overflow-y-auto border-r border-white/10">
      <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Management</p>
      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'dashboard'
            ? 'bg-white text-slate-950 shadow-sm'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('dashboard')"
      >
        <LayoutDashboard class="h-4 w-4" />
        <span>Dashboard</span>
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'teachers'
            ? 'bg-white text-slate-950 shadow-sm'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('teachers')"
      >
        <Users class="h-4 w-4" />
        <span>Manage Teachers</span>
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'students'
            ? 'bg-white text-slate-950 shadow-sm'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('students')"
      >
        <GraduationCap class="h-4 w-4" />
        <span>Learners List</span>
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'attendance'
            ? 'bg-white text-slate-950 shadow-sm'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('attendance')"
      >
        <ClipboardList class="h-4 w-4" />
        <span>Attendance Monitor</span>
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'learningAssessment'
            ? 'bg-white text-slate-950 shadow-sm'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('learningAssessment')"
      >
        <ClipboardPen class="h-4 w-4" />
        <span>Learning Assessment</span>
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'subjects'
            ? 'bg-white text-slate-950 shadow-sm'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('subjects')"
      >
        <BookOpen class="h-4 w-4" />
        <span>Manage Subjects</span>
      </button>

      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-all cursor-pointer"
        :class="
          currentPage === 'sections'
            ? 'bg-white text-slate-950 shadow-sm'
            : 'text-slate-300 hover:bg-white/10 hover:text-white'
        "
        @click="updatePage('sections')"
      >
        <FolderPlus class="h-4 w-4" />
        <span>Manage Sections</span>
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
import { LayoutDashboard, Users, GraduationCap, FolderPlus, BookOpen, ClipboardList, ClipboardPen } from 'lucide-vue-next';

defineProps({
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
  }
});

const emit = defineEmits(['update:currentPage', 'update:isSidebarOpen']);

function updatePage(page) {
  emit('update:currentPage', page);
  emit('update:isSidebarOpen', false);
}
</script>
