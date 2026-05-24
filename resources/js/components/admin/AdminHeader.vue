<template>
  <header class="sticky top-0 z-10" :class="themeMode === 'dark' ? 'bg-[#050517]' : 'bg-white'">
    <div class="h-16 flex items-center justify-between px-4 lg:px-10 border-b" :class="themeMode === 'dark' ? 'border-slate-700/80' : 'border-slate-200'">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="lg:hidden p-2 rounded-lg transition"
          :class="themeMode === 'dark' ? 'text-white hover:bg-white/10' : 'text-slate-700 hover:bg-slate-100'"
          @click="$emit('open-sidebar')"
        >
          <Menu class="h-5 w-5" />
        </button>
        <div>
          <p class="text-xs font-medium tracking-[0.25em] uppercase" :class="themeMode === 'dark' ? 'text-slate-400' : 'text-slate-500'">
            {{ pageTitle }}
          </p>
          <p class="text-sm font-semibold" :class="themeMode === 'dark' ? 'text-white' : 'text-slate-900'">
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
            :class="themeMode === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'"
            @click.stop="isProfileOpen = !isProfileOpen"
          >
            <div class="text-right">
              <p class="text-xs font-medium" :class="themeMode === 'dark' ? 'text-white' : 'text-slate-900'">{{ user.name }}</p>
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
                <img v-if="userPhotoError" :src="assetPath('/images/default-avatar.png')" class="w-full h-full object-cover" />
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
import { assetPath } from '../../composables/useAsset';
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
  },
  themeMode: {
    type: String,
    default: 'light'
  }
});

defineEmits(['open-sidebar', 'open-profile-modal', 'logout']);

const isProfileOpen = ref(false);
const userPhotoError = ref(false);

function getPhotoUrl(path) {
  if (!path) return assetPath('/images/default-avatar.png');
  if (/^https?:\/\//i.test(path)) return path;
  const cleanPath = path.replace(/^(public\/|storage\/|\/storage\/|\/public\/)/, '').replace(/^\//, '');
  return assetPath('/storage/' + cleanPath);
}
</script>
