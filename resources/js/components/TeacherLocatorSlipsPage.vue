<template>
  <div class="max-w-6xl mx-auto p-4 sm:p-6">
    <div class="bg-white rounded-lg shadow-md border border-stone-200 overflow-hidden text-stone-800">
      <div class="p-4 sm:p-5 border-b border-stone-200 bg-stone-50/50 flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-stone-800">My Locator Slips</h2>
        <button
          type="button"
          class="rounded-lg px-5 py-2.5 text-sm font-medium text-black shadow-sm transition inline-flex items-center gap-2"
          style="background: linear-gradient(90deg, #03d5ff, #00ffd1);"
          @click="openFormModal"
        >
          <FileText class="h-5 w-5" />
          File Locator Slip
        </button>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="text-white" style="background-color: #050517;">
            <tr>
              <th class="py-3 px-4 font-semibold">Date Filed</th>
              <th class="py-3 px-4 font-semibold">Destination</th>
              <th class="py-3 px-4 font-semibold">Purpose</th>
              <th class="py-3 px-4 font-semibold">Time Out</th>
              <th class="py-3 px-4 font-semibold">Status</th>
              <th class="py-3 px-4 font-semibold text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="slip in slips"
              :key="slip.id"
              class="border-b border-stone-200 hover:bg-stone-50/70 transition"
            >
              <td class="py-4 px-4 text-stone-700 whitespace-nowrap">{{ formatDate(slip.date_of_filing) }}</td>
              <td class="py-4 px-4 text-stone-800 font-medium">{{ slip.destination }}</td>
              <td class="py-4 px-4 text-stone-600 max-w-xs truncate" :title="slip.purpose_of_travel">{{ slip.purpose_of_travel }}</td>
              <td class="py-4 px-4 text-stone-700 tabular-nums">{{ formatTime(slip.time_out) }}</td>
              <td class="py-4 px-4">
                <span :class="statusBadgeClass(slip.status)" class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
                  {{ slip.status }}
                </span>
              </td>
              <td class="py-4 px-4 text-center whitespace-nowrap">
                <button
                  type="button"
                  class="rounded-md px-3 py-1.5 text-xs font-medium text-black shadow-sm transition inline-flex items-center justify-center gap-1.5 hover:opacity-80"
                  style="background: linear-gradient(90deg, #03d5ff, #00ffd1);"
                  title="Print Locator Slip"
                  @click="printSlip(slip)"
                >
                  <Printer class="h-4 w-4" />
                  Print
                </button>
              </td>
            </tr>
            <tr v-if="loading && slips.length === 0">
              <td colspan="6" class="py-12 text-center text-stone-500">Loading…</td>
            </tr>
            <tr v-if="!loading && slips.length === 0">
              <td colspan="6" class="py-12 text-center text-stone-500">You haven't filed any locator slips yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Locator Slip Form Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-stone-200" @click.stop>
        <div class="p-6 pb-4 border-b border-stone-100 flex items-center gap-3">
            <div class="bg-indigo-100 p-2 rounded-lg text-indigo-700">
                <FileText class="w-6 h-6" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-stone-800 leading-tight">Locator Slip Form</h2>
                <p class="text-xs text-stone-500 mt-1">Fill out all required fields to submit your request</p>
            </div>
        </div>
        
        <form @submit.prevent="submitForm" class="p-6 overflow-y-auto flex-1 text-left">
          <div class="space-y-4">
            
            <!-- Date of Filing -->
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">
                 Date of Filing
              </label>
              <input
                v-model="form.date_of_filing"
                type="date"
                required
                class="w-full rounded-md border border-stone-300 bg-stone-50/50 px-3 py-2 text-sm focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              />
            </div>
            
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">
                 Name
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              />
            </div>
            
            <!-- Position -->
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">
                 Position / Designation
              </label>
              <input
                v-model="form.position"
                type="text"
                placeholder="e.g., Teacher I, Master Teacher"
                required
                class="w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              />
            </div>
            
            <!-- Permanent Station -->
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">
                 Permanent Station
              </label>
              <input
                v-model="form.permanent_station"
                type="text"
                placeholder="e.g., DepEd Division Office"
                required
                class="w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              />
            </div>

            <!-- Purpose of Travel -->
            <div>
               <label class="block text-sm font-medium text-stone-700 mb-1">
                 Purpose of Travel
               </label>
               <textarea
                 v-model="form.purpose_of_travel"
                 required
                 rows="3"
                 placeholder="Describe the purpose of your travel..."
                 class="w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm focus:border-[#050517] focus:ring-1 focus:ring-[#050517] resize-y"
               ></textarea>
            </div>
            
            <!-- Official Type -->
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">
                 Official Type
              </label>
              <div class="grid grid-cols-2 gap-3">
                 <label class="flex items-center gap-2 p-2 border border-stone-200 rounded-md cursor-pointer hover:bg-stone-50 transition">
                    <input type="radio" value="Official Business" v-model="form.official_type" required class="text-[#050517] focus:ring-[#050517]" />
                    <span class="text-sm font-medium text-stone-700">Official Business</span>
                 </label>
                 <label class="flex items-center gap-2 p-2 border border-stone-200 rounded-md cursor-pointer hover:bg-stone-50 transition">
                    <input type="radio" value="Official Time" v-model="form.official_type" required class="text-[#050517] focus:ring-[#050517]" />
                    <span class="text-sm font-medium text-stone-700">Official Time</span>
                 </label>
              </div>
            </div>
            
            <!-- Date & Time -->
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">
                 Date & Time
              </label>
              <input
                v-model="form.date_time"
                type="datetime-local"
                required
                class="w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              />
            </div>
            
            <!-- Time Out -->
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">
                 Time Out
              </label>
              <input
                v-model="form.time_out"
                type="time"
                required
                class="w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              />
            </div>
            
            <!-- Expected Return -->
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">
                 Expected Return
              </label>
              <input
                v-model="form.expected_return"
                type="time"
                required
                class="w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              />
            </div>

            <!-- Destination -->
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">
                 Destination
              </label>
              <input
                v-model="form.destination"
                type="text"
                placeholder="Where are you going?"
                required
                class="w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm focus:border-[#050517] focus:ring-1 focus:ring-[#050517]"
              />
            </div>

          </div>
          
          <div v-if="formError" class="mt-4 p-3 bg-red-50 border border-red-200 text-sm text-red-600 rounded-md">
              {{ formError }}
          </div>
          
          <div class="mt-6 pt-4 border-t border-stone-100 flex gap-2">
            <button
               type="button"
               class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 transition w-1/3"
               :disabled="submitting"
               @click="showModal = false"
             >
               Cancel
             </button>
            <button
               type="submit"
               class="w-2/3 flex items-center justify-center gap-2 rounded-md bg-[#050517] px-5 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90 transition"
               :disabled="submitting"
             >
               <Send class="w-4 h-4" />
               {{ submitting ? 'Submitting...' : 'Submit Locator Slip' }}
             </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { FileText, Calendar, User, Briefcase, MapPin, Edit3, CheckSquare, Clock, CalendarClock, Send, Printer } from 'lucide-vue-next';
