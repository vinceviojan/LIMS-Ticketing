<template>
  <q-page class="staff-dash">
    <!-- ── Welcome ─────────────────────────────────────────────── -->
    <div class="staff-dash__welcome">
      <div>
        <div class="staff-dash__greeting">Good {{ timeOfDay }}, {{ firstName }} 👋</div>
        <div class="staff-dash__sub">Here are the tickets in your queue today.</div>
      </div>
      <div class="staff-dash__date">{{ currentDate }}</div>
    </div>

    <!-- ── Stats ───────────────────────────────────────────────── -->
    <div class="staff-dash__stats">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="staff-stat"
        :class="`staff-stat--${stat.color}`"
      >
        <div class="staff-stat__icon-wrap">
          <q-icon :name="stat.icon" size="24px" />
        </div>
        <div>
          <div class="staff-stat__value">{{ stat.value }}</div>
          <div class="staff-stat__label">{{ stat.label }}</div>
        </div>
      </div>
    </div>

    <!-- ── Priority Overview and Queue ──────────────────────────── -->
    <div class="row q-col-gutter-lg q-mt-sm">
      <div class="col-12 col-md-4 col-lg-3">
        <div class="staff-dash__section-title">Priority Overview</div>
        <q-card flat bordered class="staff-priority-card q-mt-md">
          <div class="staff-priority-pie" :style="{ background: priorityPieChart }"></div>
          <div class="staff-priority-legend">
            <div class="legend-item"><div class="legend-color bg-orange-8"></div>High ({{ priorityStats.high }})</div>
            <div class="legend-item"><div class="legend-color bg-amber-8"></div>Normal ({{ priorityStats.normal }})</div>
            <div class="legend-item"><div class="legend-color bg-green-5"></div>Low ({{ priorityStats.low }})</div>
          </div>
        </q-card>
      </div>
      
      <div class="col-12 col-md-8 col-lg-9">
        <div class="staff-dash__section-title">Unassigned Tickets</div>
        <q-table
      :rows="tickets"
      :columns="ticketColumns"
      row-key="id"
      flat
      bordered
      class="staff-queue q-mt-md"
    >
      <template #body="props">
        <q-tr :props="props" class="cursor-pointer" @click="openTicketModal(props.row)">
          <!-- Ticket ID -->
          <q-td key="id" :props="props">
            {{ props.row.id }}
          </q-td>

          <!-- Title -->
          <q-td key="title" :props="props">
            {{ props.row.issue }}
          </q-td>

          <!-- Requester -->
          <q-td key="requester" :props="props">
            {{ props.row.user?.name ?? '—' }}
          </q-td>

          <!-- Category -->
          <q-td key="category" :props="props">
            {{ props.row.problem_category?.categories ?? '—' }}
          </q-td>

          <!-- Priority -->
          <q-td key="priority" :props="props">
            <q-badge
              :color="getPriorityColor(props.row.urgency)"
              :label="props.row.urgency ?? '—'"
            />
          </q-td>

          <!-- Status -->
          <q-td key="status" :props="props">
            <q-badge color="primary" :label="props.row.status" />
          </q-td>

          <!-- Created -->
          <q-td key="created" :props="props">
            {{ props.row.created }}
          </q-td>
        </q-tr>
      </template>

      <!-- Empty State -->
      <template #no-data>
        <div class="full-width row flex-center q-pa-lg text-grey">
          <div class="text-center">
            <q-icon name="task_alt" size="48px" color="positive" />
            <div class="q-mt-sm">All tickets resolved — great work!</div>
          </div>
        </div>
      </template>
    </q-table>
      </div> <!-- End col -->
    </div> <!-- End row -->

    <!-- ── Ticket Details Modal ─────────────────────────────────── -->
    <ViewTicketModal
      v-model="showTicketModal"
      :ticket="selectedTicket"
      @refresh="fetchAssignedTickets(); fetchTickets();"
    />

    <!-- ── Category Browse ──────────────────────────────────────── -->
    <div class="staff-dash__section-title">Browse Problem Categories</div>
    <div class="staff-categories">
      <div v-for="cat in categories" :key="cat.type" class="staff-cat-group">
        <div class="staff-cat-group__type">{{ cat.type }}</div>
        <div class="staff-cat-group__items">
          <span v-for="item in cat.items" :key="item" class="staff-cat-chip">{{ item }}</span>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'
import ViewTicketModal from '@/components/ViewTicketModal.vue'

const $q = useQuasar()
const authStore = inject('authStore')

const showTicketModal = ref(false)
const selectedTicket = ref(null)
const loading = ref(false)

