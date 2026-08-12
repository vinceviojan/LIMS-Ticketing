<template>
  <q-page id="ticket-management-page" class="ticket-page q-pa-lg bg-grey-1">
    <!-- ── Header ──────────────────────────────────────────────── -->
    <div id="ticket-management-header" class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bolder text-dark">Ticket Management</div>
        <div class="text-caption text-grey-7 q-mt-xs">Review, assign and resolve support tickets</div>
      </div>
      <div class="row q-gutter-sm">
        <q-btn
          id="export-selected-btn"
          class="clay-btn"
          :label="selectedTickets.length ? `Export Selected (${selectedTickets.length})` : 'Export Selected'"
          icon="picture_as_pdf"
          unelevated
          no-caps
          :disable="!selectedTickets.length"
          :loading="exportingSelected"
          @click="exportSelected"
        >
          <q-tooltip v-if="!selectedTickets.length">Select one or more tickets first</q-tooltip>
        </q-btn>
        <q-btn-dropdown
          id="export-dropdown-btn"
          class="clay-btn"
          label="Export"
          icon="file_download"
          unelevated
          no-caps
          :loading="exporting"
        >
          <q-list>
            <q-item id="export-csv-item" clickable v-close-popup @click="handleExport('csv')">
              <q-item-section avatar><q-icon name="grid_on" /></q-item-section>
              <q-item-section>Export CSV</q-item-section>
            </q-item>
            <q-item id="export-json-item" clickable v-close-popup @click="handleExport('json')">
              <q-item-section avatar><q-icon name="data_object" /></q-item-section>
              <q-item-section>Export JSON</q-item-section>
            </q-item>
            <q-item id="export-pdf-all-item" clickable v-close-popup @click="handleExport('pdf')">
              <q-item-section avatar><q-icon name="picture_as_pdf" /></q-item-section>
              <q-item-section>Export PDF (all tickets)</q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
        <q-btn
          id="new-ticket-btn"
          color="primary"
          label="New Ticket"
          icon="add_circle_outline"
          unelevated
          no-caps
          class="border-radius-8 text-weight-bold"
        @click="openCreateDialog"
        />
      </div>
    </div>

    <!-- ── Status Tabs ─────────────────────────────────────────── -->
    <div id="status-tabs" class="row q-gutter-sm q-mb-lg">
      <q-btn
        v-for="tab in statusTabs"
        :key="tab.value"
        :id="'status-tab-' + tab.value.toLowerCase()"
        :color="activeTab === tab.value ? 'primary' : 'grey-8'"
        :flat="activeTab !== tab.value"
        :unelevated="activeTab === tab.value"
        no-caps
        class="border-radius-8 text-weight-bold"
        style="padding: 4px 16px;"
        @click="activeTab = tab.value"
      >
        <q-icon :name="tab.icon" size="18px" class="q-mr-sm" />
        {{ tab.label }}
        <q-badge :color="activeTab === tab.value ? 'white' : 'grey-3'" :text-color="activeTab === tab.value ? 'primary' : 'grey-8'" class="q-ml-sm text-weight-bolder">
          {{ tabCount(tab.value) }}
        </q-badge>
      </q-btn>
    </div>

    <!-- ── Toolbar ─────────────────────────────────────────────── -->
    <div id="ticket-toolbar" class="row items-center q-gutter-md q-mb-lg flex-wrap">
      <q-input
        id="ticket-search-input"
        v-model="search"
        dense outlined clearable
        placeholder="Search tickets..."
        bg-color="white"
        class="col-12 col-sm-auto border-radius-8"
        style="min-width: 240px;"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>

      <q-select
        id="priority-filter-select"
        v-model="filterPriority"
        :options="priorityOptions"
        label="Priority"
        dense outlined clearable
        emit-value map-options
        bg-color="white"
        class="col-12 col-sm-auto border-radius-8"
        style="min-width: 150px;"
      />

      <q-select
        id="category-filter-select"
        v-model="filterCategory"
        :options="categoryOptions"
        label="Category"
        dense outlined clearable
        emit-value map-options
        bg-color="white"
        class="col-12 col-sm-auto border-radius-8"
        style="min-width: 170px;"
      />

      <q-select
        id="sort-by-select"
        v-model="sortBy"
        :options="sortOptions"
        label="Sort By"
        dense outlined
        emit-value map-options
        bg-color="white"
        class="col-12 col-sm-auto border-radius-8"
        style="min-width: 190px;"
      />

      <q-btn
        v-if="search || filterPriority || filterCategory || sortBy !== 'newest'"
        id="reset-filters-btn"
        flat dense no-caps
        color="negative"
        icon="restart_alt"
        label="Reset"
        class="q-px-sm border-radius-8"
        @click="resetFilters"
      />

      <q-space />

      <q-btn-group id="display-mode-toggle" outline class="bg-white border-radius-8">
        <q-btn id="display-mode-card-btn" :color="displayMode === 'card' ? 'primary' : 'grey-7'" :flat="displayMode !== 'card'" unelevated icon="grid_view" @click="displayMode = 'card'" />
        <q-btn id="display-mode-table-btn" :color="displayMode === 'table' ? 'primary' : 'grey-7'" :flat="displayMode !== 'table'" unelevated icon="list" @click="displayMode = 'table'" />
      </q-btn-group>
    </div>

    <!-- ── Ticket Views (Reusable Component) ─────────────────── -->
    <TicketListView
      id="ticket-list-view"
      :tickets="filteredTickets"
      :displayMode="displayMode"
      :loading="loading"
      :readonly="false"
      :selectable="true"
      @view-ticket="viewTicket"
      @edit-ticket="editTicket"
      @update:selected="tickets => selectedTickets = tickets"
    />

    <!-- ── Create Dialog ───────────────────────────────────────── -->
    <AddTicketModal
      id="add-ticket-modal"
      v-model="showAddDialog"
      :category-options="categoryOptions"
      :staff-options="staffOptions"
      :priority-options="priorityOptions"
      @refresh="fetchTickets"
    />

    <!-- ── View/Edit Dialog ────────────────────────────────────── -->
    <EditTicketModal
      id="edit-ticket-modal"
      v-model="showEditDialog"
      :ticket="selectedTicketForModal"
      v-model:mode="modalMode"
      :category-options="categoryOptions"
      :staff-options="staffOptions"
      :priority-options="priorityOptions"
      @refresh="fetchTickets"
    />

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'
import AddTicketModal from '../../components/AddTicketModal.vue'
import EditTicketModal from '../../components/EditTicketModal.vue'
import TicketListView from '../../components/TicketListView.vue'
import { exportTicketToPdf, exportTicketsToPdf, exportTicketsToCSV, exportTicketsToJSON } from '../../assets/TicketExport.js'
import './TicketManagementPage.scss'
const $q = useQuasar()

