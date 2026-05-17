<template>
  <button
    type="button"
    :disabled="disabled"
    :title="label"
    :aria-label="label"
    :class="[
      'inline-flex items-center justify-center rounded-lg transition focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50',
      sizeClasses,
      variantClasses,
    ]"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  variant: {
    type: String,
    default: 'neutral',
  },
  size: {
    type: String,
    default: 'md',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const sizeClasses = computed(() => {
  if (props.size === 'sm') return 'h-8 w-8';
  if (props.size === 'lg') return 'h-10 w-10';
  return 'h-9 w-9';
});

const variantClasses = computed(() => {
  if (props.variant === 'primary') return 'bg-sky-700 text-white hover:bg-sky-800 focus:ring-sky-200';
  if (props.variant === 'danger') return 'text-slate-600 hover:bg-red-50 hover:text-red-700 focus:ring-red-100';
  if (props.variant === 'soft') return 'text-slate-600 hover:bg-sky-50 hover:text-sky-700 focus:ring-sky-100';
  return 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:ring-slate-200';
});
</script>
