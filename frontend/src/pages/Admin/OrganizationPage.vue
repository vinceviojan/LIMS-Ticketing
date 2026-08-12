<template>
  <q-page class="org-page">
    <!-- ── Header ──────────────────────────────────────────────── -->
    <div class="org-page__header">
      <div>
        <div class="text-h5 org-page__title">Organization Management</div>
        <div class="org-page__subtitle">Manage agency divisions and sections</div>
      </div>
      <q-btn
        class="clay-btn clay-btn--primary"
        :label="`New ${activeTabLabel}`"
        icon="add_circle_outline"
        unelevated
        no-caps
        @click="openCreateDialog"
      />
    </div>

    <!-- ── Tabs Navigation ────────────────────────────────────── -->
    <div class="org-page__tabs-container">
      <q-tabs
        v-model="activeTab"
        dense
        class="org-page__tabs"
        active-color="primary"
        indicator-color="primary"
        align="left"
        narrow-indicator
      >
        <q-tab name="divisions" icon="business" label="Divisions" no-caps />
        <q-tab name="sections" icon="groups" label="Sections" no-caps />
      </q-tabs>
    </div>

    <!-- ── Filter Bar ────────────────────────────────────────────── -->
    <div class="org-page__toolbar q-mt-md">
      <q-input
        v-model="search"
        dense
        outlined
        clearable
        placeholder="Search..."
        class="org-page__search"
      >
        <template #prepend>
          <q-icon name="search" />
        </template>
      </q-input>

      <q-select
        v-if="activeTab === 'sections'"
        v-model="sectionDivisionFilter"
        :options="divisionOptions"
        label="Filter by Division"
        dense
        outlined
        clearable
        emit-value
        map-options
        class="org-page__division-filter"
      />

      <div class="org-page__stat-chips">
        <div class="org-stat-chip org-stat-chip--total">
          <q-icon name="layers" size="15px" />
          <span>{{ currentRows.length }} total</span>
        </div>
      </div>
    </div>

    <!-- ── Table ─────────────────────────────────────────────────── -->
    <q-table
      class="clay-table q-mt-sm"
      :rows="filteredRows"
      :columns="currentColumns"
      row-key="id"
      :loading="loading"
      :pagination="pagination"
      flat
    >
      <template #body-cell-code="props">
        <q-td :props="props">
          <span class="org-code-badge">{{ props.value || 'N/A' }}</span>
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props" class="text-center">
          <q-btn round flat dense icon="edit" size="sm" color="primary" @click="openEditDialog(props.row)">
            <q-tooltip>Edit</q-tooltip>
          </q-btn>
          <q-btn round flat dense icon="delete_outline" size="sm" color="negative" @click="confirmDelete(props.row)">
            <q-tooltip>Delete</q-tooltip>
          </q-btn>
        </q-td>
      </template>

      <template #no-data>
        <div class="org-page__empty">
          <q-icon name="domain_disabled" size="48px" color="grey-5" />
          <p>No {{ activeTab }} found</p>
        </div>
      </template>
    </q-table>

    <!-- ── Create / Edit Dialog ──────────────────────────────────── -->
    <q-dialog v-model="showDialog" persistent>
      <q-card class="org-page__dialog">
        <q-card-section class="org-page__dialog-head">
          <q-icon :name="isEditing ? 'edit_note' : 'add_circle_outline'" size="26px" color="primary" />
          <span class="org-page__dialog-title">{{ isEditing ? `Edit ${activeTabLabel}` : `New ${activeTabLabel}` }}</span>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="org-page__dialog-body">
          <q-input
            v-model="form.name"
            :label="`${activeTabLabel} Name *`"
            outlined dense
            :rules="[val => !!val || 'Required']"
            class="q-mb-md"
          />

          <q-input
            v-model="form.code"
            label="Code / Acronym"
            outlined dense
            hint="e.g. LSD, CHEM, PHYS"
            class="q-mb-md"
          />

          <!-- Section Specific: Division dropdown -->
          <q-select
            v-if="activeTab === 'sections'"
            v-model="form.division_id"
            :options="divisionOptions"
            label="Division"
            outlined dense
            emit-value
            map-options
            clearable
            class="q-mb-md"
          />

          <q-input
            v-model="form.description"
            label="Description"
            outlined dense
            type="textarea"
            rows="3"
          />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="org-page__dialog-actions">
          <q-btn flat no-caps label="Cancel" color="grey-7" v-close-popup />
          <q-btn
            unelevated no-caps
            :label="isEditing ? 'Save Changes' : 'Create'"
            class="clay-btn clay-btn--primary"
            :loading="saving"
            @click="submitForm"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ── Delete Confirm Dialog ─────────────────────────────────── -->
    <q-dialog v-model="showDeleteDialog" persistent>
      <q-card class="org-page__dialog org-page__dialog--danger">
        <q-card-section class="org-page__dialog-head">
          <q-icon name="warning_amber" size="26px" color="negative" />
          <span class="org-page__dialog-title">Delete {{ activeTabLabel }}</span>
        </q-card-section>
        <q-card-section class="org-page__dialog-body">
          <p>Are you sure you want to delete <strong>{{ deleteTarget?.name }}</strong>?</p>
          <p class="org-page__delete-warn">This action cannot be undone.</p>
        </q-card-section>
        <q-card-actions align="right" class="org-page__dialog-actions">
          <q-btn flat no-caps label="Cancel" color="grey-7" v-close-popup />
          <q-btn unelevated no-caps label="Delete" color="negative" :loading="deleting" @click="deleteItem" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'

