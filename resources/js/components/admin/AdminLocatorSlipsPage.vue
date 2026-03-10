<template>
  <div>
    <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
      <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-lg font-semibold text-slate-900">Locator Slips Validation</h1>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-separate border-spacing-0">
          <thead class="bg-slate-900 text-slate-50 text-xs uppercase tracking-wide">
            <tr>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">Date</th>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">Teacher</th>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">Destination</th>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">Purpose</th>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">Time Out</th>
              <th class="py-3 px-4 font-semibold border-b border-slate-800/80">Status</th>
              <th class="py-3 px-4 font-semibold text-right border-b border-slate-800/80">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="slip in slips"
              :key="slip.id"
              class="border-b border-slate-200/80 odd:bg-slate-50/40 even:bg-white hover:bg-blue-50/60 transition"
            >
              <td class="py-3 px-4 text-slate-700 whitespace-nowrap">{{ formatDate(slip.date_of_filing) }}</td>
              <td class="py-3 px-4 font-medium text-slate-900">
                <div class="flex flex-col">
                  <span>{{ slip.name }}</span>
                  <span class="text-xs text-slate-500">{{ slip.position }}</span>
                </div>
              </td>
              <td class="py-3 px-4 text-slate-700">{{ slip.destination }}</td>
              <td class="py-3 px-4 text-slate-700 max-w-[200px] truncate" :title="slip.purpose_of_travel">{{ slip.purpose_of_travel }}</td>
              <td class="py-3 px-4 tabular-nums text-slate-700">{{ formatTime(slip.time_out) }}</td>
              <td class="py-3 px-4">
                <span :class="statusBadgeClass(slip.status)" class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
                  {{ slip.status }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <button
                  type="button"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 hover:bg-emerald-200 hover:text-emerald-800 transition mr-2"
                  title="Approve"
                  v-if="slip.status === 'pending'"
                  @click="openApprovalModal(slip, 'approved')"
                >
                  <Check class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 hover:text-rose-800 transition mr-2"
                  title="Reject"
                  v-if="slip.status === 'pending'"
                  @click="openApprovalModal(slip, 'rejected')"
                >
                  <X class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-800 transition"
                  title="View Details"
                  @click="openViewModal(slip)"
                >
                  <Eye class="h-4 w-4" />
                </button>
              </td>
            </tr>
            <tr v-if="loading && slips.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-500">Loading…</td>
            </tr>
            <tr v-if="!loading && slips.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-500">No locator slips found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="p-4 border-t border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50/60">
        <div class="flex items-center gap-2 ml-auto">
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="currentPage <= 1 || loading"
            @click="goToPage(currentPage - 1)"
          >
            Previous
          </button>
          <span class="text-sm text-slate-600 px-2">Page {{ currentPage }} of {{ lastPage || 1 }}</span>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="currentPage >= lastPage || loading"
            @click="goToPage(currentPage + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Action Modal -->
    <div v-if="showApprovalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showApprovalModal = false">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-slate-800">
        <h2 class="text-lg font-bold mb-4">
          {{ updatePayload.status === 'approved' ? 'Approve' : 'Reject' }} Locator Slip
        </h2>
        
        <p class="text-sm mb-4">
          Are you sure you want to {{ updatePayload.status === 'approved' ? 'approve' : 'reject' }} the locator slip for <strong class="font-semibold">{{ selectedSlip?.name }}</strong>?
        </p>
        
        <div class="mb-4">
           <label class="block text-sm font-medium text-slate-700 mb-1">Remarks (Optional)</label>
           <textarea v-model="updatePayload.admin_remarks" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600" rows="3"></textarea>
        </div>
        
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700" @click="showApprovalModal = false" :disabled="submitting">Cancel</button>
          <button
            type="button"
            class="rounded-md px-4 py-2 text-sm font-medium text-white shadow-sm"
            :class="updatePayload.status === 'approved' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
            @click="submitStatusUpdate"
            :disabled="submitting"
          >
            {{ submitting ? 'Saving...' : 'Confirm' }}
          </button>
        </div>
      </div>
    </div>

    <!-- View Modal -->
    <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showViewModal = false">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-slate-800">
        <h2 class="text-lg font-bold mb-4 border-b border-slate-200 pb-2">Locator Slip Details</h2>
        
        <div class="space-y-3 text-sm">
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Teacher:</div><div class="w-2/3 font-semibold">{{ selectedSlip?.name }}</div></div>
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Position:</div><div class="w-2/3">{{ selectedSlip?.position }}</div></div>
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Perm. Station:</div><div class="w-2/3">{{ selectedSlip?.permanent_station }}</div></div>
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Date Filed:</div><div class="w-2/3">{{ formatDate(selectedSlip?.date_of_filing) }}</div></div>
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Destination:</div><div class="w-2/3">{{ selectedSlip?.destination }}</div></div>
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Purpose:</div><div class="w-2/3">{{ selectedSlip?.purpose_of_travel }}</div></div>
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Type:</div><div class="w-2/3">{{ selectedSlip?.official_type }}</div></div>
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Date & Time:</div><div class="w-2/3">{{ selectedSlip?.date_time }}</div></div>
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Time Out:</div><div class="w-2/3">{{ formatTime(selectedSlip?.time_out) }}</div></div>
          <div class="flex gap-4"><div class="w-1/3 text-slate-500">Return:</div><div class="w-2/3">{{ formatTime(selectedSlip?.expected_return) }}</div></div>
          
          <div class="flex gap-4 items-center">
            <div class="w-1/3 text-slate-500">Status:</div>
            <div class="w-2/3">
              <span :class="statusBadgeClass(selectedSlip?.status)" class="px-2 py-0.5 rounded text-xs font-semibold uppercase">
                {{ selectedSlip?.status }}
              </span>
            </div>
          </div>
          
          <div v-if="selectedSlip?.status !== 'pending'" class="mt-4 pt-3 border-t border-slate-100">
            <div class="font-medium text-slate-700 mb-1">Reviewed By Admin:</div>
            <div class="text-slate-600">{{ selectedSlip?.admin_remarks || 'No remarks provided.' }}</div>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end">
          <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" @click="showViewModal = false">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Check, X, Eye } from 'lucide-vue-next';
import { getStoredToken } from '../../router';
import axios from 'axios';

const slips = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(15);
const total = ref(0);

const selectedSlip = ref(null);
const showApprovalModal = ref(false);
const showViewModal = ref(false);
const submitting = ref(false);

const updatePayload = ref({
  status: '',
  admin_remarks: ''
});

const getAxiosConfig = () => {
    const token = getStoredToken();
    return {
        headers: {
            Authorization: `Bearer ${token}`
        }
    };
};

async function load() {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/locator-slips', {
      params: { page: currentPage.value },
      ...getAxiosConfig()
    });
    slips.value = res.data.data;
    currentPage.value = res.data.current_page;
    lastPage.value = res.data.last_page;
    total.value = res.data.total;
  } catch (error) {
    console.error('Failed to load locator slips', error);
  } finally {
    loading.value = false;
  }
}

