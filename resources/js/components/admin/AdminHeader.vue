<template>
  <header class="sticky top-0 z-10" style="background-color: #050517;">
    <div class="h-16 flex items-center justify-between px-4 lg:px-10 border-b border-slate-700/80">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="lg:hidden p-2 text-white hover:bg-white/10 rounded-lg transition"
          @click="$emit('open-sidebar')"
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
                  @click="isProfileOpen = false; $emit('open-profile-modal')"
                >
                  <UserCircle class="h-4 w-4" />
                  <span>My Profile</span>
                </button>
              </div>

              <div class="border-t border-slate-100 py-1">
                <button
                  type="button"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                  @click="isProfileOpen = false; $emit('logout')"
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
</template>

<script setup>
import { ref } from 'vue';
import { Menu, ChevronDown, UserCircle, LogOut } from 'lucide-vue-next';

const props = defineProps({
  user: {
    type: Object,
    default: null
  },
  pageTitle: {
    type: String,
    required: true
  },
  pageSubtitle: {
    type: String,
    required: true
  }
});

defineEmits(['open-sidebar', 'open-profile-modal', 'logout']);

const isProfileOpen = ref(false);
const userPhotoError = ref(false);

function getPhotoUrl(path) {
  if (!path) return '/images/default-avatar.png';
  if (/^https?:\/\//i.test(path)) return path;
  const cleanPath = path.replace(/^(public\/|storage\/|\/storage\/|\/public\/)/, '').replace(/^\//, '');
  return '/storage/' + cleanPath;
}
</script>