const $q = useQuasar()

const activeTab = ref('divisions')
const loading   = ref(false)
const saving    = ref(false)
const deleting  = ref(false)
const search    = ref('')
const sectionDivisionFilter = ref(null)

const divisions = ref([])
const sections  = ref([])

const showDialog       = ref(false)
const showDeleteDialog = ref(false)
const isEditing        = ref(false)
const deleteTarget     = ref(null)

const pagination = ref({ sortBy: 'name', descending: false, page: 1, rowsPerPage: 10 })

const emptyForm = () => ({ name: '', code: '', division_id: null, description: '' })
const form = ref(emptyForm())
let editingId = null

const activeTabLabel = computed(() => {
  if (activeTab.value === 'divisions') return 'Division'
  return 'Section'
})

const divisionOptions = computed(() => divisions.value.map(d => ({ label: d.name, value: d.id })))

const currentRows = computed(() => {
  if (activeTab.value === 'divisions') return divisions.value
  return sections.value
})

const filteredRows = computed(() => {
  let data = currentRows.value
  if (search.value) {
    const q = search.value.toLowerCase()
    data = data.filter(r =>
      r.name?.toLowerCase().includes(q) ||
      r.code?.toLowerCase().includes(q) ||
      r.description?.toLowerCase().includes(q)
    )
  }
  return data
})

const currentColumns = computed(() => {
  const base = [
    { name: 'id',   label: '#',    field: 'id',   align: 'left', sortable: true },
    { name: 'name', label: 'Name', field: 'name', align: 'left', sortable: true },
    { name: 'code', label: 'Code', field: 'code', align: 'left', sortable: true },
  ]

  if (activeTab.value === 'sections') {
    base.push({ name: 'division', label: 'Division', field: r => r.division?.name ?? '-', align: 'left' })
  }

  base.push({ name: 'actions', label: 'Actions', field: 'actions', align: 'center' })
  return base
})

function notify(type, message) {
  $q.notify({ type, message, position: 'top-right', timeout: 2500 })
}

async function fetchAllData() {
  loading.value = true
  try {
    const [dRes, sRes] = await Promise.all([
      api.get('/divisions'),
      api.get('/sections'),
    ])
    divisions.value = dRes.data
    sections.value  = sRes.data
  } catch {
    notify('negative', 'Failed to load organization data.')
  } finally {
    loading.value = false
  }
}

