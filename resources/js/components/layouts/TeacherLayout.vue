<template>
  <div class="h-screen overflow-hidden bg-stone-50 text-stone-900 flex">
    <TeacherSidebar
      :currentTab="currentTab"
      :isSidebarOpen="isSidebarOpen"
      :depedLogo="depedLogo"
      @update:currentTab="$emit('update:currentTab', $event)"
      @update:isSidebarOpen="isSidebarOpen = $event"
    />

    <div v-if="isSidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="isSidebarOpen = false" />

    <div class="flex-1 flex flex-col bg-stone-50 min-w-0 h-full overflow-hidden">
      <TeacherHeader
        :user="user"
        :pageTitle="pageTitle"
        :pageSubtitle="pageSubtitle"
        @open-sidebar="isSidebarOpen = true"
        @open-profile-modal="$emit('open-profile-modal')"
        @logout="$emit('logout')"
      />
      <main class="flex-1 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import TeacherHeader from '../teacher/TeacherHeader.vue';
import TeacherSidebar from '../teacher/TeacherSidebar.vue';

defineProps({
  user: Object,
  pageTitle: String,
  pageSubtitle: String,
  currentTab: String,
  depedLogo: String
});

defineEmits(['update:currentTab', 'open-profile-modal', 'logout']);

const isSidebarOpen = ref(false);
</script>
