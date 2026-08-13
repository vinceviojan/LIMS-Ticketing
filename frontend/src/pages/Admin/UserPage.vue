<template>
  <q-page id="user-management-page" class="user-page q-pa-lg bg-grey-1">

    <!-- ── Header ──────────────────────────────────────────────── -->
    <div id="user-management-header" class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bolder text-dark">User Management</div>
        <div class="text-caption text-grey-7 q-mt-xs">Review, filter, assign roles and manage system user accounts</div>
      </div>

      <div class="row q-gutter-sm">
        <q-btn-dropdown
          id="export-dropdown-btn"
          class="clay-btn"
          label="Export"
          icon="file_download"
          unelevated
          no-caps
        >
          <q-list>
            <q-item id="export-csv-item" clickable v-close-popup @click="exportUsers('csv')">
              <q-item-section avatar><q-icon name="grid_on" color="primary" /></q-item-section>
              <q-item-section>Export CSV</q-item-section>
            </q-item>
            <q-item id="export-json-item" clickable v-close-popup @click="exportUsers('json')">
              <q-item-section avatar><q-icon name="data_object" color="primary" /></q-item-section>
              <q-item-section>Export JSON</q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>

        <q-btn
          id="new-user-btn"
          color="primary"
          label="New User"
          icon="person_add"
          unelevated
          no-caps
          class="border-radius-8 text-weight-bold"
          @click="openCreateDialog"
        />
      </div>
    </div>

    <!-- ── Role Tabs ───────────────────────────────────────────── -->
    <div id="role-tabs" class="row q-gutter-sm q-mb-lg">
      <q-btn
        v-for="tab in roleTabs"
        :key="tab.value"
        :id="'role-tab-' + tab.value.toLowerCase()"
        :color="activeRoleTab === tab.value ? 'primary' : 'grey-8'"
        :flat="activeRoleTab !== tab.value"
        :unelevated="activeRoleTab === tab.value"
        no-caps
        class="border-radius-8 text-weight-bold"
        style="padding: 4px 16px;"
        @click="activeRoleTab = tab.value"
      >
        <q-icon :name="tab.icon" size="18px" class="q-mr-sm" />
        {{ tab.label }}
        <q-badge
          :color="activeRoleTab === tab.value ? 'white' : 'grey-3'"
          :text-color="activeRoleTab === tab.value ? 'primary' : 'grey-8'"
          class="q-ml-sm text-weight-bolder"
        >
          {{ tabCount(tab.value) }}
        </q-badge>
      </q-btn>
    </div>

    <!-- ── Filter & Search Toolbar ─────────────────────────────── -->
    <div id="user-toolbar" class="row items-center q-gutter-md q-mb-lg flex-wrap">
      <!-- Search Input -->
      <q-input
        id="user-search-input"
        v-model="search"
        dense outlined clearable
        placeholder="Search users..."
        bg-color="white"
        class="col-12 col-sm-auto border-radius-8"
        style="min-width: 240px;"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>

      <!-- Division Filter Dropdown -->
      <q-select
        id="division-filter-select"
        v-model="filterDivision"
        :options="divisionFilterOptions"
        label="Division"
        dense outlined clearable
        emit-value map-options
        bg-color="white"
        class="col-12 col-sm-auto border-radius-8"
        style="min-width: 180px;"
        @update:model-value="onFilterDivisionChange"
      />

      <!-- Section Filter Dropdown -->
      <q-select
        id="section-filter-select"
        v-model="filterSection"
        :options="sectionFilterOptions"
        label="Section"
        dense outlined clearable
        emit-value map-options
        bg-color="white"
        class="col-12 col-sm-auto border-radius-8"
        style="min-width: 180px;"
      />

      <!-- Status Filter Dropdown -->
      <q-select
        id="status-filter-select"
        v-model="filterStatus"
        :options="statusFilterOptions"
        label="Status"
        dense outlined clearable
        emit-value map-options
        bg-color="white"
        class="col-12 col-sm-auto border-radius-8"
        style="min-width: 150px;"
      />

      <!-- Reset Filters Button -->
      <q-btn
        v-if="hasActiveFilters"
        id="reset-filters-btn"
        flat dense no-caps
        color="negative"
        icon="restart_alt"
        label="Reset"
        class="q-px-sm border-radius-8"
        @click="resetFilters"
      />

      <q-space />

      <!-- Display Mode Toggle -->
      <q-btn-group id="display-mode-toggle" outline class="bg-white border-radius-8">
        <q-btn
          id="display-mode-card-btn"
          :color="displayMode === 'card' ? 'primary' : 'grey-7'"
          :flat="displayMode !== 'card'"
          unelevated icon="grid_view"
          @click="displayMode = 'card'"
        />
        <q-btn
          id="display-mode-table-btn"
          :color="displayMode === 'table' ? 'primary' : 'grey-7'"
          :flat="displayMode !== 'table'"
          unelevated icon="list"
          @click="displayMode = 'table'"
        />
      </q-btn-group>
    </div>

    <!-- ── Loading State (Matching Ticket Management) ────────── -->
    <div v-if="loading" class="column items-center justify-center q-pa-xl text-grey-6 bg-white rounded-lg" style="border: 1px solid #dbe2ea; min-height: 280px;">
      <q-spinner-dots size="52px" color="primary" />
      <p class="text-h6 q-mt-md text-weight-medium text-grey-7">Loading users…</p>
    </div>

    <!-- ── Table View (Clean Columns: Name, Section, Role, Status, Action) ── -->
    <q-card v-else-if="displayMode === 'table' && filteredUsers.length" flat bordered class="rounded-lg bg-white overflow-hidden">
      <q-table
        :rows="paginatedUsers"
        :columns="columns"
        row-key="id"
        :loading="loading"
        hide-bottom
        :pagination="{ rowsPerPage: 0 }"
        flat
        @row-click="(evt, row) => openViewModal(row)"
      >
        <!-- Name Cell -->
        <template #body-cell-name="props">
          <q-td :props="props" class="cursor-pointer">
            <div class="text-weight-bold text-dark" style="font-size: 0.92rem;">{{ props.value }}</div>
          </q-td>
        </template>

        <!-- Section Cell -->
        <template #body-cell-section="props">
          <q-td :props="props" class="cursor-pointer">
            <span class="text-weight-medium text-grey-9">{{ props.value }}</span>
          </q-td>
        </template>

        <!-- Role Cell -->
        <template #body-cell-role="props">
          <q-td :props="props" class="cursor-pointer">
            <q-chip
              dense
              :color="getRoleBadgeBg(props.value)"
              :text-color="getRoleBadgeColor(props.value)"
              class="text-weight-bold text-caption"
            >
              <q-icon :name="getRoleIcon(props.value)" size="14px" class="q-mr-xs" />
              {{ props.value }}
            </q-chip>
          </q-td>
        </template>

        <!-- Status Cell -->
        <template #body-cell-status="props">
          <q-td :props="props" class="cursor-pointer">
            <q-badge
              :color="getStatusColor(props.value)"
              class="text-weight-bold q-pa-xs q-px-sm"
              style="border-radius: 6px;"
            >
              {{ props.value }}
            </q-badge>
          </q-td>
        </template>

        <!-- Actions Cell -->
        <template #body-cell-actions="props">
          <q-td :props="props" class="text-center" @click.stop>
            <q-btn
              flat round dense
              icon="visibility"
              size="sm"
              color="grey-7"
              @click="openViewModal(props.row)"
            >
              <q-tooltip>View Details</q-tooltip>
            </q-btn>
            <q-btn
              flat round dense
              icon="edit"
              size="sm"
              color="primary"
              @click="openEditDialog(props.row)"
            >
              <q-tooltip>Edit User</q-tooltip>
            </q-btn>
            <q-btn
              flat round dense
              icon="delete_outline"
              size="sm"
              color="negative"
              @click="confirmDelete(props.row)"
            >
              <q-tooltip>Delete User</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- ── Card Grid View (3 per row) ──────────────────────────── -->
    <div v-else-if="displayMode === 'card' && filteredUsers.length" class="row q-col-gutter-lg">
      <div v-for="user in paginatedUsers" :key="user.id" class="col-12 col-sm-6 col-md-4">
        <q-card
          flat bordered
          class="user-card rounded-lg bg-white full-height column justify-between cursor-pointer"
          style="overflow: hidden;"
          @click="openViewModal(user)"
        >
          <q-card-section class="q-pa-md col flex column justify-between">
            <div>
              <!-- Header -->
              <div class="row items-start justify-between no-wrap gap-sm q-mb-xs">
                <div class="col ellipsis" style="min-width: 0;">
                  <div class="text-subtitle1 text-weight-bold text-dark ellipsis" :title="user.first_name + ' ' + user.last_name" style="line-height: 1.3;">
                    {{ user.first_name }} {{ user.last_name }}
                  </div>
                  <div class="text-caption text-grey-7 ellipsis" :title="user.email" style="font-size: 0.78rem;">
                    {{ user.email }}
                  </div>
                </div>

                <div class="row items-center gap-xs flex-shrink-0">
                  <q-chip
                    dense
                    :color="getRoleBadgeBg(user.role)"
                    :text-color="getRoleBadgeColor(user.role)"
                    class="text-weight-bold"
                    style="font-size: 0.7rem; margin: 0;"
                  >
                    {{ user.role }}
                  </q-chip>
                  <q-badge
                    :color="getStatusColor(user.status)"
                    class="text-weight-bold"
                    style="font-size: 0.68rem; border-radius: 4px; padding: 4px 6px;"
                  >
                    {{ user.status }}
                  </q-badge>
                </div>
              </div>

              <q-separator class="q-my-sm" />

              <!-- Section & Position -->
              <div class="column gap-xs text-caption text-grey-7 q-py-xs">
                <div class="row items-center gap-xs no-wrap" style="min-width: 0;">
                  <q-icon name="apartment" size="16px" color="grey-6" class="flex-shrink-0" />
                  <span class="ellipsis text-weight-medium text-grey-9" :title="user.section?.name || 'No Section'">
                    {{ user.section?.name || 'No Section' }}
                  </span>
                </div>
                <div class="row items-center gap-xs no-wrap" v-if="user.position" style="min-width: 0;">
                  <q-icon name="badge" size="16px" color="grey-6" class="flex-shrink-0" />
                  <span class="ellipsis text-grey-7" :title="user.position">{{ user.position }}</span>
                </div>
              </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="row items-center justify-end gap-xs q-pt-sm dashed-top q-mt-sm" @click.stop>
              <q-btn
                flat dense no-caps
                icon="visibility" label="View" color="grey-7" size="sm"
                @click="openViewModal(user)"
              />
              <q-btn
                flat dense no-caps
                icon="edit" label="Edit" color="primary" size="sm"
                @click="openEditDialog(user)"
              />
              <q-btn
                flat dense no-caps
                icon="delete_outline" label="Delete" color="negative" size="sm"
                @click="confirmDelete(user)"
              />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Empty State -->
    <q-card v-else flat bordered class="rounded-lg text-center q-pa-xl bg-white">
      <q-icon name="group_off" size="56px" color="grey-4" />
      <div class="text-h6 text-grey-6 q-mt-md">No users found</div>
      <div class="text-caption text-grey-5 q-mt-xs">Try adjusting your filters or search terms.</div>
    </q-card>

    <!-- ── Unified Pagination Bar ─────────────────────────────── -->
    <div
      v-if="filteredUsers.length > perPage"
      class="row items-center justify-between q-mt-lg bg-white q-pa-md border-radius-12"
      style="border: 1px solid #dbe2ea;"
    >
      <div class="text-caption text-weight-medium text-grey-7">
        Showing {{ ((currentPage - 1) * perPage) + 1 }} - {{ Math.min(currentPage * perPage, filteredUsers.length) }} of {{ filteredUsers.length }} users
      </div>
      <q-pagination
        v-model="currentPage"
        :max="maxPages"
        :max-pages="5"
        boundary-numbers
        direction-links
        boundary-links
        color="primary"
        active-design="solid"
        active-color="primary"
      />
    </div>

    <!-- ── View User Modal ────────────────────────────────────── -->
    <ViewUserModal
      v-model="showViewDialog"
      :user="selectedUserForModal"
      @edit="openEditDialog"
    />

    <!-- ── Create / Edit Dialog ────────────────────────────── -->
    <q-dialog v-model="showDialog" persistent transition-show="scale" transition-hide="scale">
      <q-card style="width: 580px; max-width: 95vw; border-radius: 16px;">
        <q-card-section class="bg-white q-pa-md row items-center justify-between border-bottom">
          <div class="row items-center gap-sm">
            <div class="modal-icon-bg">
              <q-icon :name="isEditing ? 'edit_note' : 'person_add'" size="22px" color="primary" />
            </div>
            <div>
              <div class="text-subtitle1 text-weight-bold text-grey-10 line-height-tight">
                {{ isEditing ? 'Edit User Account' : 'Create New User' }}
              </div>
              <div class="text-caption text-grey-6">
                {{ isEditing ? 'Update account details and section assignment' : 'Add a new member to the system' }}
              </div>
            </div>
          </div>
          <q-btn flat round dense icon="close" color="grey-6" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pa-lg">
          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">First Name <span class="text-negative">*</span></label>
              <q-input
                v-model="form.first_name"
                outlined dense
                placeholder="E.g. John"
                :rules="[val => !!val || 'Required']"
                bg-color="white"
              />
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Last Name <span class="text-negative">*</span></label>
              <q-input
                v-model="form.last_name"
                outlined dense
                placeholder="E.g. Dela Cruz"
                :rules="[val => !!val || 'Required']"
                bg-color="white"
              />
            </div>
          </div>

          <div class="q-mb-md">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Email Address <span class="text-negative">*</span></label>
            <q-input
              v-model="form.email"
              outlined dense
              type="email"
              placeholder="user@lims.gov.ph"
              :rules="[val => !!val || 'Required', val => /.+@.+\..+/.test(val) || 'Invalid email']"
              bg-color="white"
            >
              <template #prepend><q-icon name="email" color="grey-5" size="20px" /></template>
            </q-input>
          </div>

          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Role <span class="text-negative">*</span></label>
              <q-select
                v-model="form.role"
                :options="roleOptions"
                outlined dense emit-value map-options
                bg-color="white"
              >
                <template #prepend><q-icon name="badge" color="grey-5" size="20px" /></template>
              </q-select>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Status <span class="text-negative">*</span></label>
              <q-select
                v-model="form.status"
                :options="statusOptions"
                outlined dense emit-value map-options
                bg-color="white"
              >
                <template #prepend><q-icon name="toggle_on" color="grey-5" size="20px" /></template>
              </q-select>
            </div>
          </div>

          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Division</label>
              <q-select
                v-model="form.division_id"
                :options="divisionOptions"
                outlined dense emit-value map-options clearable
                placeholder="Select Division"
                bg-color="white"
                @update:model-value="onDivisionChange"
              >
                <template #prepend><q-icon name="domain" color="grey-5" size="20px" /></template>
              </q-select>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Section</label>
              <q-select
                v-model="form.section_id"
                :options="filteredSectionOptions"
                outlined dense emit-value map-options clearable
                placeholder="Select Section"
                bg-color="white"
              >
                <template #prepend><q-icon name="apartment" color="grey-5" size="20px" /></template>
              </q-select>
            </div>
          </div>

          <div class="q-mb-md">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Position</label>
            <q-input
              v-model="form.position"
              outlined dense
              placeholder="E.g. Chemist II, Agriculturist"
              bg-color="white"
            >
              <template #prepend><q-icon name="work_outline" color="grey-5" size="20px" /></template>
            </q-input>
          </div>

          <div v-if="!isEditing" class="q-mb-md">
            <label class="form-label text-weight-bold text-grey-8 block q-mb-xs">Password <span class="text-negative">*</span></label>
            <q-input
              v-model="form.password"
              outlined dense type="password"
              placeholder="Minimum 8 characters"
              :rules="[val => !isEditing && !!val || 'Required', val => val?.length >= 8 || 'Min 8 characters']"
              bg-color="white"
            >
              <template #prepend><q-icon name="lock" color="grey-5" size="20px" /></template>
            </q-input>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md bg-grey-1 gap-sm">
          <q-btn flat no-caps label="Cancel" color="grey-7" v-close-popup />
          <q-btn
            unelevated no-caps
            color="primary"
            icon="save"
            :label="isEditing ? 'Save Changes' : 'Create User'"
            class="text-weight-medium"
            :loading="saving"
            @click="submitForm"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ── Delete Confirm Dialog ───────────────────────────── -->
    <q-dialog v-model="showDeleteDialog" persistent>
      <q-card style="min-width: 400px; border-radius: 12px;">
        <q-card-section class="row items-center q-pb-xs">
          <div class="text-subtitle1 text-weight-bold text-negative flex items-center gap-xs">
            <q-icon name="warning_amber" size="sm" class="q-mr-xs" /> Delete User
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-sm">
          <p class="text-body2 text-grey-9">Are you sure you want to delete <strong>{{ deleteTarget?.first_name }} {{ deleteTarget?.last_name }}</strong>?</p>
          <p class="text-caption text-negative text-weight-bold">This action cannot be undone.</p>
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat no-caps label="Cancel" color="grey-7" v-close-popup />
          <q-btn
            unelevated no-caps
            label="Delete User"
            color="negative"
            icon="delete"
            :loading="deleting"
            @click="deleteUser"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'
