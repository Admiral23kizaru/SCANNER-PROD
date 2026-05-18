<template>
  <aside class="flex h-full w-72 flex-col border-r border-slate-800 bg-slate-950 text-white">
    <div class="flex items-center gap-3 border-b border-slate-800 p-5">
      <img :src="logoSrc" alt="DepEd Ozamiz" class="h-12 w-12 rounded-full bg-white object-contain p-1" />
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-300">ScanUp</p>
        <h1 class="text-sm font-bold">System Admin</h1>
      </div>
    </div>

    <nav class="flex-1 space-y-1 p-4">
      <button
        v-for="item in items"
        :key="item.key"
        type="button"
        class="w-full rounded-lg px-4 py-3 text-left text-sm font-semibold transition"
        :class="currentPage === item.key ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-slate-900 hover:text-white'"
        @click="$emit('update:currentPage', item.key)"
      >
        {{ item.label }}
      </button>
    </nav>

    <div class="border-t border-slate-800 p-4 text-xs text-slate-400">
      Division control center for read-only monitoring.
    </div>
  </aside>
</template>

<script setup>
defineProps({
  currentPage: {
    type: String,
    required: true,
  },
  logoSrc: {
    type: String,
    required: true,
  },
});

defineEmits(['update:currentPage']);

const items = [
  { key: 'dashboard', label: 'Dashboard' },
  { key: 'schools', label: 'Schools Monitor' },
  { key: 'accounts', label: 'School Admins' },
  { key: 'subjects', label: 'Learning Areas' },
  { key: 'scanners', label: 'Live Scanners' },
  { key: 'reports', label: 'Reports' },
];
</script>