async function fetchSections() {
  loading.value = true
  try {
    const params = sectionDivisionFilter.value
      ? { division_id: sectionDivisionFilter.value }
      : {}
    const { data } = await api.get('/sections', { params })
    sections.value = data
  } catch {
    sections.value = []
    notify('negative', 'Failed to load sections.')
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
    name: row.name,
    code: row.code,
    division_id: row.division_id ?? null,
    description: row.description ?? '',
  }
  showDialog.value = true
}

async function submitForm() {
  saving.value = true
  try {
    const endpoint = `/${activeTab.value}`
    if (isEditing.value) {
      await api.put(`${endpoint}/${editingId}`, form.value)
      notify('positive', `${activeTabLabel.value} updated successfully.`)
    } else {
      await api.post(endpoint, form.value)
      notify('positive', `${activeTabLabel.value} created successfully.`)
    }
    showDialog.value = false
    await fetchAllData()
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

async function deleteItem() {
  deleting.value = true
  try {
    await api.delete(`/${activeTab.value}/${deleteTarget.value.id}`)
    notify('positive', `${activeTabLabel.value} deleted.`)
    showDeleteDialog.value = false
    await fetchAllData()
  } catch {
    notify('negative', `Failed to delete ${activeTabLabel.value.toLowerCase()}.`)
  } finally {
    deleting.value = false
    deleteTarget.value = null
  }
}

watch(activeTab, () => {
  search.value = ''
})

watch(sectionDivisionFilter, fetchSections)

onMounted(fetchAllData)
</script>

<style lang="scss" scoped>
@import '@/css/themes.scss';

.org-page {
  padding: 32px;
  background: $min-bg;
  min-height: 100vh;

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 20px;
  }

  &__title {
    color: $min-text;
    font-weight: 800;
    font-family: 'Nunito', sans-serif;
  }

  &__subtitle {
    color: $min-text-soft;
    font-size: 0.85rem;
    margin-top: 2px;
  }

  &__tabs-container {
    background: $min-surface;
    border-radius: 8px;
    border: 1px solid $min-border;
    padding: 2px 8px;
  }

  &__toolbar {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
  }

  &__search {
    min-width: 240px;
    flex: 1;
    max-width: 340px;
    :deep(.q-field__control) {
      border-radius: 8px;
      background: $min-surface;
    }
  }

  &__division-filter {
    width: 240px;
    :deep(.q-field__control) {
      border-radius: 8px;
      background: $min-surface;
    }
  }

  &__stat-chips {
    margin-left: auto;
  }

  &__empty {
    padding: 48px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: $min-text-soft;
    font-size: 0.9rem;
  }

  &__dialog {
    @include min-card();
    width: 480px;
    max-width: 95vw;
    padding: 0;

    &--danger { max-width: 400px; }
  }

  &__dialog-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 20px 12px;
    border-bottom: 1px solid $min-border;
  }

  &__dialog-title {
    font-size: 1rem;
    font-weight: 800;
    color: $min-text;
    font-family: 'Nunito', sans-serif;
  }

  &__dialog-body {
    padding: 16px 20px;
  }

  &__dialog-actions {
    padding: 8px 16px 16px;
    gap: 10px;
    background: $min-bg;
    border-top: 1px solid $min-border;
    border-radius: 0 0 12px 12px;
  }

  &__delete-warn {
    color: #ef4444 !important;
    font-size: 0.82rem !important;
    font-weight: 600;
  }
}

.org-stat-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  color: $accent-login;
  border: 1px solid rgba($accent-login, 0.3);
  background: #f0fdf4;
}

.org-code-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 700;
  background: #f0fdf4;
  color: $accent-login;
  border: 1px solid #bbf7d0;
}

.clay-table {
  @include min-card();
  padding: 8px;
  color: $min-text;

  :deep(thead tr) { background: transparent; }
  :deep(th) {
    color: $min-text-soft;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.03em;
    border-bottom: 1px solid $min-border;
  }
  :deep(td) { color: $min-text; border-bottom: 1px solid $min-border-strong; }
  :deep(tbody tr) {
    transition: background 0.15s ease;
    &:hover { background: $min-bg; }
    &:last-child td { border-bottom: none; }
  }
}

.clay-btn {
  &--primary { @include min-button($accent-login); }
}
</style>