import ViewUserModal from '../../components/ViewUserModal.vue'
import './UserPage.scss'

const $q = useQuasar()

// ── State ────────────────────────────────────────────────────
const loading        = ref(true)
const saving         = ref(false)
const deleting       = ref(false)
const search         = ref('')
const rows           = ref([])
const activeRoleTab  = ref('ALL')
const filterDivision = ref(null)
const filterSection  = ref(null)
const filterStatus   = ref(null)
const displayMode    = ref('table')

const showDialog            = ref(false)
const showViewDialog        = ref(false)
const selectedUserForModal  = ref(null)
const showDeleteDialog      = ref(false)
const isEditing             = ref(false)
const deleteTarget          = ref(null)

const divisionsList    = ref([])
const sectionsList     = ref([])

// ── Pagination State ─────────────────────────────────────────
const currentPage = ref(1)
const perPage = ref(12)

const roleTabs = [
  { label: 'All Users', value: 'ALL',   icon: 'people'          },
  { label: 'Admin',     value: 'ADMIN', icon: 'admin_panel_settings' },
  { label: 'Staff',     value: 'STAFF', icon: 'support_agent'   },
  { label: 'Users',     value: 'USER',  icon: 'person'          },
]

const emptyForm = () => ({
  first_name:  '',
  last_name:   '',
  email:       '',
  role:        'USER',
  status:      'ACTIVE',
  division_id: null,
  section_id:  null,
  position:    '',
  password:    '',
})

