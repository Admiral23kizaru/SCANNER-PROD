<template>
  <aside
    class="w-64 shrink-0 flex flex-col border-r border-stone-200 bg-white fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:static lg:transform-none"
    :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <!-- Brand -->
    <div class="px-6 py-3 border-b border-stone-700" style="background-color: #050517;">
      <div class="flex items-center gap-3">
        <img
          :src="depedLogo"
          alt="Logo"
          class="h-10 w-auto rounded-full object-contain bg-white"
        />
        <div class="leading-tight">
          <h1 class="text-sm font-semibold tracking-tight text-white"></h1>
          <p class="text-[17px] text-white uppercase tracking-[0.18em]">
            Teacher
          </p>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition cursor-pointer"
        :class="currentTab === 'learners' ? 'bg-stone-800 text-white shadow-sm' : 'text-stone-400 hover:bg-stone-800/50 hover:text-white'"
        @click="$emit('update:currentTab', 'learners'); $emit('update:isSidebarOpen', false)"
      >
        <Users class="h-4 w-4" />
        <span>Learners List</span>
      </button>
      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition cursor-pointer"
        :class="currentTab === 'monitor' ? 'bg-stone-800 text-white shadow-sm' : 'text-stone-400 hover:bg-stone-800/50 hover:text-white'"
        @click="$emit('update:currentTab', 'monitor'); $emit('update:isSidebarOpen', false)"
      >
        <ClipboardList class="h-4 w-4" />
        <span>Attendance Monitor</span>
      </button>
    </nav>
  </aside>
</template>

<script setup>
import { Users, ClipboardList } from 'lucide-vue-next';

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