async function openTicketModal(ticket) {
  selectedTicket.value = ticket
  showTicketModal.value = true

  console.log('Viewing ticket:', selectedTicket.value)
}

const firstName = computed(() => {
  const name = authStore.userName ?? ''
  return name.split(' ')[0] || 'Staff'
})

const timeOfDay = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'morning'
  if (h < 17) return 'afternoon'
  return 'evening'
})

const currentDate = computed(() =>
  new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }),
)

const assignedTickets = ref([])
const tickets = ref([])

const stats = computed(() => [
  { label: 'Assigned', icon: 'inbox', value: assignedTickets.value.length, color: 'blue' },
  {
    label: 'Open',
    icon: 'radio_button_unchecked',
    value: assignedTickets.value.filter((t) => t.status === 'OPEN').length,
    color: 'orange',
  },
  {
    label: 'Pending',
    icon: 'pending_actions',
    value: assignedTickets.value.filter((t) => t.status === 'PENDING').length,
    color: 'yellow',
  },
  {
    label: 'Resolved',
    icon: 'task_alt',
    value: assignedTickets.value.filter((t) => t.status === 'RESOLVED').length,
    color: 'green',
  },
  {
    label: 'Unassigned',
    icon: 'person_off',
    value: tickets.value.filter((t) => t.status === 'OPEN' && t.assigned_staff_id === null).length,
    color: 'orange',
  },
])

const categories = ref([
  {
    type: 'Hardware',
    items: ['Laptop Issue', 'Printer Problem', 'Monitor Fault', 'Peripheral Device'],
  },
  {
    type: 'Software',
    items: ['OS Crash', 'Application Error', 'Installation Request', 'Update Issue'],
  },
  { type: 'Network', items: ['Slow Connection', 'No Internet', 'VPN Problem', 'Wi-Fi Issue'] },
  { type: 'Account', items: ['Password Reset', 'Access Request', 'Account Lock'] },
])

const priorityStats = computed(() => {
  const all = tickets.value 
  let high = 0, normal = 0, low = 0
  
  all.forEach(t => {
    const u = (t.urgency || '').toUpperCase()
    if (u === 'HIGH') high++
    else if (u === 'NORMAL') normal++
    else if (u === 'LOW') low++
  })
  
  return { high, normal, low, total: all.length }
})

const priorityPieChart = computed(() => {
  const { high, normal, total } = priorityStats.value
  if (!total) return 'conic-gradient(#f3f4f6 0 100%)'
  
  const pHigh = (high / total) * 100
  const pNormal = (normal / total) * 100
  return `conic-gradient(#f97316 0 ${pHigh}%, #f59e0b ${pHigh}% ${pHigh + pNormal}%, #10b981 ${pHigh + pNormal}% 100%)`
})

const ticketColumns = [
  // { name: 'id', label: 'Ticket ID', field: 'ticket', align: 'left', sortable: true },
  { name: 'title', label: 'Title', field: 'issue', align: 'left', sortable: true },
  {
    name: 'requester',
    label: 'Requester',
    field: (row) => row.user?.name ?? '—',
    align: 'left',
    sortable: true,
  },
  {
    name: 'category',
    label: 'Category',
    field: (row) => row.problem_category?.categories ?? '—',
    align: 'left',
    sortable: true,
  },
  {
    name: 'priority',
    label: 'Priority',
    field: (row) => row.urgency ?? '—',
    align: 'center',
    sortable: true,
  },
  { name: 'status', label: 'Status', field: 'status', align: 'center', sortable: true },
]

function getPriorityColor(priority) {
  switch (priority) {
    case 'HIGH':
      return 'orange'
    case 'NORMAL':
      return 'warning'
    case 'LOW':
      return 'positive'
    default:
      return 'grey'
  }
}

// ── Lifecycle ────────────────────────────────────────────────
onMounted(async () => {
  await fetchAssignedTickets()
  await fetchTickets()
})

async function fetchAssignedTickets() {
  loading.value = true
  try {
    const userId = authStore.user?.id

    const res = await api.get('/getTickets', {
      params: {
        user_id: userId,
      },
    })
    const data = res.data?.data || res.data || []

    assignedTickets.value = data
  } catch (err) {
    console.error('Failed to load tickets', err)
    $q.notify({ type: 'negative', message: 'Failed to load tickets.' })
  } finally {
    loading.value = false
  }
}

async function fetchTickets() {
  loading.value = true
  try {
    const res = await api.get('/getOpenTickets')
    const data = res.data?.data || res.data || []

    tickets.value = data
  } catch (err) {
    console.error('Failed to load tickets', err)
    $q.notify({ type: 'negative', message: 'Failed to load tickets.' })
  } finally {
    loading.value = false
  }
}

