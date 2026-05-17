<template>
  <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <div v-if="$slots.icon" class="mb-3 text-slate-400">
      <slot name="icon" />
    </div>
    <p class="text-sm font-semibold text-slate-700">{{ title }}</p>
    <p v-if="message" class="mt-1 max-w-md text-sm text-slate-500">{{ message }}</p>
    <div v-if="$slots.actions" class="mt-4">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    default: '',
  },
});
</script>
