<template>
  <div>
    <template v-if="!loading && tickets.length">
      <!-- Grid View -->
      <div v-if="displayMode === 'card'" class="row q-col-gutter-lg">
        <div
          v-for="ticket in paginatedTickets"
          :key="ticket.id"
          class="col-12 col-sm-6 col-md-4"
        >
          <q-card
            class="ticket-card cursor-pointer bg-white"
            flat
            @click="$emit('view-ticket', ticket)"
          >
            <div class="q-pa-md col flex column justify-between" style="overflow: hidden; height: 100%;">
              <div>
                <!-- 1. Ticket Number & Edit Icon -->
                <div class="row items-center justify-between q-mb-xs">
                  <span class="text-caption text-weight-bolder text-grey-6 ellipsis" style="font-size: 0.75rem; letter-spacing: 0.02em;">
                    {{ ticket.ticket_no || '#' + ticket.id }}
                  </span>
                  <q-btn
                    v-if="!readonly"
                    flat round dense
                    size="sm"
                    icon="edit"
                    color="grey-6"
                    @click.stop="$emit('edit-ticket', ticket)"
                  />
                </div>

                <!-- 2. Title -->
                <div
                  class="text-subtitle1 text-weight-bold text-grey-9 ellipsis-2-lines q-mb-xs"
                  style="line-height: 1.35; height: 42px; overflow: hidden;"
                  :title="ticket.title"
                >
                  {{ ticket.title }}
                </div>

                <!-- 3. Requester & 5. Date -->
                <div class="row items-center justify-between no-wrap q-mb-xs" style="gap: 8px;">
                  <span class="text-caption text-weight-bold text-grey-9 ellipsis" style="min-width: 0; flex: 1;" :title="ticket.requester">
                    {{ ticket.requester }}
                  </span>
                  <span class="text-caption text-grey-6 flex-shrink-0" style="font-size: 0.75rem;">
                    {{ ticket.created }}
                  </span>
                </div>

                <!-- 4. Category -->
                <div class="text-caption text-grey-7 text-weight-medium ellipsis q-mb-xs" style="font-size: 0.75rem;" :title="ticket.category">
                  {{ ticket.category }}
                </div>
              </div>

              <!-- 6. Priority & 7. Status -->
              <div class="row items-center justify-between dashed-top q-pt-sm no-wrap" style="gap: 8px; margin-top: auto;">
                <span
                  class="priority-tag flex-shrink-0"
                  :style="{
                    backgroundColor: getPriorityStyle(ticket.priority).bg,
                    color: getPriorityStyle(ticket.priority).color,
                    borderColor: getPriorityStyle(ticket.priority).border
                  }"
                >
                  {{ ticket.priority }}
                </span>
                <span
                  class="status-tag flex-shrink-0"
                  :style="{
                    backgroundColor: getStatusStyle(ticket.status).bg,
                    color: getStatusStyle(ticket.status).color,
                    borderColor: getStatusStyle(ticket.status).border
                  }"
                >
                  {{ ticket.status }}
                </span>
              </div>
            </div>
          </q-card>
        </div>
      </div>

      <!-- Table View -->
      <div v-else-if="displayMode === 'table'" class="ticket-table-wrapper bg-white border-radius-12" style="border: 1px solid #dbe2ea; overflow: hidden;">
        <q-table
          :rows="paginatedTickets"
          :columns="tableColumns"
          row-key="id"
          flat
          hide-bottom
          :selection="selectable ? 'multiple' : 'none'"
          v-model:selected="selectedTickets"
          :pagination="{ rowsPerPage: 0 }"
          @row-click="(evt, row) => $emit('view-ticket', row)"
          class="border-radius-12"
          table-header-class="bg-grey-1 text-grey-8 text-weight-bold uppercase-header"
        >
          <template v-slot:body-cell-ticket_no="props">
            <q-td :props="props" class="text-weight-bold text-grey-7">
              {{ props.row.ticket_no || '#' + props.row.id }}
            </q-td>
          </template>
          <template v-slot:body-cell-title="props">
            <q-td :props="props" class="text-weight-bold text-dark ellipsis" style="max-width: 250px;">
              {{ props.row.title }}
            </q-td>
          </template>
          <template v-slot:body-cell-requester="props">
            <q-td :props="props" class="text-weight-bold text-dark">
              {{ props.row.requester }}
            </q-td>
          </template>
          <template v-slot:body-cell-category="props">
            <q-td :props="props" class="text-grey-8">
              {{ props.row.category }}
            </q-td>
          </template>
          <template v-slot:body-cell-created="props">
            <q-td :props="props" class="text-grey-7">
              {{ props.row.created }}
            </q-td>
          </template>
          <template v-slot:body-cell-priority="props">
            <q-td :props="props" class="text-center">
              <span
                class="priority-tag inline-block"
                :style="{
                  backgroundColor: getPriorityStyle(props.row.priority).bg,
                  color: getPriorityStyle(props.row.priority).color,
                  borderColor: getPriorityStyle(props.row.priority).border
                }"
              >
                {{ props.row.priority }}
              </span>
            </q-td>
          </template>
          <template v-slot:body-cell-status="props">
            <q-td :props="props" class="text-center">
              <span
                class="status-tag inline-block"
                :style="{
                  backgroundColor: getStatusStyle(props.row.status).bg,
                  color: getStatusStyle(props.row.status).color,
                  borderColor: getStatusStyle(props.row.status).border
                }"
              >
                {{ props.row.status }}
              </span>
            </q-td>
          </template>
        </q-table>
      </div>

      <!-- Unified Pagination Bar -->
      <div v-if="tickets.length > perPage" class="row items-center justify-between q-mt-lg bg-white q-pa-md border-radius-12" style="border: 1px solid #dbe2ea;">
        <div class="text-caption text-weight-medium text-grey-7">
          Showing {{ ((currentPage - 1) * perPage) + 1 }} - {{ Math.min(currentPage * perPage, tickets.length) }} of {{ tickets.length }} tickets
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
    </template>

    <div v-else-if="loading" class="column items-center justify-center q-pa-xl text-grey-6">
      <q-spinner-dots size="48px" color="primary" />
      <p class="text-h6 q-mt-md">Loading tickets…</p>
    </div>

    <div v-else class="column items-center justify-center q-pa-xl text-grey-5">
      <q-icon name="confirmation_number" size="72px" />
      <p class="text-h6 q-mt-md">No tickets found</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  tickets: { type: Array, required: true },
  displayMode: { type: String, default: 'table' },
  loading: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  selectable: { type: Boolean, default: false }
})

