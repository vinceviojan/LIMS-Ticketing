<template>
  <q-page class="accomplishment-page">
    <div class="accomplishment-page__header">
      <div>
        <div class="text-h5 accomplishment-page__title">Accomplishment Report</div>
        <div class="accomplishment-page__subtitle">Generate your accomplishment report for resolved tickets</div>
      </div>
      <q-btn
        class="clay-btn clay-btn--primary"
        label="Generate Report"
        icon="summarize"
        unelevated
        no-caps
        :disable="selectedTickets.length === 0"
        @click="openReportDialog"
      />
    </div>

    <!-- ── Filters ─────────────────────────────────────────────── -->
    <div class="accomplishment-page__filters">
      <q-input v-model="dateStart" type="date" label="Start Date" outlined dense class="date-input" />
      <q-input v-model="dateEnd" type="date" label="End Date" outlined dense class="date-input" />
      <q-btn icon="search" color="primary" unelevated no-caps label="Filter" @click="filterTickets" class="q-ml-sm" />
    </div>

    <!-- ── Table ───────────────────────────────────────────────── -->
    <div class="accomplishment-page__table-card">
      <q-table
        v-if="filteredTickets.length"
        flat
        :rows="filteredTickets"
        :columns="columns"
        row-key="id"
        selection="multiple"
        v-model:selected="selectedTickets"
        :pagination="pagination"
      >
        <template v-slot:body-cell-status="props">
          <q-td :props="props">
            <q-badge color="positive">{{ props.row.status }}</q-badge>
          </q-td>
        </template>
        <template v-slot:body-cell-priority="props">
          <q-td :props="props">
            <span style="font-weight:600">{{ props.row.priority }}</span>
          </q-td>
        </template>
      </q-table>

      <div v-else class="accomplishment-page__empty">
        <q-icon name="assignment_turned_in" size="52px" color="grey-5" />
        <p>No closed tickets found for this period</p>
      </div>
    </div>

    <!-- ── Report Dialog ───────────────────────────────────────── -->
    <q-dialog v-model="showReportDialog" persistent>
      <q-card class="report-dialog__card">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6" style="font-weight: 700; color: #1e293b;">Generated Report</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="report-dialog__content">
          <div class="report-dialog__options">
            <span style="font-weight: 600; font-size: 0.9rem;">Format:</span>
            <q-option-group
              v-model="reportFormat"
              :options="formatOptions"
              color="primary"
              inline
              dense
            />
          </div>
          <div class="report-dialog__preview">
            <textarea v-model="generatedReportText" readonly></textarea>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancel" color="grey-7" v-close-popup no-caps />
          <q-btn class="clay-btn clay-btn--primary" icon="content_copy" label="Copy to Clipboard" @click="copyReport" no-caps />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar, copyToClipboard } from 'quasar'
import { useAuthStore } from '../../stores/auth'
import './AccomplishmentReport.scss'

const $q = useQuasar()
const authStore = useAuthStore()

// State
const dateStart = ref('')
const dateEnd = ref('')
const selectedTickets = ref([])
const tickets = ref([])
const filteredTickets = ref([])

const showReportDialog = ref(false)
const reportFormat = ref('bullets')

const pagination = ref({
  rowsPerPage: 10
})

const columns = [
  { name: 'ticket_no', label: 'Ticket #', field: row => row.ticket_no || '#' + row.id, align: 'left', sortable: true },
  { name: 'title', label: 'Title', field: 'title', align: 'left', sortable: true },
  { name: 'category', label: 'Category', field: 'category', align: 'left' },
  { name: 'requester', label: 'Requester', field: 'requester', align: 'left' },
  { name: 'priority', label: 'Priority', field: 'priority', align: 'center', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'center' },
  { name: 'date', label: 'Date Resolved', field: 'created', align: 'right', sortable: true } // Assuming 'created' acts as date for now, would be resolved_at
]

const formatOptions = [
  { label: 'Bulleted List', value: 'bullets' },
  { label: 'Detailed Summary', value: 'detailed' },
  { label: 'CSV / Table', value: 'csv' }
]

