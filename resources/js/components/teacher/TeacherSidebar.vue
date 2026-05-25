<template>
  <aside
    class="w-72 shrink-0 flex flex-col border-r border-white/10 bg-[#07111f] text-white fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:static lg:transform-none"
    :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <!-- Brand -->
    <div class="px-5 py-4 border-b border-white/10">
      <div class="flex items-center gap-3">
        <img
          :src="depedLogo"
          alt="Logo"
          class="h-11 w-11 rounded-xl object-contain bg-white p-1 shadow-sm"
        />
        <div class="leading-tight">
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-200">ScanUp</p>
          <h1 class="text-sm font-semibold tracking-tight text-white">Teacher Workspace</h1>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1.5 text-sm">
      <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Class Tools</p>
      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all cursor-pointer"
        :class="currentTab === 'learners' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        @click="$emit('update:currentTab', 'learners'); $emit('update:isSidebarOpen', false)"
      >
        <Users class="h-4 w-4" />
        <span>Learners List</span>
      </button>
      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all cursor-pointer"
        :class="currentTab === 'monitor' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        @click="$emit('update:currentTab', 'monitor'); $emit('update:isSidebarOpen', false)"
      >
        <ClipboardList class="h-4 w-4" />
        <span>Attendance Monitor</span>
      </button>

      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all cursor-pointer"
        :class="currentTab === 'semestralAssessment' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        @click="$emit('update:currentTab', 'semestralAssessment'); $emit('update:isSidebarOpen', false)"
      >
        <ClipboardPen class="h-4 w-4" />
        <span>Semestral Assessment</span>
      </button>
    </nav>
    <div class="p-4">
      <div class="rounded-xl border border-white/10 bg-white/5 p-3">
        <p class="text-xs font-semibold text-white">Classroom Mode</p>
        <p class="mt-1 text-xs leading-5 text-slate-400">Manage learners, attendance, and assessment in one place.</p>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { Users, ClipboardList, ClipboardPen } from 'lucide-vue-next';

defineProps({
  isSidebarOpen: {
    type: Boolean,
    required: true
  },
  currentTab: {
    type: String,
    required: true
  },
  depedLogo: {
    type: String,
    required: true
  }
});

defineEmits(['update:currentTab', 'update:isSidebarOpen']);
</script>