// ── State ────────────────────────────────────────────────────
const loading = ref(true)
const search  = ref('')
const filterPriority = ref(null)
const filterCategory = ref(null)
const sortBy = ref('newest')

const activeTab = ref('ALL')
const displayMode = ref('table')
const showAddDialog = ref(false)
const showEditDialog = ref(false)
const modalMode = ref('view')
// Ticket opened via the view/edit dialog (separate from the checkbox selection below)
const selectedTicketForModal = ref(null)
// All tickets currently checked in TicketListView (table checkboxes / card checkboxes)
const selectedTickets = ref([])
const exporting = ref(false)
const exportingSelected = ref(false)

const priorityOptions = [
  { label: 'Low',      value: 'LOW'      },
  { label: 'Normal',   value: 'NORMAL'   },
  { label: 'High',     value: 'HIGH'     },
]

const sortOptions = [
  { label: 'Newest First',          value: 'newest'        },
  { label: 'Oldest First',          value: 'oldest'        },
  { label: 'Ticket # (A-Z)',        value: 'ticket_asc'    },
  { label: 'Title (A-Z)',           value: 'title_asc'     },
  { label: 'Priority (High->Low)',  value: 'priority_desc' }
]

const categoryOptions = ref([])
const staffOptions = ref([])
const tickets = ref([])

const statusTabs = [
  { label: 'All',      value: 'ALL',      icon: 'list_alt'       },
  { label: 'Open',     value: 'OPEN',     icon: 'inbox'          },
  { label: 'On-going', value: 'ON-GOING', icon: 'autorenew'      },
  { label: 'Resolved', value: 'RESOLVED', icon: 'task'           },
  { label: 'Escalated',value: 'ESCALATED',icon: 'pending_actions'},
  { label: 'Closed',   value: 'CLOSE',    icon: 'check_box'      },
  { label: 'Canceled', value: 'CANCEL',   icon: 'cancel'         },
]

// ── Lifecycle ────────────────────────────────────────────────
onMounted(async () => {
  await fetchCategories()
  await fetchStaff()
  await fetchTickets()
})

async function fetchCategories() {
  try {
    const res = await api.get('/problem-categories')
    const cats = res.data?.data || res.data || []
    categoryOptions.value = cats.map(c => ({ label: c.categories, value: c.id }))
  } catch (err) {
    console.error('Failed to load categories', err)
  }
}

async function fetchStaff() {
  try {
    const { data } = await api.get('/users')
    staffOptions.value = (data.data || data || [])
      .filter(user => user.role === 'STAFF')
      .map(user => ({ label: user.name || `${user.first_name} ${user.last_name}`, value: user.id }))
  } catch (err) {
    console.error('Failed to load staff', err)
  }
}

