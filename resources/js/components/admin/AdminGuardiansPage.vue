<template>
  <section class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Parents and Guardians</h2>
      <form class="mt-4 grid gap-3 md:grid-cols-5" @submit.prevent="save">
        <input v-model="form.name" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Name" required />
        <select v-model="form.relationship" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
          <option>Parent</option>
          <option>Guardian</option>
        </select>
        <input v-model="form.contact_number" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Contact number" />
        <input v-model="form.email" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Email" />
        <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Add</button>
      </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Type</th>
            <th class="px-4 py-3">Learner</th>
            <th class="px-4 py-3">Contact</th>
            <th class="px-4 py-3">Email</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in rows" :key="row.id">
            <td class="px-4 py-3 font-semibold text-slate-900">{{ row.name }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.relationship }}</td>
            <td class="px-4 py-3 text-slate-700">{{ learnerName(row.student) }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.contact_number || '-' }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.email || '-' }}</td>
          </tr>
          <tr v-if="rows.length === 0">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No parent or guardian records yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { createAdminGuardian, fetchAdminGuardians } from '../../services/adminService';

const rows = ref([]);
const form = reactive({ name: '', relationship: 'Guardian', contact_number: '', email: '' });

function learnerName(student) {
  if (!student) return '-';
  return `${student.last_name || ''}, ${student.first_name || ''}`.replace(/^,\s*/, '').trim() || '-';
}

async function load() {
  rows.value = await fetchAdminGuardians();
}

async function save() {
  await createAdminGuardian({ ...form });
  form.name = '';
  form.relationship = 'Guardian';
  form.contact_number = '';
  form.email = '';
  await load();
}

onMounted(load);
</script>