const emit = defineEmits(['view-ticket', 'edit-ticket', 'update:selected'])

const selectedTickets = ref([])
watch(selectedTickets, (newVal) => {
  emit('update:selected', newVal)
})

// ── Pagination State ─────────────────────────────────────────
const currentPage = ref(1)
const perPage = ref(9)

// Reset to page 1 if the underlying tickets array changes significantly (e.g. filters applied)
watch(() => props.tickets, () => {
  currentPage.value = 1
}, { deep: false })

const maxPages = computed(() => {
  return Math.ceil(props.tickets.length / perPage.value)
})

const paginatedTickets = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return props.tickets.slice(start, start + perPage.value)
})

// ── Table Configuration ──────────────────────────────────────
const tableColumns = [
  { name: 'ticket_no', label: 'Ticket #', field: 'ticket_no', align: 'left', sortable: true },
  { name: 'title', label: 'Title', field: 'title', align: 'left', sortable: true },
  { name: 'requester', label: 'Requester', field: 'requester', align: 'left', sortable: true },
  { name: 'category', label: 'Category', field: 'category', align: 'left', sortable: true },
  { name: 'created', label: 'Date', field: 'created', align: 'left', sortable: true },
  { name: 'priority', label: 'Priority', field: 'priority', align: 'center', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'center', sortable: true }
]

// ── Style Helpers ────────────────────────────────────────────
const getStatusStyle = (status) => {
  const s = status?.toUpperCase() || ''
  if (s === 'OPEN') return { bg: '#fff7ed', color: '#c2410c', border: '#ffedd5' }
  if (s === 'PENDING') return { bg: '#fefce8', color: '#ca8a04', border: '#fef3c7' }
  if (s === 'ESCALATED') return { bg: '#faf5ff', color: '#9333ea', border: '#f3e8ff' }
  if (s === 'RESOLVED') return { bg: '#f0fdf4', color: '#16a34a', border: '#dcfce7' }
  if (s === 'CLOSE' || s === 'CLOSED') return { bg: '#f8fafc', color: '#475569', border: '#e2e8f0' }
  if (s === 'CANCEL' || s === 'CANCELED') return { bg: '#fef2f2', color: '#dc2626', border: '#fee2e2' }
  return { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0' }
}

const getPriorityStyle = (priority) => {
  const p = priority?.toUpperCase() || ''
  if (p === 'HIGH') return { bg: '#fff5f5', color: '#e53e3e', border: '#fed7d7' }
  if (p === 'NORMAL') return { bg: '#ebf8ff', color: '#3182ce', border: '#bee3f8' }
  if (p === 'LOW') return { bg: '#f0fff4', color: '#38a169', border: '#c6f6d5' }
  return { bg: '#f1f5f9', color: '#475569', border: '#e2e8f0' }
}
</script>

<style scoped>
.ticket-card {
  min-height: 220px;
  height: auto;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s;
}

.ticket-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  border-color: #dbe2ea;
}

.status-tag, .priority-tag {
  display: inline-block;
  padding: 4px 10px;
  font-size: 0.72rem;
  font-weight: 700;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border: 1px solid transparent;
}

.dashed-top {
  border-top: 1px dashed #e2e8f0;
}

.ellipsis-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
