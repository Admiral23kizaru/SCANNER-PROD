<template>
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
          <h1 class="text-sm font-semibold tracking-tight text-white"></h1>
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
        @click="updatePage('dashboard')"
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
        @click="updatePage('teachers')"
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
        @click="updatePage('students')"
      >
        <GraduationCap class="h-4 w-4" />
        <span>Students</span>
      </button>

      <!-- Manage Sections sidebar item -->
      <button
        type="button"
        class="w-full flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors border-l-2 border-transparent cursor-pointer"
        :class="
          currentPage === 'sections'
            ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm border-l-blue-600'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
        "
        @click="updatePage('sections')"
      >
        <FolderPlus class="h-4 w-4" />
        <span>Manage Sections</span>
      </button>
    </nav>
  </aside>
</template>

<script setup>
import { LayoutDashboard, Users, GraduationCap, FolderPlus } from 'lucide-vue-next';

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
  }
});

const emit = defineEmits(['update:currentPage', 'update:isSidebarOpen']);

function updatePage(page) {
  emit('update:currentPage', page);
  emit('update:isSidebarOpen', false);
}
</script>
