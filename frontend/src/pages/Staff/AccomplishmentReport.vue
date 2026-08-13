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
        v-if="filteredTickets.length || loading"
        flat
        :loading="loading"
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
import { api } from '../../boot/axios'
import { useAuthStore } from '../../stores/auth'
import './AccomplishmentReport.scss'

const $q = useQuasar()
const authStore = useAuthStore()

// State
const dateStart = ref('')
const dateEnd = ref('')
const loading = ref(true)
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
  { name: 'resolution', label: 'Resolution', field: 'resolution', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'center' },
  { name: 'date', label: 'Date Resolved', field: 'created', align: 'right', sortable: true }
]

const formatOptions = [
  { label: 'Bulleted List', value: 'bullets' },
  { label: 'Detailed Summary', value: 'detailed' },
  { label: 'CSV / Table', value: 'csv' }
]

onMounted(() => {
  // Set default dates to current month
  const today = new Date()
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
  dateStart.value = firstDay.toISOString().split('T')[0]
  dateEnd.value = today.toISOString().split('T')[0]
  
  fetchClosedTickets()
})

async function fetchClosedTickets() {
  loading.value = true
  try {
    const res = await api.get('/getTickets')
    const data = res.data?.data || res.data || []
    
    // Only keep closed/resolved tickets
    const closed = data.filter(t => ['CLOSE', 'RESOLVED'].includes((t.status || '').toUpperCase()))
    
    tickets.value = closed.map(t => ({
      id: t.id,
      real_id: t.id,
      ticket_no: t.ticket_no || `#${t.id}`,
      title: t.issue || 'No Title',
      category: t.problem_category ? t.problem_category.categories : 'Uncategorized',
      requester: t.user ? (t.user.name || `${t.user.first_name} ${t.user.last_name}`) : 'Unknown',
      priority: t.urgency || 'NORMAL',
      status: t.status || 'CLOSE',
      created: (t.date_submitted || t.created_at || '').split('T')[0],
      description: t.description || '',
      remarks: t.remarks || '',
      resolution: t.resolution || ''
    }))

    filterTickets()
  } catch (err) {
    console.error('Failed to fetch closed tickets', err)
    $q.notify({ type: 'negative', message: 'Failed to load closed tickets.' })
  } finally {
    loading.value = false
  }
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

  const count = selectedTickets.value.length
  const staff = authStore.userName
  const period = `${dateStart.value} to ${dateEnd.value}`

  // Build a smart summary of the unique categories resolved
  const categories = [...new Set(selectedTickets.value.map(t => t.category).filter(Boolean))]
  const categoryList = categories.length > 0 ? categories.join(', ') : 'various categories'
  const highPriorityCount = selectedTickets.value.filter(t => t.priority === 'HIGH' || t.priority === 'CRITICAL').length
  const priorityNote = highPriorityCount > 0 ? ` including ${highPriorityCount} high-priority item(s)` : ''
  const summaryLine = `${staff} successfully resolved ${count} ticket(s)${priorityNote} covering ${categoryList} during the period ${period}.`

  let text = `ACCOMPLISHMENT REPORT\n`
  text += `${'='.repeat(60)}\n`
  text += `Period  : ${period}\n`
  text += `Staff   : ${staff}\n`
  text += `Generated: ${new Date().toLocaleString()}\n`
  text += `${'='.repeat(60)}\n\n`
  text += `SUMMARY\n`
  text += `${'-'.repeat(60)}\n`
  text += `${summaryLine}\n\n`

  if (reportFormat.value === 'bullets') {
    text += `RESOLVED TICKETS (${count})\n`
    text += `${'-'.repeat(60)}\n`
    selectedTickets.value.forEach(t => {
      const id = t.ticket_no || `#${t.id}`
      text += `• [${id}] ${t.title} (${t.created})\n`
      if (t.resolution) {
        text += `  Resolution: ${t.resolution}\n`
      }
    })
  }
  else if (reportFormat.value === 'detailed') {
    text += `DETAILED RESOLUTION LOG (${count} tickets)\n`
    text += `${'-'.repeat(60)}\n`
    selectedTickets.value.forEach((t, i) => {
      const id = t.ticket_no || `#${t.id}`
      text += `${i + 1}. Ticket ${id}: ${t.title}\n`
      text += `   Category  : ${t.category}\n`
      text += `   Priority  : ${t.priority}\n`
      text += `   Requester : ${t.requester}\n`
      text += `   Resolved  : ${t.created}\n`
      text += `   Remarks   : ${t.remarks || '—'}\n`
      text += `   Resolution: ${t.resolution || '—'}\n\n`
    })
  }
  else if (reportFormat.value === 'csv') {
    text += `Ticket No,Title,Category,Requester,Priority,Date Resolved,Resolution\n`
    selectedTickets.value.forEach(t => {
      const id = t.ticket_no || `#${t.id}`
      const res = (t.resolution || '').replace(/"/g, '""')
      text += `"${id}","${t.title}","${t.category}","${t.requester}","${t.priority}","${t.created}","${res}"\n`
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
