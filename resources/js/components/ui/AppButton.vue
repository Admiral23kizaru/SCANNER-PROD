<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50',
      sizeClasses,
      variantClasses,
      block ? 'w-full' : '',
    ]"
  >
    <span v-if="loading" class="h-4 w-4 rounded-full border-2 border-current border-t-transparent animate-spin" />
    <slot name="icon" v-else />
    <span>
      <slot />
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: {
    type: String,
    default: 'button',
  },
  variant: {
    type: String,
    default: 'primary',
  },
  size: {
    type: String,
    default: 'md',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  block: {
    type: Boolean,
    default: false,
  },
});

const sizeClasses = computed(() => {
  if (props.size === 'sm') return 'px-3 py-1.5 text-xs';
  if (props.size === 'lg') return 'px-5 py-3 text-sm';
  return 'px-4 py-2.5 text-sm';
});

const variantClasses = computed(() => {
  if (props.variant === 'secondary') {
    return 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-200';
  }
  if (props.variant === 'soft') {
    return 'border border-sky-200 bg-sky-50 text-sky-800 hover:bg-sky-100 focus:ring-sky-100';
  }
  if (props.variant === 'danger') {
    return 'bg-red-600 text-white shadow-sm hover:bg-red-700 focus:ring-red-200';
  }
  if (props.variant === 'danger-secondary') {
    return 'border border-red-200 bg-white text-red-700 hover:bg-red-50 focus:ring-red-100';
  }
  if (props.variant === 'ghost') {
    return 'text-slate-700 hover:bg-slate-100 focus:ring-slate-200';
  }
  return 'bg-sky-700 text-white shadow-sm hover:bg-sky-800 focus:ring-sky-200';
});
</script>