const form = ref(emptyForm())
let editingId = null

// ── Options ──────────────────────────────────────────────────
const roleOptions = [
  { label: 'ADMIN', value: 'ADMIN' },
  { label: 'STAFF', value: 'STAFF' },
  { label: 'USER',  value: 'USER'  },
]

const statusOptions = [
  { label: 'ACTIVE',    value: 'ACTIVE' },
  { label: 'INACTIVE',  value: 'INACTIVE' },
  { label: 'SUSPENDED', value: 'SUSPENDED' },
  { label: 'ARCHIVED',  value: 'ARCHIVED' },
]

const statusFilterOptions = [
  { label: 'Active',    value: 'ACTIVE' },
  { label: 'Inactive',  value: 'INACTIVE' },
  { label: 'Suspended', value: 'SUSPENDED' },
  { label: 'Archived',  value: 'ARCHIVED' },
]

const divisionOptions = computed(() => divisionsList.value.map(d => ({ label: d.name, value: d.id })))
const divisionFilterOptions = computed(() => divisionsList.value.map(d => ({ label: d.name, value: d.id })))

const sectionFilterOptions = computed(() => {
  if (!filterDivision.value) {
    return sectionsList.value.map(s => ({ label: s.name, value: s.id }))
  }
  return sectionsList.value
    .filter(s => String(s.division_id) === String(filterDivision.value))
    .map(s => ({ label: s.name, value: s.id }))
})

