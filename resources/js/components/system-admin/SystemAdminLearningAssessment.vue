<template>
  <div class="space-y-5">
    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_220px_220px] lg:items-end">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-600">Division Learning Assessment</p>
          <h2 class="mt-1 text-xl font-bold text-slate-950">All-school assessment workflow</h2>
          <p class="mt-1 text-sm text-slate-500">
            Select one school, then export templates, analyze Excel files, and review mastery charts for that school.
          </p>
        </div>

        <label class="block">
          <span class="mb-1 block text-xs font-semibold text-slate-500">School</span>
          <select
            v-model="selectedSchoolId"
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          >
            <option value="" disabled>Select school</option>
            <option v-for="school in schoolOptions" :key="school.id" :value="school.id">
              {{ school.name }}
            </option>
          </select>
        </label>

        <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2">
          <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Coverage</p>
          <p class="mt-1 text-lg font-black text-blue-950">{{ schoolOptions.length }} schools</p>
        </div>
      </div>
    </section>

    <LearningAssessment
      v-if="selectedSchoolId"
      :key="selectedSchoolId"
      api-base="/api/system-admin/learning-assessment"
      :request-params="{ school_id: selectedSchoolId }"
    />

    <div v-else class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
      Select a school to start the System Admin learning assessment workflow.
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import LearningAssessment from '../teacher/LearningAssessment.vue';

const props = defineProps({
  schools: {
    type: Array,
    default: () => [],
  },
});

const selectedSchoolId = ref('');

const schoolOptions = computed(() => props.schools
  .filter((school) => school.scanup_school_id || school.school_id || school.id)
  .map((school) => ({
    id: school.scanup_school_id || school.school_id || school.id,
    name: school.school_name || school.name || `School ${school.scanup_school_id || school.school_id || school.id}`,
  }))
  .sort((a, b) => a.name.localeCompare(b.name)));

watch(schoolOptions, (options) => {
  if (!selectedSchoolId.value && options.length) {
    selectedSchoolId.value = options[0].id;
  }
}, { immediate: true });
</script>
