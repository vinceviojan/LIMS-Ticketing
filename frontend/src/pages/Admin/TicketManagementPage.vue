<template>
  <q-page class="ticket-page">

    <!-- ── Header ──────────────────────────────────────────────── -->
    <div class="ticket-page__header">
      <div>
        <div class="text-h5 ticket-page__title">Ticket Management</div>
        <div class="ticket-page__subtitle">Review, assign and resolve support tickets</div>
      </div>
      <q-btn
        class="clay-btn clay-btn--primary"
        label="New Ticket"
        icon="add_circle_outline"
        unelevated
        no-caps
        @click="openCreateDialog"
      />
    </div>

    <!-- ── Status Tabs ─────────────────────────────────────────── -->
    <div class="ticket-page__tabs">
      <button
        v-for="tab in statusTabs"
        :key="tab.value"
        class="ticket-page__tab"
        :class="{ 'ticket-page__tab--active': activeTab === tab.value }"
        @click="activeTab = tab.value"
      >
        <q-icon :name="tab.icon" size="16px" />
        {{ tab.label }}
        <span class="ticket-page__tab-count">{{ tabCount(tab.value) }}</span>
      </button>
    </div>

    <!-- ── Toolbar ─────────────────────────────────────────────── -->
    <div class="ticket-page__toolbar">
      <q-input
        v-model="search"
        dense outlined clearable
        placeholder="Search tickets..."
        class="ticket-page__search"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>

      <q-select
        v-model="filterPriority"
        :options="priorityOptions"
        label="Priority"
        dense outlined clearable
        emit-value map-options
        class="ticket-page__filter"
      />

      <q-space />

      <q-btn-group outline class="ticket-page__view-toggle">
        <q-btn :color="displayMode === 'card' ? 'primary' : 'grey-7'" :outline="displayMode !== 'card'" unelevated icon="grid_view" @click="displayMode = 'card'" />
        <q-btn :color="displayMode === 'table' ? 'primary' : 'grey-7'" :outline="displayMode !== 'table'" unelevated icon="list" @click="displayMode = 'table'" />
      </q-btn-group>
    </div>

    <!-- ── Ticket Views ────────────────────────────────────────── -->
    <template v-if="!loading && filteredTickets.length">
      <!-- Grid View -->
      <div v-if="displayMode === 'card'" class="ticket-page__grid">
        <div
          v-for="ticket in filteredTickets"
          :key="ticket.id"
          class="ticket-card"
          :class="`ticket-card--${ticket.priority?.toLowerCase()}`"
          @click="viewTicket(ticket)"
        >
          <div class="ticket-card__top">
            <span class="ticket-card__id">{{ ticket.ticket_no || '#' + ticket.id }}</span>
            <span class="ticket-card__priority">{{ ticket.priority }}</span>
          </div>
          <div class="ticket-card__title">{{ ticket.title }}</div>
          <div class="ticket-card__description">{{ ticket.description || 'No description provided.' }}</div>
          <div class="ticket-card__meta">
            <q-icon name="person" size="13px" />
            {{ ticket.requester }}
            <q-icon name="category" size="13px" class="q-ml-sm" />
            {{ ticket.category }}
            <q-icon name="support_agent" size="13px" class="q-ml-sm" />
            {{ ticket.assignedStaff || 'Unassigned' }}
            <q-icon v-if="ticket.hasAttachments" name="attach_file" size="13px" class="q-ml-sm" />
          </div>
          <div class="ticket-card__footer">
            <span :class="['ticket-card__status', `ticket-card__status--${ticket.status?.toLowerCase()}`]">
              {{ ticket.status }}
            </span>
            <span class="ticket-card__date">{{ ticket.created }}</span>
          </div>
        </div>
      </div>

      <!-- Table View -->
      <div v-else-if="displayMode === 'table'" class="ticket-page__table-wrap">
        <table class="mini-table">
          <thead>
            <tr>
              <th>Ticket #</th>
              <th>Title</th>
              <th>Description</th>
              <th>Requester</th>
              <th>Assigned Staff</th>
              <th>Category</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Date</th>
              <th>Files</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ticket in filteredTickets" :key="ticket.id" @click="viewTicket(ticket)" class="cursor-pointer">
              <td class="mini-table__id">{{ ticket.ticket_no || '#' + ticket.id }}</td>
              <td class="mini-table__title"><strong>{{ ticket.title }}</strong></td>
              <td>{{ ticket.description || '—' }}</td>
              <td>{{ ticket.requester }}</td>
              <td>{{ ticket.assignedStaff || 'Unassigned' }}</td>
              <td>{{ ticket.category }}</td>
              <td><span class="ticket-card__priority">{{ ticket.priority }}</span></td>
              <td>
                <span :class="['ticket-card__status', `ticket-card__status--${ticket.status?.toLowerCase()}`]">
                  {{ ticket.status }}
                </span>
              </td>
              <td class="mini-table__date">{{ ticket.created }}</td>
              <td><q-icon v-if="ticket.hasAttachments" name="attach_file" size="17px" color="primary" /></td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <div v-else-if="loading" class="ticket-page__loading">
      <q-spinner-dots size="40px" color="primary" />
      <p>Loading tickets…</p>
    </div>

    <div v-else class="ticket-page__empty">
      <q-icon name="confirmation_number" size="52px" color="grey-5" />
      <p>No tickets found</p>
    </div>

    <!-- ── View/Create Dialog ──────────────────────────────────── -->
    <TicketModal 
      v-model="showDialog" 
      :ticket="selectedTicket" 
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
import TicketModal from '../../components/TicketModal.vue'
import './TicketManagementPage.scss'
const $q = useQuasar()

// ── State ────────────────────────────────────────────────────
const loading = ref(false)
const search  = ref('')
const filterPriority = ref(null)
const activeTab = ref('ALL')
const displayMode = ref('card')
const showDialog = ref(false)
const modalMode = ref('create')
const selectedTicket = ref(null)

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
  if (search.value) {
    const q = search.value.toLowerCase()
    data = data.filter(t =>
      t.title?.toLowerCase().includes(q) ||
      t.ticket_no?.toLowerCase().includes(q) ||
      t.requester?.toLowerCase().includes(q) ||
      t.category?.toLowerCase().includes(q)
    )
  }
  return data
})

function tabCount(status) {
  if (status === 'ALL') return tickets.value.length
  return tickets.value.filter(t => t.status === status).length
}

// ── Actions ─────────────────────────────────────────────────
function openCreateDialog() {
  modalMode.value = 'create'
  selectedTicket.value = null
  showDialog.value = true
}

function viewTicket(ticket) {
  modalMode.value = 'view'
  selectedTicket.value = ticket
  showDialog.value = true
}
</script>

<style lang="scss" scoped>
</style>