// function resolve(ticket) {
//   ticket.status = 'RESOLVED'
//   const idx = assignedTickets.value.indexOf(ticket)
//   setTimeout(() => assignedTickets.value.splice(idx, 1), 800)
//   $q.notify({
//     type: 'positive',
//     message: `Ticket #${ticket.id} marked as resolved.`,
//     position: 'top-right',
//     timeout: 2000,
//   })
// }
</script>

<style lang="scss" scoped>
@import '@/css/themes.scss';

.staff-dash {
  padding: 32px;
  background: $min-bg;
  min-height: 100vh;

  &__welcome {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 28px;
  }

  &__greeting {
    font-size: 1.5rem;
    font-weight: 800;
    color: $min-text;
    font-family: 'Nunito', sans-serif;
  }

  &__sub {
    color: $min-text-soft;
    font-size: 0.86rem;
    margin-top: 4px;
  }

  &__date {
    font-size: 0.8rem;
    font-weight: 600;
    color: $min-text-soft;
    align-self: center;
  }

  &__stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
  }

  &__section-title {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: $min-text-soft;
    margin-bottom: 12px;
  }

  &__empty {
    padding: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: $min-text-soft;
  }
}

// ── Stat Cards ────────────────────────────────────────────────
.staff-stat {
  @include min-card();
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  transition: transform 0.18s ease;
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  &__icon-wrap {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  &__value {
    font-size: 1.5rem;
    font-weight: 800;
    color: $min-text;
    font-family: 'Nunito', sans-serif;
    line-height: 1;
  }

  &__label {
    font-size: 0.73rem;
    color: $min-text-soft;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-top: 3px;
  }

  &--blue .staff-stat__icon-wrap {
    background: #eff6ff;
    color: #3b82f6;
    border: 1px solid #bfdbfe;
  }
  &--orange .staff-stat__icon-wrap {
    background: #fff7ed;
    color: #f97316;
    border: 1px solid #fed7aa;
  }
  &--yellow .staff-stat__icon-wrap {
    background: #fefce8;
    color: #eab308;
    border: 1px solid #fef08a;
  }
  &--green .staff-stat__icon-wrap {
    background: #f0fdf4;
    color: $positive;
    border: 1px solid #bbf7d0;
  }
}

// ── Ticket Queue ──────────────────────────────────────────────
.staff-queue {
  @include min-card();
  margin-bottom: 32px;
  overflow: hidden;

  &__item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 20px;
    border-bottom: 1px solid $min-border;
    transition: background 0.15s ease;
    border-left: 4px solid transparent;

    &:last-child {
      border-bottom: none;
    }
    &:hover {
      background: $min-bg;
    }

    &--low {
      border-left-color: $min-text-soft;
    }
    &--medium {
      border-left-color: #f59e0b;
    }
    &--high {
      border-left-color: $accent-login;
    }
    &--critical {
      border-left-color: #ef4444;
    }
  }

  &__priority-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;

    &--low {
      background: $min-text-soft;
    }
    &--medium {
      background: #f59e0b;
    }
    &--high {
      background: $accent-login;
    }
    &--critical {
      background: #ef4444;
    }
  }

  &__body {
    flex: 1;
  }

  &__title {
    font-size: 0.9rem;
    font-weight: 600;
    color: $min-text;
  }

  &__meta {
    font-size: 0.74rem;
    color: $min-text-soft;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  &__actions {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  &__status {
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #fff;

    &--open {
      background: $accent-login;
    }
    &--pending {
      background: #f59e0b;
    }
    &--resolved {
      background: $positive;
    }
    &--closed {
      background: $min-text-soft;
    }
  }
}

// ── Category Browser ──────────────────────────────────────────
.staff-categories {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
}

.staff-cat-group {
  @include min-card();
  padding: 16px;

  &__type {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: $accent-login;
    margin-bottom: 10px;
  }

  &__items {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
}

.staff-cat-chip {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  color: $min-text-soft;
  background: $min-surface;
  border: 1px solid $min-border;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

// ── Priority Pie Chart ─────────────────────────────────────────
.staff-priority-card {
  padding: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
  height: calc(100% - 30px);
}

.staff-priority-pie {
  width: 160px;
  height: 160px;
  border-radius: 50%;
  box-shadow: inset 0 2px 10px rgba(0,0,0,0.06), 0 4px 14px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
  border: 4px solid #fff;
  &:hover {
    transform: scale(1.04);
  }
}

.staff-priority-legend {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.85rem;
  font-weight: 700;
  color: $min-text-soft;
}

.legend-color {
  width: 14px;
  height: 14px;
  border-radius: 4px;
}
</style>
