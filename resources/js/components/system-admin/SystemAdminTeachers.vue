<template>
  <section class="space-y-4">
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 p-4">
        <div class="mb-4">
          <div class="flex items-center gap-2">
            <Filter class="h-4 w-4 text-slate-700" />
            <h2 class="text-lg font-bold text-slate-950">Search &amp; Filter Criteria</h2>
          </div>
          <p class="mt-1 text-sm text-slate-500">Filter teachers by district and school.</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
          <div class="relative">
            <label class="mb-1 block text-sm font-medium text-slate-700">School/Office</label>
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
              @click="schoolDropdownOpen = !schoolDropdownOpen"
            >
              <span class="truncate">{{ schoolOfficeLabel }}</span>
              <ChevronDown class="h-4 w-4 shrink-0 text-slate-500" :class="schoolDropdownOpen ? 'rotate-180' : ''" />
            </button>

            <div
              v-if="schoolDropdownOpen"
              class="absolute z-30 mt-2 max-h-72 w-full overflow-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
            >
              <button
                type="button"
                class="mb-1 flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100"
                @click="clearSchoolFilter"
              >
                All Schools/Offices
              </button>

              <div v-for="group in districtGroups" :key="group.name" class="border-t border-slate-100 py-1 first:border-t-0">
                <button
                  type="button"
                  class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold text-blue-700 hover:bg-blue-50"
                  @click="toggleDistrict(group.name)"
                >
                  <span>{{ group.name }}</span>
                  <ChevronDown class="h-4 w-4" :class="expandedDistrict === group.name ? 'rotate-180' : ''" />
                </button>
                <div v-if="expandedDistrict === group.name" class="ml-3 border-l border-slate-200 py-1">
                  <button
                    v-for="school in group.schools"
                    :key="school.deped_school_id"
                    type="button"
                    class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                    :class="selectedSchoolId === school.deped_school_id ? 'bg-blue-50 font-semibold text-blue-700' : ''"
                    @click="selectSchool(group.name, school)"
                  >
                    {{ school.school_name }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">School Head / Reporting Manager</label>
            <input
              :value="reportingManagerLabel"
              readonly
              class="w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-700"
            />
          </div>
        </div>
      </div>

      <div v-if="error" class="m-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ error }}</div>

      <div class="grid gap-3 border-b border-slate-200 p-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">District</p>
          <p class="mt-1 truncate text-lg font-bold text-slate-950">{{ selectedDistrict || 'All districts' }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">School</p>
          <p class="mt-1 truncate text-lg font-bold text-slate-950">{{ selectedSchool?.school_name || 'All schools' }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teachers</p>
          <p class="mt-1 text-lg font-bold text-slate-950">{{ filteredTeachers.length }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Learners</p>
          <p class="mt-1 text-lg font-bold text-slate-950">{{ learnerTotal }}</p>
        </div>
      </div>

      <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-lg font-bold text-slate-950">Teacher List</h2>
          <p class="text-sm text-slate-500">Teachers under the selected district or school.</p>
        </div>
        <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
          <input
            v-model="search"
            type="search"
            placeholder="Search teacher, school, HRID, advisory"
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 lg:w-96"
          />
          <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700" @click="loadRows">
            Refresh
          </button>
        </div>
      </div>

      <div class="max-h-[580px] overflow-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="sticky top-0 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left">Teacher</th>
              <th class="px-4 py-3 text-left">HRID</th>
              <th class="px-4 py-3 text-left">School</th>
              <th class="px-4 py-3 text-left">Advisory</th>
              <th class="px-4 py-3 text-right">Learners</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="teacher in filteredTeachers" :key="teacher.row_key" class="hover:bg-blue-50/60">
              <td class="px-4 py-3">
                <p class="font-semibold text-slate-950">{{ teacher.name }}</p>
                <p class="text-xs text-slate-500">{{ teacher.email || '-' }}</p>
              </td>
              <td class="px-4 py-3 text-slate-700">{{ teacher.hrid || '-' }}</td>
              <td class="px-4 py-3">
                <p class="font-medium text-slate-800">{{ teacher.school_name }}</p>
                <p class="text-xs text-slate-500">{{ teacher.district }}</p>
              </td>
              <td class="max-w-[340px] px-4 py-3 text-slate-700">
                <p>{{ advisoryLabel(teacher) }}</p>
                <p v-if="advisoryLabel(teacher) !== 'No advisory'" class="mt-1 text-[11px] uppercase tracking-wide text-emerald-600">Advisory class</p>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ teacher.learner_count }}</td>
            </tr>
            <tr v-if="!loading && filteredTeachers.length === 0">
              <td colspan="5" class="px-4 py-10 text-center text-slate-500">No teacher records found for the selected filter.</td>
            </tr>
            <tr v-if="loading">
              <td colspan="5" class="px-4 py-10 text-center text-slate-500">Loading teachers...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { ChevronDown, Filter } from 'lucide-vue-next';
import { fetchSystemAdminTeachers } from '../../services/systemAdminService';

const rows = ref([]);
const loading = ref(false);
const error = ref('');
const search = ref('');
const schoolDropdownOpen = ref(false);
const expandedDistrict = ref('');
const selectedDistrict = ref('');
const selectedSchoolId = ref('');
let refreshTimer = null;

const districtGroups = computed(() => {
  const groups = new Map();

  rows.value.forEach((row) => {
    const name = normalizeDistrictName(row.district || districtNameFromCode(row.district_code)) || 'Unassigned District';
    if (!groups.has(name)) {
      groups.set(name, []);
    }
    groups.get(name).push(row);
  });

  return Array.from(groups.entries())
    .map(([name, schools]) => ({
      name,
      schools: schools.slice().sort((a, b) => String(a.school_name).localeCompare(String(b.school_name))),
    }))
    .sort((a, b) => districtSortValue(a.name) - districtSortValue(b.name));
});

const selectedSchool = computed(() => {
  if (!selectedSchoolId.value) return null;
  return rows.value.find((row) => String(row.deped_school_id) === String(selectedSchoolId.value)) || null;
});

const schoolOfficeLabel = computed(() => {
  if (selectedSchool.value) return selectedSchool.value.school_name;
  if (selectedDistrict.value) return selectedDistrict.value;
  return 'All Schools/Offices';
});

const reportingManagerLabel = computed(() => {
  if (!selectedSchool.value) return 'Select a school to show reporting manager';
  return selectedSchool.value.school_head?.name || selectedSchool.value.assigned_admin?.name || 'Not set';
});

const scopedSchools = computed(() => {
  return rows.value.filter((row) => {
    if (selectedSchoolId.value && String(row.deped_school_id) !== String(selectedSchoolId.value)) return false;
    if (!selectedSchoolId.value && selectedDistrict.value) {
      const district = row.district || districtNameFromCode(row.district_code);
      const normalizedDistrict = normalizeDistrictName(district);
      if (normalizedDistrict !== selectedDistrict.value) return false;
    }
    return true;
  });
});

const teacherRows = computed(() => {
  return scopedSchools.value.flatMap((school) => {
    const district = normalizeDistrictName(school.district || districtNameFromCode(school.district_code));
    return (school.teacher_rows || []).map((teacher) => ({
      ...teacher,
      row_key: `${school.deped_school_id}-${teacher.id || teacher.email || teacher.hrid}`,
      school_name: school.school_name,
      deped_school_id: school.deped_school_id,
      district,
      status: teacher.status || 'Active',
    }));
  });
});

const filteredTeachers = computed(() => {
  const term = search.value.trim().toLowerCase();

  return teacherRows.value.filter((teacher) => {
    if (!term) return true;

    return [
      teacher.name,
      teacher.email,
      teacher.hrid,
      teacher.school_name,
      teacher.district,
      teacher.job_title,
      teacher.role,
      advisoryLabel(teacher),
    ].join(' ').toLowerCase().includes(term);
  });
});

const learnerTotal = computed(() => {
  return scopedSchools.value.reduce((sum, school) => sum + Number(school.learner_count || 0), 0);
});

function toggleDistrict(name) {
  selectedDistrict.value = name;
  selectedSchoolId.value = '';
  expandedDistrict.value = expandedDistrict.value === name ? '' : name;
}

function selectSchool(district, school) {
  selectedDistrict.value = district;
  selectedSchoolId.value = String(school.deped_school_id);
  schoolDropdownOpen.value = false;
}

function clearSchoolFilter() {
  selectedDistrict.value = '';
  selectedSchoolId.value = '';
  expandedDistrict.value = '';
  schoolDropdownOpen.value = false;
}

function advisoryLabel(teacher) {
  const grade = gradeLabel(teacher?.grade_level);
  const section = String(teacher?.section || '').trim();
  if (grade === 'No advisory' && !section) return 'No advisory';
  return section ? `${grade} - ${section}` : grade;
}

function gradeLabel(value) {
  const grade = String(value || '').trim();
  if (!grade) return 'No advisory';
  return grade.toLowerCase().startsWith('grade') ? grade : `Grade ${grade}`;
}

function districtNameFromCode(code) {
  const value = String(code || '').trim();
  if (!value) return '';
  if (/^920\d{2}$/.test(value)) {
    return `District ${Number(value.slice(-2))}`;
  }
  const match = value.match(/(\d+)$/);
  return match ? `District ${Number(match[1])}` : `District ${value}`;
}

function normalizeDistrictName(name) {
  const value = String(name || '').trim();
  const match = value.match(/^District\s+920(\d{2})$/i);
  if (match) return `District ${Number(match[1])}`;
  return value;
}

function districtSortValue(name) {
  const match = String(name || '').match(/(\d+)/);
  return match ? Number(match[1]) : 999;
}

async function loadRows() {
  loading.value = true;
  error.value = '';
  try {
    rows.value = await fetchSystemAdminTeachers();
    if (!expandedDistrict.value && districtGroups.value.length) {
      expandedDistrict.value = districtGroups.value[0].name;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load teacher records.';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadRows();
  refreshTimer = window.setInterval(loadRows, 30000);
});

onUnmounted(() => {
  if (refreshTimer) {
    window.clearInterval(refreshTimer);
  }
});
</script>
