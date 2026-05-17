<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
    @click.self="$emit('update:modelValue', false)"
  >
    <div
      :class="[
        'w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl',
        sizeClass,
      ]"
      @click.stop
    >
      <div v-if="title || $slots.header" class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
        <div>
          <slot name="header">
            <h2 class="text-base font-semibold text-slate-900">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
          </slot>
        </div>
        <button
          type="button"
          class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
          aria-label="Close"
          @click="$emit('update:modelValue', false)"
        >
          x
        </button>
      </div>
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
  },
});

defineEmits(['update:modelValue']);

const sizeClass = computed(() => {
  if (props.size === 'sm') return 'max-w-sm';
  if (props.size === 'lg') return 'max-w-2xl';
  if (props.size === 'xl') return 'max-w-4xl';
  return 'max-w-md';
});
</script>
