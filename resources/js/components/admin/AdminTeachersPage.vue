<template>
  <div>
    <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
      <!-- Toolbar (match screenshot layout) -->
      <div class="p-4 sm:p-5 border-b border-slate-200 bg-white flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
          <button
            type="button"
            class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800 shadow-sm transition inline-flex items-center gap-2"
            @click="openCreateModal"
          >
            <Plus class="h-4 w-4" />
            Create Teacher
          </button>

          <button
            type="button"
            class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-800 hover:bg-indigo-100 transition inline-flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="openEhrisModal"
            :disabled="ehrisModalLoading"
          >
            <Users class="h-4 w-4" />
            Fetch from EHRIS
          </button>

          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition inline-flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="handleExport"
            :disabled="exporting"
          >
            <Download class="h-4 w-4" />
            {{ exporting ? 'Exporting...' : 'Export' }}
          </button>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
          <div class="relative flex-1 md:flex-none">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Search teachers..."
              class="w-full md:w-64 rounded-lg border border-slate-200 bg-white pl-9 pr-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              @input="debouncedTeacherSearch"
            />
          </div>
          <div class="relative shrink-0">
            <button
              type="button"
              class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition"
              :class="(filterGrade || filterSection) ? 'ring-2 ring-indigo-200 border-indigo-200' : ''"
              title="Filter by grade / section"
              @click="showFilterPanel = !showFilterPanel"
            >
              <Filter class="h-4 w-4" />
            </button>
            <div
              v-if="showFilterPanel"
              class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-lg z-20 text-left"
              @click.stop
            >
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Filters</p>
              <div class="space-y-2">
                <div>
                  <label class="block text-xs font-medium text-slate-600 mb-1">Grade</label>
                  <select
                    v-model="filterGrade"
                    class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm bg-white"
                  >
                    <option value="">All grades</option>
                    <option v-for="g in gradeFilterOptions" :key="g" :value="g">{{ g }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 mb-1">Section</label>
                  <select
                    v-model="filterSection"
                    class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm bg-white"
                  >
                    <option value="">All sections</option>
                    <option v-for="s in sectionFilterOptions" :key="s" :value="s">{{ s }}</option>
                  </select>
                </div>
              </div>
              <div class="mt-3 flex justify-end gap-2">
                <button
                  type="button"
                  class="text-xs font-medium text-slate-600 hover:text-slate-900 px-2 py-1"
                  @click="clearGradeSectionFilters"
                >
                  Clear
                </button>
                <button
                  type="button"
                  class="text-xs font-medium text-white bg-slate-900 rounded-lg px-3 py-1.5 hover:bg-slate-800"
                  @click="showFilterPanel = false"
                >
                  Done
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="lastSyncSummary"
        class="mx-4 mb-0 mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
      >
        <span class="font-semibold">Last EHRIS sync:</span>
        {{ lastSyncSummary.synced_count }} synced
        ({{ lastSyncSummary.created_count }} teachers created,
        {{ lastSyncSummary.updated_count }} teachers updated
        <template v-if="lastSyncSummary.skipped_count">
          , {{ lastSyncSummary.skipped_count }} skipped
        </template>
        · Users: {{ lastSyncSummary.users_created_count }} created,
        {{ lastSyncSummary.users_updated_count }} updated)
        <span class="text-emerald-700/80">· {{ lastSyncSummary.at }}</span>
        <button
          type="button"
          class="ml-2 text-xs font-medium text-emerald-800 underline underline-offset-2 hover:text-emerald-950"
          @click="lastSyncSummary = null"
        >
          Dismiss
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-separate border-spacing-0">
          <thead class="bg-slate-50 text-slate-500 text-xs font-medium">
            <tr>
              <th class="py-3 px-4 border-b border-slate-200">Name</th>
              <th class="py-3 px-4 border-b border-slate-200">Employee ID</th>
              <th class="py-3 px-4 border-b border-slate-200">Grade / Section</th>
              <th class="py-3 px-4 border-b border-slate-200">Created</th>
              <th class="py-3 px-4 text-right border-b border-slate-200">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(t, idx) in filteredTeachers"
              :key="t.id"
              class="border-b border-slate-100 hover:bg-slate-50 transition"
            >
              <td class="py-3 px-4">
                <div 
                  class="flex items-center gap-3 cursor-pointer group" 
                  @click="viewTeacherStudents(t)" 
                  title="Click to view enrolled students"
                >
                  <div
                    class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0 shadow-sm transition group-hover:ring-2 group-hover:ring-indigo-100 group-hover:scale-105"
                  >
                    <img
                      v-if="t.profile_photo && !photoLoadError[t.id]"
                      :src="getPhotoUrl(t.profile_photo)"
                      alt=""
                      class="w-full h-full object-cover"
                      @error="handlePhotoError(t.id)"
                    />
                    <div
                      v-else
                      class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-400 to-slate-500 text-white font-semibold text-sm"
                    >
                      <img v-if="photoLoadError[t.id]" :src="assetPath('/images/default-avatar.png')" class="w-full h-full object-cover" @error="photoLoadError[t.id] = 'failed_twice'" />
                      <span v-else>{{ t.name?.charAt(0) || 'T' }}</span>
                    </div>
                  </div>
                  <div class="min-w-0">
                    <div class="font-semibold text-indigo-600 group-hover:text-indigo-800 group-hover:underline truncate transition-colors">{{ t.name }}</div>
                    <div class="text-xs text-slate-500 truncate">{{ t.job_title || 'Teacher' }}</div>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4 text-slate-700 whitespace-nowrap">
                {{ t.employee_id || '—' }}
              </td>
              <td class="py-3 px-4 text-slate-700">{{ formatTeacherGradeSection(t) }}</td>
              <td class="py-3 px-4 text-slate-600">{{ formatDate(t.created_at) }}</td>
              <td class="py-3 px-4 text-right">
                <span class="inline-flex items-center justify-end gap-3">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                    title="Edit teacher"
                    @click="openEditModal(t)"
                  >
                    <PencilLine class="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-red-600 hover:bg-slate-100 transition"
                    title="Delete teacher"
                    @click="confirmDelete(t)"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </span>
              </td>
            </tr>
            <tr v-if="loading && teachers.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-500">Loading…</td>
            </tr>
            <tr v-if="!loading && filteredTeachers.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-500">
                {{ emptyTeachersMessage }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50/60">
        <span class="text-sm text-slate-600">
          Showing {{ filteredTeachers.length }} on this page · {{ teacherTotal }} total
        </span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="teacherListPage <= 1"
            @click="goTeacherPage(teacherListPage - 1)"
            title="Previous page"
          >
            <ChevronLeft class="h-4 w-4" />
          </button>
          <span class="text-sm text-slate-600 px-1">{{ teacherListPage }} / {{ teacherLastPage || 1 }}</span>
          <button
            type="button"
            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="teacherListPage >= teacherLastPage"
            @click="goTeacherPage(teacherListPage + 1)"
            title="Next page"
          >
            <ChevronRight class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- 
      Header Comment: Action: Implementing static backdrop to prevent accidental data loss during teacher creation.
    -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold p-6 pb-0">Create Teacher Account</h2>
        <form @submit.prevent="submitCreate" class="p-6 overflow-y-auto flex-1">
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Name</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Employee ID</label>
              <input
                v-model="form.employee_id"
                type="text"
                required
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Grade Level</label>
                <select
                  v-model="form.grade_level"
                  class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm bg-white"
                >
                  <option value="">Select grade</option>
                  <option v-for="g in gradeLevelOptions" :key="g" :value="g">{{ g }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Section</label>
                <select
                  v-model="form.section"
                  class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm bg-white"
                >
                  <option value="">Select section</option>
                  <option v-for="s in createSectionOptions" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Password</label>
              <input
                v-model="form.password"
                type="password"
                required
                minlength="8"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Confirm Password</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm"
              />
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-3">
              <label class="block text-sm font-medium text-stone-700 mb-2">
                Profile photo
                <span class="text-xs text-stone-500 font-normal">(optional, JPG/PNG, max 2&nbsp;MB)</span>
              </label>
              <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-stone-300 text-sm font-medium text-stone-700 cursor-pointer hover:bg-stone-50">
                  Choose file
                  <input
                    ref="createPhotoInput"
                    type="file"
                    accept="image/png,image/jpeg"
                    class="sr-only"
                    @change="onCreatePhotoChange"
                  />
                </label>
                <span class="text-xs text-stone-600 truncate">
                  {{ createPhotoFileName || 'No file chosen' }}
                </span>
              </div>
              <p v-if="createPhotoError" class="mt-1 text-xs text-red-600">{{ createPhotoError }}</p>
            </div>
          </div>
          <div v-if="formError" class="mt-2 text-sm text-red-600">{{ formError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button
              type="button"
              class="rounded-md border border-stone-300 px-4 py-2 text-sm"
              @click="showCreateModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-md bg-blue-800 px-4 py-2 text-sm font-medium text-white hover:bg-blue-900"
            >
              Create
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- 
      Header Comment: Action: Implementing static backdrop to prevent accidental data loss during teacher editing.
    -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] flex flex-col border border-stone-200" @click.stop>
        <h2 class="text-lg font-semibold p-6 pb-0">Edit Teacher</h2>
        <form @submit.prevent="submitEdit" class="p-6 overflow-y-auto flex-1">
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Name</label>
              <input v-model="editForm.name" type="text" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700 mb-1">Employee ID</label>
              <input v-model="editForm.employee_id" type="text" required class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Grade Level</label>
                <select
                  v-model="editForm.grade_level"
                  class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm bg-white"
                >
                  <option value="">Select grade</option>
                  <option v-for="g in gradeLevelOptions" :key="g" :value="g">{{ g }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Section</label>
                <select
                  v-model="editForm.section"
                  class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm bg-white"
                >
                  <option value="">Select section</option>
                  <option v-for="s in editSectionOptions" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-3">
              <p class="text-xs text-stone-500 mb-2">Optional: set a new password for this teacher.</p>
              <div class="space-y-2">
                <div>
                  <label class="block text-sm font-medium text-stone-700 mb-1">New Password</label>
                  <input v-model="editForm.password" type="password" minlength="8" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-stone-700 mb-1">Confirm New Password</label>
                  <input v-model="editForm.password_confirmation" type="password" class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm" />
                </div>
              </div>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-3">
              <label class="block text-sm font-medium text-stone-700 mb-2">
                Profile photo
                <span class="text-xs text-stone-500 font-normal">(optional, JPG/PNG, max 2&nbsp;MB)</span>
              </label>
              <div class="flex items-center gap-4 mb-3" v-if="editForm.profile_photo">
                <div class="w-16 h-16 rounded-lg overflow-hidden border border-stone-200 bg-stone-100 shadow-sm">
                  <img :src="getPhotoUrl(editForm.profile_photo)" class="w-full h-full object-cover" />
                </div>
                <div class="text-[10px] text-stone-400">Current photo</div>
              </div>
              <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-stone-300 text-sm font-medium text-stone-700 cursor-pointer hover:bg-stone-50">
                  Choose file
                  <input
                    ref="editPhotoInput"
                    type="file"
                    accept="image/png,image/jpeg"
                    class="sr-only"
                    @change="onEditPhotoChange"
                  />
                </label>
                <span class="text-xs text-stone-600 truncate">
                  {{ editPhotoFileName || 'No file chosen' }}
                </span>
              </div>
              <p v-if="editPhotoError" class="mt-1 text-xs text-red-600">{{ editPhotoError }}</p>
            </div>
          </div>
          <div v-if="editError" class="mt-2 text-sm text-red-600">{{ editError }}</div>
          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="rounded-md border border-stone-300 px-4 py-2 text-sm" @click="showEditModal = false">Cancel</button>
            <button type="submit" class="rounded-md bg-blue-800 px-4 py-2 text-sm font-medium text-white hover:bg-blue-900">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- EHRIS preview + selective sync (read-only source; writes only to ScanUp teachers/users) -->
    <div
      v-if="showEhrisModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeEhrisModal"
    >
      <div
        class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col border border-slate-200"
        @click.stop
      >
        <div class="flex items-center justify-between gap-3 p-4 sm:p-5 border-b border-slate-200">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">EHRIS teachers (preview)</h2>
            <p class="text-xs text-slate-500 mt-1">
              Active EHRIS accounts with role Teacher for DepEd ID
              <span class="font-mono font-medium text-slate-700">{{ ehrisDepedSchoolId || '—' }}</span>
              · Read-only from EHRIS; sync updates ScanUp only.
            </p>
          </div>
          <button
            type="button"
            class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800"
            aria-label="Close"
            @click="closeEhrisModal"
          >
            ✕
          </button>
        </div>

        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row gap-3 sm:items-center">
          <div class="relative flex-1">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
            <input
              v-model="ehrisSearchInput"
              type="search"
              placeholder="Search name, email, employee ID…"
              class="w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 py-2.5 text-sm"
              @keyup.enter="loadEhrisPreview"
            />
          </div>
          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            :disabled="ehrisModalLoading"
            @click="loadEhrisPreview"
          >
            <RefreshCw class="h-4 w-4" :class="ehrisModalLoading ? 'animate-spin' : ''" />
            Refresh preview
          </button>
        </div>

        <div v-if="ehrisModalError" class="mx-4 sm:mx-5 mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
          {{ ehrisModalError }}
        </div>

        <div class="flex-1 overflow-auto min-h-[200px] p-4 sm:p-5">
          <div v-if="ehrisModalLoading && !ehrisRows.length" class="py-16 text-center text-slate-500 text-sm">
            Loading EHRIS roster…
          </div>
          <div v-else-if="!ehrisRows.length && !ehrisModalLoading" class="py-16 text-center text-slate-500 text-sm">
            No EHRIS teachers found for this school. Check DepEd School ID on the school record or try another search.
          </div>
          <table v-else class="w-full text-sm text-left border-separate border-spacing-0">
            <thead class="bg-slate-50 text-slate-600 text-xs font-medium">
              <tr>
                <th class="py-2 px-3 border-b border-slate-200 w-10">
                  <input
                    type="checkbox"
                    class="rounded border-slate-300"
                    :checked="ehrisAllSelected"
                    @change="toggleSelectAllEhris($event.target.checked)"
                  />
                </th>
                <th class="py-2 px-3 border-b border-slate-200">Name</th>
                <th class="py-2 px-3 border-b border-slate-200">Email</th>
                <th class="py-2 px-3 border-b border-slate-200">Employee ID</th>
                <th class="py-2 px-3 border-b border-slate-200">In ScanUp</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in ehrisRows"
                :key="row.ehris_user_id + '-' + row.employee_id"
                class="border-b border-slate-100 hover:bg-slate-50/80"
              >
                <td class="py-2 px-3 align-middle">
                  <input
                    type="checkbox"
                    class="rounded border-slate-300"
                    :checked="ehrisSelectedIds.includes(row.employee_id)"
                    @change="toggleEhrisRow(row.employee_id, $event.target.checked)"
                  />
                </td>
                <td class="py-2 px-3 font-medium text-slate-900">{{ row.name }}</td>
                <td class="py-2 px-3 text-slate-600 truncate max-w-[180px]" :title="row.email">{{ row.email }}</td>
                <td class="py-2 px-3 text-slate-700 font-mono text-xs">{{ row.employee_id }}</td>
                <td class="py-2 px-3">
                  <span
                    v-if="row.is_synced"
                    class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800"
                  >Yes</span>
                  <span
                    v-else
                    class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900"
                  >No</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 sm:p-5 border-t border-slate-200 bg-slate-50/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <p class="text-xs text-slate-600">
            {{ ehrisRows.length }} row(s) in preview · {{ ehrisSelectedIds.length }} selected
          </p>
          <div class="flex flex-wrap gap-2 justify-end">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              @click="closeEhrisModal"
            >
              Close
            </button>
            <button
              type="button"
              class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
              :disabled="syncingEhris || !ehrisRows.length"
              @click="runEhrisSyncSelected"
            >
              {{ syncingEhris ? 'Syncing…' : 'Sync selected' }}
            </button>
            <button
              type="button"
              class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
              :disabled="syncingEhris || !ehrisRows.length"
              @click="runEhrisSyncAll"
            >
              {{ syncingEhris ? 'Syncing…' : 'Sync all in preview' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showDeleteModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" @click.stop>
        <h2 class="text-lg font-semibold text-stone-800 mb-2">Delete Teacher</h2>
        <p class="text-sm text-stone-600 mb-4">
          Are you sure you want to delete <strong>{{ deleteTarget?.name }}</strong> ({{ deleteTarget?.employee_id }})?
        </p>
        <div v-if="deleteError" class="mb-3 text-sm text-red-600">{{ deleteError }}</div>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-md border border-stone-300 px-4 py-2 text-sm" @click="showDeleteModal = false">Cancel</button>
          <button type="button" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50" :disabled="deleting" @click="executeDelete">
            {{ deleting ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
    <!-- Population Analytics Modal (Teacher Students) -->
    <div v-if="isStudentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeStudentModal"></div>
      <div class="relative bg-white rounded-2xl w-full max-w-2xl shadow-xl flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
          <h2 class="text-lg font-semibold text-slate-900">{{ studentModalTitle }}</h2>
          <button @click="closeStudentModal" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 transition">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        
        <div class="flex-1 overflow-auto p-5">
          <div v-if="studentModalLoading" class="flex flex-col items-center justify-center py-12">
            <div class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            <p class="mt-4 text-sm font-medium text-slate-500">Loading students...</p>
          </div>
          <div v-else-if="studentModalList.length === 0" class="text-center py-12 text-slate-500">
            No students found for this teacher's assignment.
          </div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="student in studentModalList" :key="student.id" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50">
              <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 text-indigo-700 font-bold border border-indigo-200">
                 {{ student.last_name?.charAt(0) }}{{ student.first_name?.charAt(0) }}
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900 truncate">{{ student.last_name }}, {{ student.first_name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ student.grade || 'No Grade' }} - {{ student.section || 'No Section' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { assetPath } from '../../composables/useAsset';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { PencilLine, Trash2, Plus, Download, Search, Filter, RefreshCw, Users, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { fetchTeachers, createTeacher, updateTeacher, deleteTeacher, uploadTeacherPhoto, exportAdminTeachers, fetchEhrisTeachers, syncEhrisTeachers } from '../../services/adminService';

const teachers = ref([]);
const searchQuery = ref('');
const loading = ref(false);
const loadError = ref('');
const teacherListPage = ref(1);
const teacherLastPage = ref(1);
const teacherTotal = ref(0);
const teacherPerPage = ref(50);
let teacherSearchTimer = null;

function getAuthHeaders() {
  const token = localStorage.getItem('scan_up_token');
  return token ? { Authorization: `Bearer ${token}`, Accept: 'application/json' } : { Accept: 'application/json' };
}

const showFilterPanel = ref(false);
const filterGrade = ref('');
const filterSection = ref('');
/** Sections from `/api/admin/sections` (authoritative names + grade levels for this school). */
const schoolSections = ref([]);
const exporting = ref(false);
const syncingEhris = ref(false);
const showEhrisModal = ref(false);
const ehrisModalLoading = ref(false);
const ehrisModalError = ref('');
const ehrisRows = ref([]);
const ehrisDepedSchoolId = ref('');
const ehrisSearchInput = ref('');
const ehrisSelectedIds = ref([]);
const lastSyncSummary = ref(null);

/**
 * ehrisAllSelected
 * PURPOSE: True when every preview row’s employee_id is selected.
 * WHY: Drives the header checkbox for bulk select in the EHRIS modal.
 */
const ehrisAllSelected = computed(() => {
  if (!ehrisRows.value.length) return false;
  const ids = ehrisRows.value.map((r) => r.employee_id);
  return ids.every((id) => ehrisSelectedIds.value.includes(id));
});
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);
const deleteError = ref('');

/**
 * Action: Implementing Section-based Teacher Assignment and Gender-specific Dashboard Analytics.
 * Grade level options matching the Philippine K-12 curriculum.
 */
const gradeLevelOptions = [
  'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
  'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12',
];

function formatTeacherGradeSection(t) {
  const grade = t?.grade_level;
  const section = t?.section;
  if (grade && section) return `${grade} / ${section}`;
  if (grade) return grade;
  if (section) return section;
  return '—';
}

const gradeFilterOptions = computed(() => {
  const set = new Set(gradeLevelOptions);
  schoolSections.value.forEach((sec) => {
    if (sec.grade_level) set.add(sec.grade_level);
  });
  teachers.value.forEach((teacher) => {
    if (teacher.grade_level) set.add(teacher.grade_level);
  });
  return Array.from(set).sort((a, b) => String(a).localeCompare(String(b)));
});

/**
 * Section names from Manage Sections API. When a grade is selected, only sections
 * for that grade are listed so the filter matches what exists in the system.
 */
const sectionFilterOptions = computed(() => {
  const sections = schoolSections.value;
  const grade = filterGrade.value;

  if (sections.length) {
    const names = sections
      .filter((s) => !grade || (s.grade_level || '') === grade)
      .map((s) => s.name)
      .filter(Boolean);
    return [...new Set(names)].sort((a, b) => String(a).localeCompare(String(b)));
  }

  // Fallback if sections API failed or returned nothing: derive from teachers only.
  const fromTeachers = teachers.value
    .filter((t) => !grade || (t.grade_level || '') === grade)
    .map((t) => t.section)
    .filter(Boolean);
  const set = new Set([...fromTeachers]);
  return Array.from(set).sort((a, b) => String(a).localeCompare(String(b)));
});

watch(filterGrade, () => {
  const allowed = new Set(sectionFilterOptions.value);
  if (filterSection.value && !allowed.has(filterSection.value)) {
    filterSection.value = '';
  }
});

watch(showFilterPanel, (open) => {
  if (open) loadSchoolSections();
});

const filteredTeachers = computed(() => {
  const fg = filterGrade.value;
  const fs = filterSection.value;

  return teachers.value.filter((t) => {
    if (fg && (t.grade_level || '') !== fg) return false;
    if (fs && (t.section || '') !== fs) return false;
    return true;
  });
});

const emptyTeachersMessage = computed(() => {
  if (!teachers.value.length && !loading.value) {
    if (loadError.value) return loadError.value;
    if (teacherTotal.value === 0) return 'No teachers yet.';
    return 'No teachers on this page.';
  }
  if (filteredTeachers.value.length) return '';
  if (filterGrade.value || filterSection.value) return 'No teachers match the selected filters on this page.';
  return 'No teachers match the selected filters.';
});

function clearGradeSectionFilters() {
  filterGrade.value = '';
  filterSection.value = '';
}

async function loadSchoolSections() {
  try {
    const res = await axios.get('/api/admin/sections', { headers: getAuthHeaders() });
    schoolSections.value = res.data?.data || [];
  } catch {
    schoolSections.value = [];
  }
}

const form = ref({
  name: '',
  employee_id: '',
  grade_level: '',
  section: '',
  password: '',
  password_confirmation: '',
});
const formError = ref('');

const editTargetId = ref(null);
const editForm = ref({ name: '', employee_id: '', grade_level: '', section: '', password: '', password_confirmation: '' });
const editError = ref('');

const createPhotoFile = ref(null);
const createPhotoFileName = ref('');
const createPhotoError = ref('');
const createPhotoInput = ref(null);

const editPhotoFile = ref(null);
const editPhotoFileName = ref('');
const editPhotoError = ref('');
const editPhotoInput = ref(null);

const photoLoadError = ref({});

function getAvailableSections(grade) {
  const all = schoolSections.value || [];
  const scoped = grade
    ? all.filter((s) => (s.grade_level || '') === grade)
    : all;

  const namesFromApi = scoped.map((s) => s.name).filter(Boolean);
  const unique = Array.from(new Set(namesFromApi));

  if (unique.length) {
    return unique.sort((a, b) => String(a).localeCompare(String(b)));
  }

  // Fallback (only if API returns no sections yet)
  return [];
}

const createSectionOptions = computed(() => getAvailableSections(form.value.grade_level));
const editSectionOptions = computed(() => getAvailableSections(editForm.value.grade_level));

watch(
  () => form.value.grade_level,
  () => {
    if (form.value.section && !createSectionOptions.value.includes(form.value.section)) {
      form.value.section = '';
    }
  },
);

watch(
  () => editForm.value.grade_level,
  () => {
    if (editForm.value.section && !editSectionOptions.value.includes(editForm.value.section)) {
      editForm.value.section = '';
    }
  },
);

function getPhotoUrl(path) {
  if (!path) return assetPath('/images/default-avatar.png');
  // Strip 'public/' or 'storage/' or leading slashes
  const cleanPath = path.replace(/^(public\/|storage\/|\/storage\/|\/public\/)/, '').replace(/^\//, '');
  return assetPath('/storage/' + cleanPath);
}

function handlePhotoError(id) {
  photoLoadError.value[id] = true;
}

function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString();
}

// ─── Modular Teacher-Student Modal ──────────────────────────────────────────
// Why: Allows the admin to click on a teacher in the list and see which
//      students are assigned to them based on their grade_level & section.
// How: Sends an axios GET to /api/admin/dashboard/analytics?type=teacher_students
//      and displays the result in a dedicated drill-down modal.

const isStudentModalOpen = ref(false);
const studentModalTitle = ref('');
const studentModalList = ref([]);
const studentModalLoading = ref(false);

/**
 * // Description: viewTeacherStudents - Opens a drill-down modal showing students
 * //   assigned to a specific teacher's grade_level and section.
 * // Author: Antigravity System Agent
 *
 * @param {Object} teacher - The teacher object from the list (must have .id and .name)
 */
async function viewTeacherStudents(teacher) {
  const userId = teacher.user_id;
  if (!userId) {
    alert('This teacher has no linked ScanUp login user yet. Sync from EHRIS or create the account, then try again.');
    return;
  }

  // 1. Show the modal immediately with a loading spinner
  isStudentModalOpen.value = true;
  studentModalTitle.value = `Students for ${teacher.name}`;
  studentModalLoading.value = true;
  studentModalList.value = [];

  try {
    // 2. Ask the backend for students matching this teacher's grade & section (users.id)
    const response = await axios.get(
      `/api/admin/dashboard/analytics?type=teacher_students&teacher_id=${userId}`,
      { headers: getAuthHeaders() },
    );
    studentModalList.value = response.data.data;
  } catch (err) {
    console.error('Failed to load students for teacher', err);
  } finally {
    studentModalLoading.value = false;
  }
}

/**
 * // Description: closeStudentModal - Resets modal state so it can be cleanly reopened.
 */
function closeStudentModal() {
  isStudentModalOpen.value = false;
  studentModalList.value = [];
}

function openCreateModal() {
  form.value = {
    name: '', employee_id: '',
    grade_level: '', section: '',
    password: '', password_confirmation: ''
  };
  formError.value = '';
  createPhotoFile.value = null;
  createPhotoFileName.value = '';
  createPhotoError.value = '';
  if (createPhotoInput.value) {
    createPhotoInput.value.value = '';
  }
  showCreateModal.value = true;
}

/**
 * @param {Object} t - The teacher record from the list
 */
function openEditModal(t) {
  editTargetId.value = t.id;
  editForm.value = {
    name: t.name || '',
    employee_id: t.employee_id || '',
    grade_level: t.grade_level || '',
    section: t.section || '',
    profile_photo: t.profile_photo || null,
    password: '',
    password_confirmation: '',
  };
  editError.value = '';
  editPhotoFile.value = null;
  editPhotoFileName.value = '';
  editPhotoError.value = '';
  if (editPhotoInput.value) {
    editPhotoInput.value.value = '';
  }
  showEditModal.value = true;
}

function confirmDelete(t) {
  deleteTarget.value = t;
  deleteError.value = '';
  showDeleteModal.value = true;
}

/**
 * debouncedTeacherSearch
 * PURPOSE: Debounces server-side teacher search when the toolbar query changes.
 * WHY: Avoids hammering the API on every keystroke while keeping lists scalable.
 */
function debouncedTeacherSearch() {
  if (teacherSearchTimer) clearTimeout(teacherSearchTimer);
  teacherSearchTimer = setTimeout(() => {
    teacherListPage.value = 1;
    load();
  }, 350);
}

/**
 * goTeacherPage
 * PURPOSE: Changes the server-side teacher list page.
 * WHY: Pagination keeps payloads small for large faculties.
 *
 * @param {number} page 1-based page index.
 */
function goTeacherPage(page) {
  if (page < 1 || page > teacherLastPage.value) return;
  teacherListPage.value = page;
  load();
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    const [res] = await Promise.all([
      fetchTeachers({
        page: teacherListPage.value,
        per_page: teacherPerPage.value,
        search: searchQuery.value.trim() || undefined,
      }),
      loadSchoolSections(),
    ]);
    if (Array.isArray(res)) {
      teachers.value = res;
      teacherLastPage.value = 1;
      teacherTotal.value = res.length;
      teacherListPage.value = 1;
    } else {
      teachers.value = res?.data ?? [];
      teacherLastPage.value = res?.last_page ?? 1;
      teacherTotal.value = res?.total ?? 0;
      teacherListPage.value = res?.current_page ?? teacherListPage.value;
    }
    photoLoadError.value = {};
  } catch {
    teachers.value = [];
    loadError.value = 'Failed to load teachers. Please refresh and try again.';
  } finally {
    loading.value = false;
  }
}

async function handleExport() {
  if (exporting.value) return;
  exporting.value = true;
  try {
    const blob = await exportAdminTeachers();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = 'teachers_export.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    alert('Failed to export teachers.');
  } finally {
    exporting.value = false;
  }
}

/**
 * openEhrisModal
 * PURPOSE: Opens the EHRIS preview modal and loads the roster for the admin’s school.
 * WHY: Lets admins review EHRIS rows before syncing to ScanUp.
 */
function openEhrisModal() {
  ehrisModalError.value = '';
  ehrisSearchInput.value = '';
  ehrisSelectedIds.value = [];
  showEhrisModal.value = true;
  loadEhrisPreview();
}

/**
 * closeEhrisModal
 * PURPOSE: Closes the EHRIS modal and clears transient errors.
 * WHY: Avoids stale error text when reopening the preview.
 */
function closeEhrisModal() {
  showEhrisModal.value = false;
  ehrisModalError.value = '';
}

/**
 * loadEhrisPreview
 * PURPOSE: Calls GET /api/admin/teachers/ehris to list active EHRIS teachers for this school.
 * WHY: Read-only preview with optional search before POST sync.
 */
async function loadEhrisPreview() {
  ehrisModalLoading.value = true;
  ehrisModalError.value = '';
  try {
    const res = await fetchEhrisTeachers(ehrisSearchInput.value.trim());
    ehrisDepedSchoolId.value = res.deped_school_id || '';
    ehrisRows.value = Array.isArray(res.data) ? res.data : [];
    ehrisSelectedIds.value = ehrisSelectedIds.value.filter((id) =>
      ehrisRows.value.some((r) => r.employee_id === id),
    );
  } catch (err) {
    ehrisRows.value = [];
    ehrisModalError.value =
      err.response?.data?.message || err.response?.data?.error || 'Failed to load EHRIS teachers.';
  } finally {
    ehrisModalLoading.value = false;
  }
}

/**
 * toggleSelectAllEhris
 * PURPOSE: Selects or clears all visible EHRIS preview rows.
 * WHY: Faster bulk selection before “Sync selected”.
 *
 * @param {boolean} checked - Header checkbox state.
 */
function toggleSelectAllEhris(checked) {
  if (checked) {
    ehrisSelectedIds.value = ehrisRows.value.map((r) => r.employee_id);
  } else {
    ehrisSelectedIds.value = [];
  }
}

/**
 * toggleEhrisRow
 * PURPOSE: Toggles one EHRIS row in the sync selection set.
 * WHY: POST sync-ehris accepts a subset via employee_ids.
 *
 * @param {string} employeeId - Resolved EHRIS employee key (hrId or userId).
 * @param {boolean} checked - Row checkbox state.
 */
function toggleEhrisRow(employeeId, checked) {
  const set = new Set(ehrisSelectedIds.value);
  if (checked) {
    set.add(employeeId);
  } else {
    set.delete(employeeId);
  }
  ehrisSelectedIds.value = [...set];
}

/**
 * applyEhrisSyncResult
 * PURPOSE: Stores counts from POST sync-ehris for the banner under the toolbar.
 * WHY: Trainers and admins need visible created/updated/synced totals without alerts only.
 *
 * @param {object} res - JSON body from sync-ehris.
 */
function applyEhrisSyncResult(res) {
  lastSyncSummary.value = {
    synced_count: res.synced_count ?? 0,
    created_count: res.created_count ?? 0,
    updated_count: res.updated_count ?? 0,
    skipped_count: res.skipped_count ?? 0,
    users_created_count: res.users_created_count ?? 0,
    users_updated_count: res.users_updated_count ?? 0,
    at: new Date().toLocaleString(),
  };
}

/**
 * runEhrisSyncAll
 * PURPOSE: POST sync-ehris with no employee_ids (all EHRIS teachers for the school).
 * WHY: One-click import after preview.
 */
async function runEhrisSyncAll() {
  if (syncingEhris.value || !ehrisRows.value.length) return;
  const ok = window.confirm(
    'Sync all active EHRIS teachers for this school into ScanUp (teachers + login users)?',
  );
  if (!ok) return;

  syncingEhris.value = true;
  ehrisModalError.value = '';
  try {
    const res = await syncEhrisTeachers({});
    applyEhrisSyncResult(res);
    await load();
    closeEhrisModal();
  } catch (err) {
    ehrisModalError.value = err.response?.data?.message || 'EHRIS sync failed.';
  } finally {
    syncingEhris.value = false;
  }
}

/**
 * runEhrisSyncSelected
 * PURPOSE: POST sync-ehris with employee_ids for only checked preview rows.
 * WHY: Admins can onboard a subset without importing the whole roster.
 */
async function runEhrisSyncSelected() {
  if (syncingEhris.value) return;
  if (!ehrisSelectedIds.value.length) {
    alert('Select at least one teacher, or use “Sync all in preview”.');
    return;
  }

  syncingEhris.value = true;
  ehrisModalError.value = '';
  try {
    const res = await syncEhrisTeachers({ employee_ids: [...ehrisSelectedIds.value] });
    applyEhrisSyncResult(res);
    await load();
    await loadEhrisPreview();
  } catch (err) {
    ehrisModalError.value = err.response?.data?.message || 'EHRIS sync failed.';
  } finally {
    syncingEhris.value = false;
  }
}

async function submitCreate() {
  formError.value = '';
  if (form.value.password !== form.value.password_confirmation) {
    formError.value = 'Passwords do not match.';
    return;
  }
  try {
    const payload = {
      name: form.value.name,
      employee_id: form.value.employee_id,
      job_title: null,
      grade_level: form.value.grade_level || null,
      section: form.value.section || null,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    };

    const res = await createTeacher(payload);
    const createdId = res?.teacher?.id;

    if (createdId && createPhotoFile.value) {
      try {
        await uploadTeacherPhoto(createdId, createPhotoFile.value);
      } catch (err) {
        createPhotoError.value = err.response?.data?.message || 'Photo upload failed.';
      }
    }

    showCreateModal.value = false;
    await load();
  } catch (err) {
    const msg = err.response?.data?.message || 'Request failed.';
    const errors = err.response?.data?.errors;
    formError.value = errors ? Object.values(errors).flat().join(' ') : msg;
  }
}

async function submitEdit() {
  editError.value = '';
  if (!editTargetId.value) return;

  if ((editForm.value.password || editForm.value.password_confirmation) && editForm.value.password !== editForm.value.password_confirmation) {
    editError.value = 'Passwords do not match.';
    return;
  }

  const payload = {
    name: editForm.value.name,
    employee_id: editForm.value.employee_id,
    grade_level: editForm.value.grade_level || null,
    section: editForm.value.section || null,
  };
  if (editForm.value.password) {
    payload.password = editForm.value.password;
    payload.password_confirmation = editForm.value.password_confirmation;
  }

  try {
    await updateTeacher(editTargetId.value, payload);

    if (editTargetId.value && editPhotoFile.value) {
      try {
        await uploadTeacherPhoto(editTargetId.value, editPhotoFile.value);
      } catch (err) {
        editPhotoError.value = err.response?.data?.message || 'Photo upload failed.';
      }
    }

    showEditModal.value = false;
    editTargetId.value = null;
    await load();
  } catch (err) {
    const msg = err.response?.data?.message || 'Request failed.';
    const errors = err.response?.data?.errors;
    editError.value = errors ? Object.values(errors).flat().join(' ') : msg;
  }
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  deleting.value = true;
  deleteError.value = '';
  try {
    await deleteTeacher(deleteTarget.value.id);
    showDeleteModal.value = false;
    deleteTarget.value = null;
    await load();
  } catch (err) {
    deleteError.value = err.response?.data?.message || 'Delete failed.';
  } finally {
    deleting.value = false;
  }
}

function onCreatePhotoChange(e) {
  const file = e.target.files?.[0];
  createPhotoError.value = '';
  if (file) {
    if (!['image/png', 'image/jpeg'].includes(file.type)) {
      createPhotoError.value = 'Only JPG or PNG images are accepted.';
      createPhotoFile.value = null;
      createPhotoFileName.value = '';
      if (createPhotoInput.value) createPhotoInput.value.value = '';
      return;
    }
    createPhotoFile.value = file;
    createPhotoFileName.value = file.name;
  } else {
    createPhotoFile.value = null;
    createPhotoFileName.value = '';
  }
}

function onEditPhotoChange(e) {
  const file = e.target.files?.[0];
  editPhotoError.value = '';
  if (file) {
    if (!['image/png', 'image/jpeg'].includes(file.type)) {
      editPhotoError.value = 'Only JPG or PNG images are accepted.';
      editPhotoFile.value = null;
      editPhotoFileName.value = '';
      if (editPhotoInput.value) editPhotoInput.value.value = '';
      return;
    }
    editPhotoFile.value = file;
    editPhotoFileName.value = file.name;
  } else {
    editPhotoFile.value = null;
    editPhotoFileName.value = '';
  }
}

onMounted(async () => {
  await load();
  const flag = sessionStorage.getItem('admin_open_create_teacher');
  if (flag) {
    sessionStorage.removeItem('admin_open_create_teacher');
    openCreateModal();
  }
});
</script>
