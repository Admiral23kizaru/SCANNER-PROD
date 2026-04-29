<template>
  <div class="space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div>
          <h2 class="text-lg font-bold text-slate-800 tracking-tight">Create School Admin Account</h2>
          <p class="text-sm text-slate-500 mt-1">Register a new school and its principal/admin account.</p>
        </div>
      </div>

      <div class="p-6">
        <form @submit.prevent="submitForm" class="space-y-6 max-w-2xl">
          
          <!-- DepEd School ID -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">DepEd School ID</label>
            <input 
              v-model="form.deped_id" 
              type="text" 
              required
              placeholder="e.g. 128165"
              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow bg-slate-50"
              @input="detectSchoolName"
            />
          </div>

          <!-- Detected School Name -->
          <div v-if="form.deped_id" class="p-4 rounded-xl border" :class="detectedSchool ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'">
            <div class="flex items-center gap-3">
              <div v-if="detectedSchool" class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
              </div>
              <div v-else class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </div>
              <div>
                <p class="text-xs font-bold uppercase tracking-wider" :class="detectedSchool ? 'text-emerald-700' : 'text-red-700'">
                  {{ detectedSchool ? 'School Detected' : 'School Not Found' }}
                </p>
                <p class="text-sm font-medium mt-0.5" :class="detectedSchool ? 'text-emerald-900' : 'text-red-900'">
                  {{ detectedSchool ? detectedSchool : 'Please check the DepEd School ID.' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Admin Email</label>
            <input 
              v-model="form.email" 
              type="email" 
              required
              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow bg-slate-50"
            />
            <p class="text-xs text-slate-500 mt-2">This will be the login username for the school admin.</p>
          </div>

          <!-- Password -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
              <input 
                v-model="form.password" 
                type="password" 
                required
                minlength="6"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow bg-slate-50"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
              <input 
                v-model="form.password_confirmation" 
                type="password" 
                required
                minlength="6"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow bg-slate-50"
              />
            </div>
          </div>

          <!-- Submit Button -->
          <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <p class="text-sm text-red-600 font-medium">{{ errorMessage }}</p>
            <button 
              type="submit" 
              :disabled="isSubmitting || !detectedSchool"
              class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-sm transition-colors flex items-center gap-2"
            >
              <div v-if="isSubmitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
              <span>Create School Account</span>
            </button>
          </div>

        </form>

        <!-- Success Message -->
        <div v-if="successData" class="mt-8 p-6 bg-emerald-50 border border-emerald-200 rounded-2xl">
          <h3 class="text-lg font-bold text-emerald-800 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Account Created Successfully!
          </h3>
          <div class="space-y-3 bg-white p-4 rounded-xl border border-emerald-100 shadow-sm">
            <div class="grid grid-cols-[100px_1fr] gap-2 text-sm">
              <span class="font-semibold text-slate-500">School:</span>
              <span class="font-bold text-slate-900">{{ successData.school_name }}</span>
              
              <span class="font-semibold text-slate-500">Email:</span>
              <span class="font-bold text-slate-900">{{ successData.email }}</span>
              
              <span class="font-semibold text-slate-500">Password:</span>
              <span class="font-bold text-slate-900">{{ successData.password }}</span>
            </div>
          </div>
          <button @click="resetForm" class="mt-4 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-colors">
            Create Another Account
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const schools = {
  '128164': 'Baybay Central School',
  '128165': 'Ozamiz City Central School',
  '128166': 'Bacolod Elementary School',
  '128168': 'Catadman Elementary School',
  '128169': 'Dona Consuelo Elementary School',
  '128170': 'Embargo Elementary School',
  '128171': 'Gango Elementary School',
  '128172': 'Gotocan Elementary School',
  '128173': 'Labo Central School',
  '128174': 'Antero D. Hinagdanan Elementary School',
  '128175': 'Maningcol Central School',
  '128177': 'San Antonio Elementary School',
  '128178': 'Andrea D. Costonera Elementary School',
  '128181': 'Maximino S. Laurete Sr. Central School',
  '128182': 'Felipe Carreon Central School',
  '128186': 'Guimad Elementary School',
  '128187': 'Roman E. Mabanag Sr. Elementary School',
  '128188': 'Antero U. Roa Central School',
  '128190': 'Pershing Tan Queto Sr. Elementary School',
  '128191': 'Balintawak Elementary School',
  '128195': 'Bongbong Elementary School',
  '128196': 'Gala Elementary School',
  '128197': 'Gregorio A. Saquin Elementary School',
  '128198': 'Juan A. Acapulco Elementary School',
  '128199': 'Labinay Elementary School',
  '128201': 'Diego Tuastomban Elementary School',
  '128203': 'Pulot Elementary School',
  '203501': 'Narciso B. Ledesma Central School',
  '203502': 'Hilarion J. Ramiro Jr. Elementary School',
  '203504': 'Mintalar Elementary School',
  '203505': 'Dalapang Elementary School',
  '203506': 'Tipan Elementary School',
  '203507': 'Capucao C Elementary School',
  '304161': 'Sta. Cruz Elementary School',
  '304162': 'Labinay National High School',
  '304163': 'Tabid National High School',
  '304164': 'Labo National High School',
  '304165': 'Jose Lim Ho National High School',
  '304166': 'San Antonio National High School',
  '304167': 'Montol National High School',
  '304168': 'Ozamiz City National High School',
  '304169': 'Ozamiz City School of Arts and Trades',
  '304170': 'Pulot National High School',
  '304171': 'Gala National High School',
  '500699': 'Cogon Integrated School',
  '500700': 'Malaubang Integrated School',
  '500701': 'Misamis Annex Integrated School',
  '501205': 'Cruz Lanzado Saligan Elementary School',
  '501206': 'Jacinto Nemeno Integrated School',
  '501209': 'Marcelino C. Regis Integrated School',
  '502051': 'Sancho Capa Integrated School',
  '502052': 'Sangay Integrated School',
  '502053': 'Sinusa Integrated School',
  '502054': 'Capucao Integrated School',
  '502410': 'Dimaluna Integrated School',
  '502411': 'Domingo A. Barloa Integrated School',
  '502412': 'Faustino C. Decena Integrated School',
  '502413': 'Guingona Integrated School'
};

const form = reactive({
  deped_id: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const detectedSchool = ref('');
const isSubmitting = ref(false);
const errorMessage = ref('');
const successData = ref(null);

function detectSchoolName() {
  const id = form.deped_id.trim();
  if (schools[id]) {
    detectedSchool.value = schools[id];
    form.email = `school${id}@deped.ozamiz.edu.ph`;
  } else {
    detectedSchool.value = '';
    if (form.email.startsWith('school')) {
      form.email = '';
    }
  }
}

async function submitForm() {
  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Passwords do not match.';
    return;
  }
  
  if (!detectedSchool.value) {
    errorMessage.value = 'Invalid DepEd School ID.';
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = '';
  successData.value = null;

  try {
    const payload = {
      deped_school_id: form.deped_id,
      name: detectedSchool.value,
      email: form.email,
      password: form.password
    };
    
    await axios.post('/api/admin/schools', payload);
    
    successData.value = {
      school_name: detectedSchool.value,
      email: form.email,
      password: form.password
    };
    
  } catch (error) {
    errorMessage.value = error.response?.data?.message || error.response?.data?.error || 'Failed to create school account.';
  } finally {
    isSubmitting.value = false;
  }
}

function resetForm() {
  form.deped_id = '';
  form.email = '';
  form.password = '';
  form.password_confirmation = '';
  detectedSchool.value = '';
  errorMessage.value = '';
  successData.value = null;
}
</script>
