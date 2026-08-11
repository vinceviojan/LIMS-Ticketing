<template>
  <q-page class="pc-page">

    <!-- ── Header ──────────────────────────────────────────────── -->
    <div class="pc-page__header">
      <div>
        <div class="text-h5 pc-page__title">Problem Categories</div>
        <div class="pc-page__subtitle">Manage issue classification types and labels</div>
      </div>
      <q-btn
        class="clay-btn clay-btn--primary"
        label="New Category"
        icon="add_circle_outline"
        unelevated
        no-caps
        @click="openCreateDialog"
      />
    </div>

    <!-- ── Filter Bar ────────────────────────────────────────────── -->
    <div class="pc-page__toolbar">
      <q-input
        v-model="search"
        dense
        outlined
        clearable
        placeholder="Search categories..."
        class="pc-page__search"
      >
        <template #prepend>
          <q-icon name="search" />
        </template>
      </q-input>

      <q-select
        v-model="filterType"
        :options="typeFilterOptions"
        label="Filter by Type"
        dense
        outlined
        clearable
        emit-value
        map-options
        class="pc-page__type-filter"
      />

      <div class="pc-page__stat-chips">
        <div class="pc-stat-chip">
          <q-icon name="category" size="15px" />
          <span>{{ filteredRows.length }} shown</span>
        </div>
        <div class="pc-stat-chip pc-stat-chip--total">
          <q-icon name="layers" size="15px" />
          <span>{{ rows.length }} total</span>
        </div>
      </div>
    </div>

    <!-- ── Table ─────────────────────────────────────────────────── -->
    <q-table
      class="clay-table"
      :rows="filteredRows"
      :columns="columns"
      row-key="id"
      :loading="loading"
      :pagination="pagination"
      flat
    >
      <template #body-cell-type="props">
        <q-td :props="props">
          <span class="pc-type-badge">{{ props.value }}</span>
        </q-td>
      </template>

      <template #body-cell-categories="props">
        <q-td :props="props">
          <span class="pc-category-label">{{ props.value }}</span>
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
        <div class="pc-page__empty">
          <q-icon name="category" size="48px" color="grey-5" />
          <p>No problem categories found</p>
        </div>
      </template>
    </q-table>

    <!-- ── Create / Edit Dialog ──────────────────────────────────── -->
    <q-dialog v-model="showDialog" persistent>
      <q-card class="pc-page__dialog">
        <q-card-section class="pc-page__dialog-head">
          <q-icon :name="isEditing ? 'edit_note' : 'add_circle_outline'" size="26px" color="primary" />
          <span class="pc-page__dialog-title">{{ isEditing ? 'Edit Category' : 'New Category' }}</span>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="pc-page__dialog-body">
          <q-input
            v-model="form.type"
            label="Type *"
            outlined dense
            hint="e.g. hardware, software, network"
            :rules="[val => !!val || 'Required']"
            class="q-mb-md"
          />
          <q-input
            v-model="form.categories"
            label="Category Name *"
            outlined dense
            hint="e.g. Laptop Issue, OS Crash"
            :rules="[val => !!val || 'Required']"
          />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="pc-page__dialog-actions">
          <q-btn flat no-caps label="Cancel" color="grey-7" v-close-popup />
          <q-btn
            unelevated no-caps
            :label="isEditing ? 'Save Changes' : 'Create Category'"
            class="clay-btn clay-btn--primary"
            :loading="saving"
            @click="submitForm"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- ── Delete Confirm Dialog ─────────────────────────────────── -->
    <q-dialog v-model="showDeleteDialog" persistent>
      <q-card class="pc-page__dialog pc-page__dialog--danger">
        <q-card-section class="pc-page__dialog-head">
          <q-icon name="warning_amber" size="26px" color="negative" />
          <span class="pc-page__dialog-title">Delete Category</span>
        </q-card-section>
        <q-card-section class="pc-page__dialog-body">
          <p>Are you sure you want to delete <strong>{{ deleteTarget?.categories }}</strong> ({{ deleteTarget?.type }})?</p>
          <p class="pc-page__delete-warn">This action cannot be undone.</p>
        </q-card-section>
        <q-card-actions align="right" class="pc-page__dialog-actions">
          <q-btn flat no-caps label="Cancel" color="grey-7" v-close-popup />
          <q-btn unelevated no-caps label="Delete" color="negative" :loading="deleting" @click="deleteCategory" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'