const filteredSectionOptions = computed(() => {
  if (!form.value.division_id) {
    return sectionsList.value.map(s => ({ label: s.name, value: s.id }))
  }
  return sectionsList.value
    .filter(s => String(s.division_id) === String(form.value.division_id))
    .map(s => ({ label: s.name, value: s.id }))
})

function onFilterDivisionChange() {
  if (filterSection.value) {
    const valid = sectionFilterOptions.value.some(s => String(s.value) === String(filterSection.value))
    if (!valid) filterSection.value = null
  }
}

// ── Combination Filtering ─────────────────────────────────────
const filteredUsers = computed(() => {
  let list = [...rows.value]

  // 1. Role Tab Filter
  if (activeRoleTab.value !== 'ALL') {
    list = list.filter(u => (u.role || '').toUpperCase() === activeRoleTab.value)
  }

  // 2. Division Filter
  if (filterDivision.value !== null && filterDivision.value !== undefined) {
    const divTarget = String(filterDivision.value)
    list = list.filter(u => {
      const uDivId = u.division_id !== null && u.division_id !== undefined ? String(u.division_id) : ''
      const uObjDivId = u.division?.id !== null && u.division?.id !== undefined ? String(u.division.id) : ''
      return uDivId === divTarget || uObjDivId === divTarget || u.division?.name === filterDivision.value
    })
  }

  // 3. Section Filter
  if (filterSection.value !== null && filterSection.value !== undefined) {
    const secTarget = String(filterSection.value)
    list = list.filter(u => {
      const uSecId = u.section_id !== null && u.section_id !== undefined ? String(u.section_id) : ''
      const uObjSecId = u.section?.id !== null && u.section?.id !== undefined ? String(u.section.id) : ''
      return uSecId === secTarget || uObjSecId === secTarget || u.section?.name === filterSection.value
    })
  }

  // 4. Status Filter
  if (filterStatus.value) {
    list = list.filter(u => (u.status || '').toUpperCase() === filterStatus.value.toUpperCase())
  }

  // 5. Text Search
  if (search.value && search.value.trim()) {
    const q = search.value.trim().toLowerCase()
    list = list.filter(u => {
      const fullName = `${u.first_name || ''} ${u.last_name || ''}`.toLowerCase()
      const email = (u.email || '').toLowerCase()
      const position = (u.position || '').toLowerCase()
      const sectionName = (u.section?.name || '').toLowerCase()
      const divisionName = (u.division?.name || '').toLowerCase()
      const role = (u.role || '').toLowerCase()
      return fullName.includes(q) || email.includes(q) || position.includes(q) || sectionName.includes(q) || divisionName.includes(q) || role.includes(q)
    })
  }

  return list
})

