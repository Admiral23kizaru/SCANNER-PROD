<template>
  <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-5">
      <h2 class="text-xl font-bold text-slate-950">School Admins Directory</h2>
      <p class="text-sm text-slate-500">
        Reference list of school heads and assigned admins from the latest assignment sheet.
      </p>
    </div>

    <div class="max-h-[650px] overflow-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="sticky top-0 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-4 py-3 text-left">Department ID</th>
            <th class="px-4 py-3 text-left">School</th>
            <th class="px-4 py-3 text-left">School Head</th>
            <th class="px-4 py-3 text-left">Assigned Admin</th>
            <th class="px-4 py-3 text-left">Setup Health</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="school in schools" :key="school.deped_school_id">
            <td class="px-4 py-3 font-semibold text-slate-700">{{ school.deped_school_id }}</td>
            <td class="px-4 py-3 font-semibold text-slate-950">{{ school.school_name }}</td>
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ school.school_head?.name || 'Not set' }}</p>
              <p class="text-xs text-slate-500">{{ school.school_head?.job_title || '' }}</p>
            </td>
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ school.assigned_admin?.name || 'Not set' }}</p>
              <p class="text-xs text-slate-500">{{ school.assigned_admin?.job_title || '' }}</p>
            </td>
            <td class="px-4 py-3">
              <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="healthClass(school.health?.severity)">
                {{ school.health?.label || 'Unknown' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
defineProps({
  schools: {
    type: Array,
    default: () => [],
  },
});

function healthClass(severity) {
  if (severity === 'success') return 'bg-emerald-50 text-emerald-700';
  if (severity === 'danger') return 'bg-red-50 text-red-700';
  if (severity === 'warning') return 'bg-amber-50 text-amber-700';
  return 'bg-slate-100 text-slate-700';
}
</script>
