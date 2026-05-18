<template>
  <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
    <article
      v-for="card in cards"
      :key="card.label"
      class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
    >
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
        {{ card.label }}
      </p>
      <div class="mt-3 flex items-end justify-between gap-3">
        <p class="text-3xl font-bold text-slate-950">{{ card.value }}</p>
        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="card.badgeClass">
          {{ card.badge }}
        </span>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  overview: {
    type: Object,
    default: () => ({}),
  },
});

function numberValue(value) {
  return Number(value || 0).toLocaleString();
}

const cards = computed(() => [
  {
    label: 'Districts',
    value: numberValue(props.overview.districts),
    badge: 'active PSDS',
    badgeClass: 'bg-sky-50 text-sky-700',
  },
  {
    label: 'Schools',
    value: numberValue(props.overview.total_schools),
    badge: `${numberValue(props.overview.scanup_schools)} ready`,
    badgeClass: 'bg-blue-50 text-blue-700',
  },
  {
    label: 'Encoded Students',
    value: numberValue(props.overview.encoded_students),
    badge: 'attendance basis',
    badgeClass: 'bg-emerald-50 text-emerald-700',
  },
  {
    label: 'Synced Teachers',
    value: numberValue(props.overview.synced_teachers),
    badge: 'ScanUp users',
    badgeClass: 'bg-indigo-50 text-indigo-700',
  },
  {
    label: 'Scans Today',
    value: numberValue(props.overview.scans_today),
    badge: `${numberValue(props.overview.schools_with_scans_today)} active schools`,
    badgeClass: 'bg-orange-50 text-orange-700',
  },
]);
</script>