async function fetchTickets() {
  loading.value = true
  try {
    const res = await api.get('/tickets')
    const data = res.data?.data || res.data || []
    
    // Map backend array to UI properties internally
    tickets.value = data.map(t => ({
      id: t.id,
      real_id: t.id,
      ticket_no: t.ticket_no,
      title: t.issue || 'No Title',
      requester: t.user ? (t.user.first_name + ' ' + t.user.last_name) : 'Unknown',
      assignedStaff: t.assigned_staff ? (t.assigned_staff.name || `${t.assigned_staff.first_name} ${t.assigned_staff.last_name}`) : '',
      assigned_staff_id: t.assigned_staff_id,
      category: t.problem_category ? t.problem_category.categories : 'Uncategorized',
      problem_category_id: t.problem_category_id,
      priority: t.urgency || 'NORMAL',
      status: t.status || 'OPEN',
      description: t.description || '',
      remarks: t.remarks || '',
      rating: t.rating,
      feedback: t.feedback,
      attachments: t.attachments || [],
      upload_intralab: t.upload_intralab,
      upload_limsportal: t.upload_limsportal,
      hasAttachments: Boolean((t.attachments && t.attachments.length) || t.upload_intralab || t.upload_limsportal),
      created: new Date(t.date_submitted || t.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    }))
  } catch (err) {
    console.error('Failed to load tickets', err)
    $q.notify({ type: 'negative', message: 'Failed to load tickets.' })
  } finally {
    loading.value = false
  }
}

// ── Computed & Watchers ───────────────────────────────────────
const filteredTickets = computed(() => {
  let data = [...tickets.value]
  
  if (activeTab.value !== 'ALL') data = data.filter(t => t.status === activeTab.value)
  if (filterPriority.value) data = data.filter(t => t.priority === filterPriority.value)
  if (filterCategory.value) data = data.filter(t => t.problem_category_id === filterCategory.value || t.category === filterCategory.value)
  
  if (search.value) {
    const q = search.value.toLowerCase()
    data = data.filter(t =>
      t.title?.toLowerCase().includes(q) ||
      t.ticket_no?.toLowerCase().includes(q) ||
      t.requester?.toLowerCase().includes(q) ||
      t.category?.toLowerCase().includes(q)
    )
  }

  // Sorting
  if (sortBy.value === 'newest') {
    data.sort((a, b) => new Date(b.created) - new Date(a.created))
  } else if (sortBy.value === 'oldest') {
    data.sort((a, b) => new Date(a.created) - new Date(b.created))
  } else if (sortBy.value === 'ticket_asc') {
    data.sort((a, b) => (a.ticket_no || '').localeCompare(b.ticket_no || ''))
  } else if (sortBy.value === 'title_asc') {
    data.sort((a, b) => (a.title || '').localeCompare(b.title || ''))
  } else if (sortBy.value === 'priority_desc') {
    const pWeight = { CRITICAL: 4, HIGH: 3, NORMAL: 2, LOW: 1 }
    data.sort((a, b) => (pWeight[b.priority] || 0) - (pWeight[a.priority] || 0))
  }

  return data
})

function tabCount(status) {
  if (status === 'ALL') return tickets.value.length
  return tickets.value.filter(t => t.status === status).length
}

// ── Actions ─────────────────────────────────────────────────
function resetFilters() {
  search.value = ''
  filterPriority.value = null
  filterCategory.value = null
  sortBy.value = 'newest'
}

// ── Actions ─────────────────────────────────────────────────
function openCreateDialog() {
  showAddDialog.value = true
}

function viewTicket(ticket) {
  modalMode.value = 'view'
  selectedTicketForModal.value = ticket
  showEditDialog.value = true
}

function editTicket(ticket) {
  modalMode.value = 'edit'
  selectedTicketForModal.value = ticket
  showEditDialog.value = true
}

// ── Export ──────────────────────────────────────────────────
async function exportSelected() {
  const selection = selectedTickets.value
  if (!selection.length) {
    $q.notify({ type: 'warning', message: 'Select one or more tickets first.' })
    return
  }
  exportingSelected.value = true
  try {
    if (selection.length === 1) {
      await exportTicketToPdf(selection[0])
    } else {
      await exportTicketsToPdf(selection, `selected-tickets-${selection.length}.pdf`)
    }
  } catch (err) {
    console.error('Failed to export selected tickets', err)
    $q.notify({ type: 'negative', message: 'Failed to export selected tickets.' })
  } finally {
    exportingSelected.value = false
  }
}

async function handleExport(format) {
  if (!tickets.value.length) {
    $q.notify({ type: 'warning', message: 'No tickets to export.' })
    return
  }
  exporting.value = true
  try {
    if (format === 'csv') exportTicketsToCSV(tickets.value)
    else if (format === 'json') exportTicketsToJSON(tickets.value)
    else if (format === 'pdf') await exportTicketsToPdf(tickets.value)
  } catch (err) {
    console.error('Failed to export tickets', err)
    $q.notify({ type: 'negative', message: 'Failed to export tickets.' })
  } finally {
    exporting.value = false
  }
}
</script>

<style scoped>
</style>