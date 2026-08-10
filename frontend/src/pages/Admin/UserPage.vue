<template>
  <q-page class="user-page">
    <!-- ── Header ─────────────────────────────────────────── -->
    <div class="user-page__header">
      <div>
        <div class="text-h5 user-page__title">User Management</div>
        <div class="user-page__subtitle">Manage system accounts and roles</div>
      </div>
      <q-btn
        class="clay-btn clay-btn--primary"
        label="New User"
        icon="person_add"
        unelevated
        no-caps
        @click="openCreateDialog"
      />
    </div>

    <!-- ── Toolbar ─────────────────────────────────────────── -->
    <div class="user-page__toolbar">
      <q-input
        v-model="search"
        dense
        outlined
        clearable
        placeholder="Search users..."
        class="user-page__search"
      >
        <template #prepend>
          <q-icon name="search" />
        </template>
      </q-input>

      <div class="user-page__stats">
        <div class="user-page__stat-chip">
          <q-icon name="people" size="16px" />
          <span>{{ rows.length }} total</span>
        </div>
        <div class="user-page__stat-chip user-page__stat-chip--active">
          <q-icon name="check_circle" size="16px" />
          <span>{{ activeCount }} active</span>
        </div>
        <div class="user-page__stat-chip user-page__stat-chip--pending">
          <q-icon name="block" size="16px" />
          <span>{{ pendingCount }} suspended</span>
        </div>
      </div>
    </div>

    <!-- ── Table ───────────────────────────────────────────── -->
    <q-table
      class="clay-table"
      :rows="rows"
      :columns="columns"
      row-key="id"
      :loading="loading"
      :filter="search"
      :pagination="pagination"
      flat
    >
      <template #body-cell-name="props">
        <q-td :props="props">
          <div class="user-page__user-cell">
            <div class="user-page__avatar">
              {{ getInitials(props.row) }}
            </div>
            <div>
              <div class="user-page__user-name">{{ props.value }}</div>
              <div class="user-page__user-division">{{ props.row.division }}</div>
            </div>
          </div>
        </q-td>
      </template>

      <template #body-cell-status="props">
        <q-td :props="props">
          <q-badge
            :class="['status-badge', `status-badge--${props.value?.toLowerCase()}`]"
            :label="props.value"
          />
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props" class="text-center">
          <q-btn
            round flat dense
            icon="edit"
            size="sm"
            color="primary"
            @click="openEditDialog(props.row)"
          >
            <q-tooltip>Edit User</q-tooltip>
          </q-btn>
          <q-btn
            round flat dense
            icon="delete_outline"
            size="sm"
            color="negative"
            @click="confirmDelete(props.row)"
          >
            <q-tooltip>Delete User</q-tooltip>
          </q-btn>
        </q-td>
      </template>

      <template #no-data>
        <div class="user-page__empty">
          <q-icon name="group_off" size="48px" color="grey-5" />
          <p>No users found</p>
        </div>
      </template>
    </q-table>

    <!-- ── Create / Edit Dialog ────────────────────────────── -->
    <q-dialog v-model="showDialog" persistent>
      <q-card class="user-page__dialog">
        <q-card-section class="user-page__dialog-head">
          <q-icon
            :name="isEditing ? 'edit_note' : 'person_add'"
            size="28px"
            color="primary"
          />
          <span class="user-page__dialog-title">
            {{ isEditing ? 'Edit User' : 'New User' }}
          </span>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="user-page__dialog-body">
          <div class="user-page__form-row">
            <q-input
              v-model="form.first_name"
              label="First Name *"
              outlined dense
              :rules="[val => !!val || 'Required']"
            />
            <q-input
              v-model="form.last_name"
              label="Last Name *"
              outlined dense
              :rules="[val => !!val || 'Required']"
            />
          </div>

          <q-input
            v-model="form.email"
            label="Email Address *"
            outlined dense
            type="email"
            class="q-mt-sm"
            :rules="[val => !!val || 'Required', val => /.+@.+\..+/.test(val) || 'Invalid email']"
          />

          <div class="user-page__form-row q-mt-sm">
            <q-select
              v-model="form.role"
              :options="roleOptions"
              label="Role *"
              outlined dense
              emit-value
              map-options
              :rules="[val => !!val || 'Required']"
            />
            <q-select
              v-model="form.status"
              :options="statusOptions"
              label="Status *"
              outlined dense
              emit-value
              map-options
              :rules="[val => !!val || 'Required']"
            />
          </div>

          <div class="user-page__form-row q-mt-sm">
            <q-input
              v-model="form.division"
              label="Division"
              outlined dense
            />
            <q-input
              v-model="form.sections"
              label="Sections"
              outlined dense
            />
          </div>

          <q-input
            v-model="form.position"
            label="Position"
            outlined dense
            class="q-mt-sm"
          />

          <q-input
            v-if="!isEditing"
            v-model="form.password"
            label="Password *"
            outlined dense
            type="password"
            class="q-mt-sm"
            :rules="[val => !isEditing && !!val || 'Required', val => val?.length >= 8 || 'Min 8 characters']"
          />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="user-page__dialog-actions">
          <q-btn flat no-caps label="Cancel" color="grey-7" v-close-popup />
          <q-btn
            unelevated no-caps
            :label="isEditing ? 'Save Changes' : 'Create User'"
            class="clay-btn clay-btn--primary"
            :loading="saving"
            @click="submitForm"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ── Delete Confirm Dialog ───────────────────────────── -->
    <q-dialog v-model="showDeleteDialog" persistent>
      <q-card class="user-page__dialog user-page__dialog--danger">
        <q-card-section class="user-page__dialog-head">
          <q-icon name="warning_amber" size="28px" color="negative" />
          <span class="user-page__dialog-title">Delete User</span>
        </q-card-section>

        <q-card-section class="user-page__dialog-body">
          <p>Are you sure you want to delete <strong>{{ deleteTarget?.first_name }} {{ deleteTarget?.last_name }}</strong>?</p>
          <p class="user-page__delete-warn">This action cannot be undone.</p>
        </q-card-section>

        <q-card-actions align="right" class="user-page__dialog-actions">
          <q-btn flat no-caps label="Cancel" color="grey-7" v-close-popup />
          <q-btn
            unelevated no-caps
            label="Delete"
            color="negative"
            :loading="deleting"
            @click="deleteUser"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'