import { getStoredToken } from '../router';
import axios from 'axios';

// Assume currentUser is passed to prefill Name and Position, or fetch it again
const slips = ref([]);
const loading = ref(false);
const submitting = ref(false);
const showModal = ref(false);
const formError = ref('');

const form = ref({
    date_of_filing: '',
    name: '',
    position: '',
    permanent_station: '',
    destination: '',
    purpose_of_travel: '',
    official_type: '',
    date_time: '',
    time_out: '',
    expected_return: ''
});

const getAxiosConfig = () => {
    const token = getStoredToken();
    return {
        headers: {
            Authorization: `Bearer ${token}`
        }
    };
};

async function fetchCurrentUser() {
  try {
     const res = await axios.get('/api/user', getAxiosConfig());
     if(res.data) {
         form.value.name = res.data.name || '';
         form.value.position = res.data.designation || 'Teacher';
     }
  } catch (err) {}
}

async function load() {
    loading.value = true;
    try {
        const res = await axios.get('/api/teacher/locator-slips', getAxiosConfig());
        slips.value = res.data.data;
    } catch (err) {
        console.error('Failed to load slips', err);
    } finally {
        loading.value = false;
    }
}

function openFormModal() {
    form.value.date_of_filing = new Date().toISOString().split('T')[0];
    // Keep name and position as already fetched
    form.value.permanent_station = '';
    form.value.destination = '';
    form.value.purpose_of_travel = '';
    form.value.official_type = 'Official Business';
    form.value.date_time = '';
    form.value.time_out = '';
    form.value.expected_return = '';
    formError.value = '';
    showModal.value = true;
}

async function submitForm() {
    submitting.value = true;
    formError.value = '';
    try {
        await axios.post('/api/teacher/locator-slips', form.value, getAxiosConfig());
        showModal.value = false;
        await load();
    } catch (err) {
        const msg = err.response?.data?.message || 'Failed to submit locator slip.';
        formError.value = msg;
    } finally {
        submitting.value = false;
    }
}

function printSlip(slip) {
    console.log('Printing slip:', slip.id || slip);
    // Add actual print logic here when ready
    alert('Printing locator slip ' + (slip.id || '...'));
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
  if (status === 'approved') return 'bg-emerald-100 text-emerald-800 border-emerald-200 border';
  if (status === 'rejected') return 'bg-rose-100 text-rose-800 border-rose-200 border';
  return 'bg-amber-100 text-amber-800 border-amber-200 border';
}

onMounted(() => {
    fetchCurrentUser();
    load();
});
</script>
