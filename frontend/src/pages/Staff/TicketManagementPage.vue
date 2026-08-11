<template>
  <q-page class="ticket-page q-pa-lg bg-grey-1">
    <!-- ── Header ──────────────────────────────────────────────── -->
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bolder text-dark">Ticket Management</div>
        <div class="text-caption text-grey-7 q-mt-xs">Review, assign and resolve support tickets</div>
      </div>
      <q-btn
        color="primary"
        label="New Ticket"
        icon="add_circle_outline"
        unelevated
        no-caps
        class="border-radius-8 text-weight-bold"
        @click="openCreateDialog"
      />
    </div>

    <!-- ── Status Tabs ─────────────────────────────────────────── -->
    <div class="row q-gutter-sm q-mb-lg">
      <q-btn
        v-for="tab in statusTabs"
        :key="tab.value"
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
    <div class="row items-center q-gutter-md q-mb-lg flex-wrap">
      <q-input
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
        flat dense no-caps
        color="negative"
        icon="restart_alt"
        label="Reset"
        class="q-px-sm border-radius-8"
        @click="resetFilters"
      />

      <q-space />

      <q-btn-group outline class="bg-white border-radius-8">
        <q-btn :color="displayMode === 'card' ? 'primary' : 'grey-7'" :flat="displayMode !== 'card'" unelevated icon="grid_view" @click="displayMode = 'card'" />
        <q-btn :color="displayMode === 'table' ? 'primary' : 'grey-7'" :flat="displayMode !== 'table'" unelevated icon="list" @click="displayMode = 'table'" />
      </q-btn-group>
    </div>

    <!-- ── Ticket Views (Reusable Component) ─────────────────── -->
    <TicketListView
      :tickets="filteredTickets"
      :displayMode="displayMode"
      :loading="loading"
      :readonly="true"
      @view-ticket="viewTicket"
    />

    <!-- ── Create Dialog ───────────────────────────────────────── -->
    <AddTicketModal
      v-model="showAddDialog"
      :category-options="categoryOptions"
      :staff-options="staffOptions"
      :priority-options="priorityOptions"
      @refresh="fetchTickets"
    />

    <!-- ── View Dialog (read-only) ─────────────────────────────── -->
    <ViewTicketModal
      v-model="showViewDialog"
      :ticket="selectedTicket"
    />

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'
import AddTicketModal from '../../components/AddTicketModal.vue'
import ViewTicketModal from '../../components/ViewTicketModal.vue'
import TicketListView from '../../components/TicketListView.vue'
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
const showViewDialog = ref(false)
const selectedTicket = ref(null)

const sortOptions = [
  { label: 'Newest First', value: 'newest' },
  { label: 'Oldest First', value: 'oldest' },
  { label: 'Ticket # (A-Z)', value: 'ticket_asc' },
  { label: 'Title (A-Z)', value: 'title_asc' },
  { label: 'Priority (High to Low)', value: 'priority_desc' }
]

const priorityOptions = [
  { label: 'Low',      value: 'LOW'      },
  { label: 'Normal',   value: 'NORMAL'   },
  { label: 'High',     value: 'HIGH'     },
]

const categoryOptions = ref([])
const staffOptions = ref([])
const tickets = ref([])

const statusTabs = [
  { label: 'All',      value: 'ALL',      icon: 'list_alt'       },
  { label: 'Open',     value: 'OPEN',     icon: 'inbox'          },
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
      upload_intralab: t.upload_intralab,
      upload_limsportal: t.upload_limsportal,
      hasAttachments: Boolean(t.upload_intralab || t.upload_limsportal),
      created: new Date(t.date_submitted || t.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    }))
  } catch (err) {
    console.error('Failed to load tickets', err)
    $q.notify({ type: 'negative', message: 'Failed to load tickets.' })
  } finally {
    loading.value = false
  }
}

// ── Computed ─────────────────────────────────────────────────
const filteredTickets = computed(() => {
  let data = tickets.value
  if (activeTab.value !== 'ALL') data = data.filter(t => t.status === activeTab.value)
  if (filterPriority.value) data = data.filter(t => t.priority === filterPriority.value)
  if (filterCategory.value) data = data.filter(t => t.category === filterCategory.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    data = data.filter(t =>
      t.title?.toLowerCase().includes(q) ||
      t.ticket_no?.toLowerCase().includes(q) ||
      t.requester?.toLowerCase().includes(q) ||
      t.category?.toLowerCase().includes(q)
    )
  }

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

function openCreateDialog() {
  showAddDialog.value = true
}

function viewTicket(ticket) {
  selectedTicket.value = ticket
  showViewDialog.value = true
}
</script>