// ── Sliced Pagination ────────────────────────────────────────
const maxPages = computed(() => Math.ceil(filteredUsers.value.length / perPage.value) || 1)

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredUsers.value.slice(start, start + perPage.value)
})

watch([search, filterDivision, filterSection, filterStatus, activeRoleTab], () => {
  currentPage.value = 1
})

const hasActiveFilters = computed(() => {
  return Boolean(search.value || filterDivision.value || filterSection.value || filterStatus.value || activeRoleTab.value !== 'ALL')
})

function tabCount(role) {
  if (role === 'ALL') return rows.value.length
  return rows.value.filter(u => (u.role || '').toUpperCase() === role).length
}

function resetFilters() {
  search.value = ''
  filterDivision.value = null
  filterSection.value = null
  filterStatus.value = null
  activeRoleTab.value = 'ALL'
  currentPage.value = 1
}

// ── Table Columns (Clean: Name, Section, Role, Status, Actions) ──
const columns = [
  {
    name: 'name', label: 'Name', align: 'left', sortable: true,
    field: row => `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim(),
  },
  { name: 'section', label: 'Section', field: row => row.section?.name || '—', align: 'left', sortable: true },
  { name: 'role',    label: 'Role',    field: 'role',   align: 'left', sortable: true },
  { name: 'status',  label: 'Status',  field: 'status', align: 'center', sortable: true },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' },
]

// ── Color & Icon Helpers ─────────────────────────────────────
function getRoleBadgeBg(role) {
  const r = (role || '').toUpperCase()
  if (r === 'ADMIN') return 'purple-1'
  if (r === 'STAFF') return 'blue-1'
  return 'teal-1'
}

function getRoleBadgeColor(role) {
  const r = (role || '').toUpperCase()
  if (r === 'ADMIN') return 'purple-9'
  if (r === 'STAFF') return 'blue-9'
  return 'teal-9'
}

function getRoleIcon(role) {
  const r = (role || '').toUpperCase()
  if (r === 'ADMIN') return 'admin_panel_settings'
  if (r === 'STAFF') return 'support_agent'
  return 'person'
}

function getStatusColor(status) {
  const s = (status || '').toUpperCase()
  if (s === 'ACTIVE') return 'positive'
  if (s === 'INACTIVE') return 'grey'
  if (s === 'SUSPENDED') return 'warning'
  return 'grey-7'
}

function notify(type, message) {
  $q.notify({ type, message, position: 'top-right', timeout: 2500 })
}

function onDivisionChange() {
  if (form.value.section_id) {
    const valid = sectionsList.value.some(s => String(s.id) === String(form.value.section_id) && String(s.division_id) === String(form.value.division_id))
    if (!valid) {
      form.value.section_id = null
    }
  }
}

// ── Export Functionality ──────────────────────────────────────
function exportUsers(format) {
  const dataToExport = filteredUsers.value
  if (!dataToExport.length) {
    notify('warning', 'No users available to export.')
    return
  }

  if (format === 'csv') {
    const headers = ['First Name', 'Last Name', 'Email', 'Role', 'Status', 'Section', 'Position']
    const csvRows = [
      headers.join(','),
      ...dataToExport.map(u => [
        `"${u.first_name || ''}"`,
        `"${u.last_name || ''}"`,
        `"${u.email || ''}"`,
        `"${u.role || ''}"`,
        `"${u.status || ''}"`,
        `"${u.section?.name || ''}"`,
        `"${u.position || ''}"`
      ].join(','))
    ]
    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `users_export_${new Date().toISOString().slice(0,10)}.csv`
    link.click()
    notify('positive', 'Users exported to CSV.')
  } else if (format === 'json') {
    const jsonContent = JSON.stringify(dataToExport, null, 2)
    const blob = new Blob([jsonContent], { type: 'application/json' })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `users_export_${new Date().toISOString().slice(0,10)}.json`
    link.click()
    notify('positive', 'Users exported to JSON.')
  }
}

// ── View Modal Trigger ──────────────────────────────────────
function openViewModal(user) {
  selectedUserForModal.value = user
  showViewDialog.value = true
}

// ── CRUD Operations ─────────────────────────────────────────
async function fetchOrganizationData() {
  try {
    const [divRes, secRes] = await Promise.all([
      api.get('/divisions'),
      api.get('/sections')
    ])
    divisionsList.value = divRes.data?.data || divRes.data || []
    sectionsList.value  = secRes.data?.data || secRes.data || []
  } catch (e) {
    console.error('Failed to load division/section structure:', e)
  }
}

async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await api.get('/users')
    rows.value = data.data || data || []
  } catch (e) {
    console.error('fetchUsers error:', e.response ?? e)
    notify('negative', 'Failed to load users.')
  } finally {
    loading.value = false
  }
}

function openCreateDialog() {
  isEditing.value = false
  editingId = null
  form.value = emptyForm()
  showDialog.value = true
}

function openEditDialog(row) {
  isEditing.value = true
  editingId = row.id
  form.value = {
    first_name:  row.first_name,
    last_name:   row.last_name,
    email:       row.email,
    role:        row.role?.toUpperCase(),
    status:      row.status?.toUpperCase(),
    division_id: row.division_id || row.division?.id || null,
    section_id:  row.section_id || row.section?.id || null,
    position:    row.position || '',
    password:    '',
  }
  showDialog.value = true
}

async function submitForm() {
  saving.value = true
  try {
    const payload = { ...form.value }
    if (isEditing.value) {
      delete payload.password
      await api.put(`/users/${editingId}`, payload)
      notify('positive', 'User updated successfully.')
    } else {
      await api.post('/users', payload)
      notify('positive', 'User created successfully.')
    }
    showDialog.value = false
    await fetchUsers()
  } catch (e) {
    const msg = e.response?.data?.message ?? 'Operation failed.'
    notify('negative', msg)
  } finally {
    saving.value = false
  }
}

function confirmDelete(row) {
  deleteTarget.value = row
  showDeleteDialog.value = true
}

async function deleteUser() {
  deleting.value = true
  try {
    await api.delete(`/users/${deleteTarget.value.id}`)
    notify('positive', 'User deleted.')
    showDeleteDialog.value = false
    await fetchUsers()
  } catch {
    notify('negative', 'Failed to delete user.')
  } finally {
    deleting.value = false
    deleteTarget.value = null
  }
}

onMounted(async () => {
  await fetchOrganizationData()
  await fetchUsers()
})
</script>

<style lang="scss" scoped>
.border-radius-12 {
  border-radius: 12px !important;
}

.dashed-top {
  border-top: 1px dashed #e2e8f0;
}
</style>