import './UserPage.scss'
const $q = useQuasar()

// ── State ────────────────────────────────────────────────────
const loading  = ref(false)
const saving   = ref(false)
const deleting = ref(false)
const search   = ref('')
const rows     = ref([])

const showDialog       = ref(false)
const showDeleteDialog = ref(false)
const isEditing        = ref(false)
const deleteTarget     = ref(null)

const pagination = ref({ sortBy: 'first_name', descending: false, page: 1, rowsPerPage: 10 })

const emptyForm = () => ({
  first_name: '',
  last_name:  '',
  email:      '',
  role:       '',
  status:     'active',
  division:   '',
  sections:   '',
  position:   '',
  password:   '',
})
const form = ref(emptyForm())
let editingId = null

// ── Options ──────────────────────────────────────────────────
const roleOptions   = ['ADMIN', 'STAFF', 'USER'].map(v => ({ label: v, value: v }))
const statusOptions = [
  { label: 'Active',    value: 'ACTIVE' },
  { label: 'Inactive',  value: 'INACTIVE' },
  { label: 'Suspended', value: 'SUSPENDED' },
  { label: 'Archived',  value: 'ARCHIVED' },
]

// ── Computed ─────────────────────────────────────────────────
const activeCount  = computed(() => rows.value.filter(r => r.status?.toUpperCase() === 'ACTIVE').length)
const pendingCount = computed(() => rows.value.filter(r => r.status?.toUpperCase() === 'SUSPENDED').length)

// ── Columns ──────────────────────────────────────────────────
const columns = [
  {
    name: 'name', label: 'Name', align: 'left', sortable: true,
    field: row => `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim(),
  },
  { name: 'email',    label: 'Email',    field: 'email',    align: 'left', sortable: true },
  { name: 'sections', label: 'Sections', field: 'sections', align: 'left' },
  { name: 'role',     label: 'Role',     field: 'role',     align: 'left', sortable: true },
  { name: 'position', label: 'Position', field: 'position', align: 'left' },
  { name: 'status',   label: 'Status',   field: 'status',   align: 'center', sortable: true },
  { name: 'actions',  label: 'Actions',  field: 'actions',  align: 'center' },
]

// ── Helpers ──────────────────────────────────────────────────
function getInitials(row) {
  const f = row.first_name?.[0]?.toUpperCase() ?? ''
  const l = row.last_name?.[0]?.toUpperCase() ?? ''
  return f + l
}

function notify(type, message) {
  $q.notify({ type, message, position: 'top-right', timeout: 2500 })
}

// ── CRUD ─────────────────────────────────────────────────────
async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await api.get('/users')
    rows.value = data
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
    first_name: row.first_name,
    last_name:  row.last_name,
    email:      row.email,
    role:       row.role?.toUpperCase(),
    status:     row.status?.toUpperCase(),
    division:   row.division,
    sections:   row.sections,
    position:   row.position,
    password:   '',
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

onMounted(fetchUsers)
</script>

<style lang="scss" scoped>
</style>