function goToPage(page) {
    currentPage.value = page;
    load();
}

function formatDate(dateString) {
  if (!dateString) return '';
  const opts = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, opts);
}

function formatTime(timeString) {
    if (!timeString) return '';
    try {
        const [hours, minutes] = timeString.split(':');
        const h = parseInt(hours, 10);
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 || 12;
        return `${h12}:${minutes} ${ampm}`;
    } catch {
        return timeString;
    }
}

function statusBadgeClass(status) {
  if (status === 'approved') return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
  if (status === 'rejected') return 'bg-rose-100 text-rose-800 border border-rose-200';
  return 'bg-amber-100 text-amber-800 border border-amber-200';
}

function openApprovalModal(slip, action) {
  selectedSlip.value = slip;
  updatePayload.value.status = action;
  updatePayload.value.admin_remarks = '';
  showApprovalModal.value = true;
}

function openViewModal(slip) {
  selectedSlip.value = slip;
  showViewModal.value = true;
}

async function submitStatusUpdate() {
  submitting.value = true;
  try {
    await axios.put(`/api/admin/locator-slips/${selectedSlip.value.id}/status`, updatePayload.value, getAxiosConfig());
    await load();
    showApprovalModal.value = false;
  } catch (err) {
    alert(err.response?.data?.message || 'Update failed');
  } finally {
    submitting.value = false;
  }
}

onMounted(() => {
  load();
});
</script>