const $q = useQuasar()

// ── State ─────────────────────────────────────────────────────
const loading  = ref(false)
const saving   = ref(false)
const deleting = ref(false)
const search   = ref('')
const filterType = ref(null)
const rows     = ref([])

const showDialog       = ref(false)
const showDeleteDialog = ref(false)
const isEditing        = ref(false)
const deleteTarget     = ref(null)

const pagination = ref({ sortBy: 'type', descending: false, page: 1, rowsPerPage: 12 })

const emptyForm = () => ({ type: '', categories: '' })
const form = ref(emptyForm())
let editingId = null

// ── Columns ───────────────────────────────────────────────────
const columns = [
  { name: 'id',         label: '#',        field: 'id',         align: 'left',   sortable: true },
  { name: 'type',       label: 'Type',     field: 'type',       align: 'left',   sortable: true },
  { name: 'categories', label: 'Category', field: 'categories', align: 'left',   sortable: true },
  { name: 'actions',   label: 'Actions',  field: 'actions',   align: 'center' },
]

// ── Computed ──────────────────────────────────────────────────
const typeFilterOptions = computed(() => {
  const types = [...new Set(rows.value.map(r => r.type))]
  return types.map(t => ({ label: t, value: t }))
})

const filteredRows = computed(() => {
  let data = rows.value
  if (filterType.value) data = data.filter(r => r.type === filterType.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    data = data.filter(r =>
      r.type?.toLowerCase().includes(q) ||
      r.categories?.toLowerCase().includes(q)
    )
  }
  return data
})

// ── Helpers ───────────────────────────────────────────────────
function notify(type, message) {
  $q.notify({ type, message, position: 'top-right', timeout: 2500 })
}

// ── CRUD ──────────────────────────────────────────────────────
async function fetchCategories() {
  loading.value = true
  try {
    const { data } = await api.get('/problem-categories')
    rows.value = data.data ?? data
  } catch {
    notify('negative', 'Failed to load problem categories.')
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
  form.value = { type: row.type, categories: row.categories }
  showDialog.value = true
}

async function submitForm() {
  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/problem-categories/${editingId}`, form.value)
      notify('positive', 'Category updated successfully.')
    } else {
      await api.post('/problem-categories', form.value)
      notify('positive', 'Category created successfully.')
    }
    showDialog.value = false
    await fetchCategories()
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

async function deleteCategory() {
  deleting.value = true
  try {
    await api.delete(`/problem-categories/${deleteTarget.value.id}`)
    notify('positive', 'Category deleted.')
    showDeleteDialog.value = false
    await fetchCategories()
  } catch {
    notify('negative', 'Failed to delete category.')
  } finally {
    deleting.value = false
    deleteTarget.value = null
  }
}

onMounted(fetchCategories)
</script>

<style lang="scss" scoped>
@import '@/css/themes.scss';

.pc-page {
  padding: 32px;
  background: $min-bg;
  min-height: 100vh;

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
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

  &__toolbar {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 20px;
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

  &__type-filter {
    min-width: 180px;
    :deep(.q-field__control) {
      border-radius: 8px;
      background: $min-surface;
    }
  }

  &__stat-chips {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
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

  // ── Dialog ────────────────────────────────────────────────────
  &__dialog {
    @include min-card();
    width: 460px;
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
    padding: 12px 20px 16px;
    p {
      margin: 0 0 8px;
      color: $min-text;
      font-size: 0.9rem;
    }
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

// ── Stat Chips ────────────────────────────────────────────────
.pc-stat-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  color: $min-text-soft;
  background: $min-surface;
  border: 1px solid $min-border;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);

  &--total { color: $accent-login; border-color: rgba($accent-login, 0.3); background: #f0fdf4; }
}

// ── Type Badge ────────────────────────────────────────────────
.pc-type-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  background: #f0fdf4;
  color: $accent-login;
  border: 1px solid #bbf7d0;
}

.pc-category-label {
  font-weight: 500;
  color: $min-text;
}

// ── Table ─────────────────────────────────────────────────────
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
    &:hover {
      background: $min-bg;
    }
    &:last-child td { border-bottom: none; }
  }
  :deep(.q-table__bottom) { color: $min-text-soft; border-top: 1px solid $min-border; }
}

// ── Buttons ───────────────────────────────────────────────────
.clay-btn {
  &--primary { @include min-button($accent-login); }
}
</style>