// Mock data fetching, in a real app this would be an API call
onMounted(() => {
  // Set default dates to current month
  const today = new Date()
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
  dateStart.value = firstDay.toISOString().split('T')[0]
  dateEnd.value = today.toISOString().split('T')[0]
  
  fetchClosedTickets()
})

function fetchClosedTickets() {
  // Mock closed tickets assigned to current staff
  const staffName = authStore.userName || 'Staff Member'
  
  tickets.value = [
    { id: 101, ticket_no: 'TKT-2026-0801', title: 'Network Connectivity Issue', category: 'Network', requester: 'John Doe', priority: 'HIGH', status: 'CLOSE', assignedStaff: staffName, created: '2026-08-01', description: 'User could not connect to the local network.' },
    { id: 102, ticket_no: 'TKT-2026-0802', title: 'Printer Configuration', category: 'Hardware', requester: 'Jane Smith', priority: 'NORMAL', status: 'CLOSE', assignedStaff: staffName, created: '2026-08-02', description: 'Set up the new printer in the HR department.' },
    { id: 105, ticket_no: 'TKT-2026-0804', title: 'Software License Renewal', category: 'Software', requester: 'Mark Lee', priority: 'LOW', status: 'CLOSE', assignedStaff: staffName, created: '2026-08-04', description: 'Renewed the antivirus license for the laboratory.' },
    { id: 108, ticket_no: 'TKT-2026-0805', title: 'Password Reset', category: 'Access', requester: 'Sarah Connor', priority: 'NORMAL', status: 'CLOSE', assignedStaff: staffName, created: '2026-08-05', description: 'Reset password for LIMS system.' },
    { id: 112, ticket_no: 'TKT-2026-0809', title: 'Database Backup Configuration', category: 'Database', requester: 'Admin', priority: 'CRITICAL', status: 'CLOSE', assignedStaff: staffName, created: '2026-08-09', description: 'Configured automated daily backups.' },
  ]
  
  filterTickets()
}

function filterTickets() {
  selectedTickets.value = [] // Reset selection on filter
  
  filteredTickets.value = tickets.value.filter(ticket => {
    // Check if within date range
    if (dateStart.value && ticket.created < dateStart.value) return false
    if (dateEnd.value && ticket.created > dateEnd.value) return false
    return true
  })
}

function openReportDialog() {
  showReportDialog.value = true
}

const generatedReportText = computed(() => {
  if (selectedTickets.value.length === 0) return ''
  
  let text = `Accomplishment Report\nPeriod: ${dateStart.value} to ${dateEnd.value}\nStaff: ${authStore.userName}\n\n`
  
  if (reportFormat.value === 'bullets') {
    text += `Resolved ${selectedTickets.value.length} tickets:\n\n`
    selectedTickets.value.forEach(t => {
      const id = t.ticket_no || `#${t.id}`
      text += `- [${id}] ${t.title} (${t.created})\n`
    })
  } 
  else if (reportFormat.value === 'detailed') {
    text += `Detailed summary of ${selectedTickets.value.length} resolved tickets:\n\n`
    selectedTickets.value.forEach((t, i) => {
      const id = t.ticket_no || `#${t.id}`
      text += `${i + 1}. Ticket ${id}: ${t.title}\n`
      text += `   Category: ${t.category} | Priority: ${t.priority} | Requester: ${t.requester}\n`
      text += `   Date Resolved: ${t.created}\n`
      text += `   Description: ${t.description}\n\n`
    })
  }
  else if (reportFormat.value === 'csv') {
    text += `Ticket No,Title,Category,Requester,Priority,Date Resolved\n`
    selectedTickets.value.forEach(t => {
      const id = t.ticket_no || `#${t.id}`
      text += `"${id}","${t.title}","${t.category}","${t.requester}","${t.priority}","${t.created}"\n`
    })
  }
  
  return text
})

function copyReport() {
  copyToClipboard(generatedReportText.value)
    .then(() => {
      $q.notify({ type: 'positive', message: 'Report copied to clipboard!', position: 'top-right', timeout: 2000 })
    })
    .catch(() => {
      $q.notify({ type: 'negative', message: 'Failed to copy report.', position: 'top-right' })
    })
}
</script>

<style lang="scss" scoped>
</style>
