<template>
  <q-page class="staff-dash">
    <!-- ── Welcome Banner ─────────────────────────────────────────── -->
    <div class="staff-dash__welcome">
      <div class="staff-dash__welcome-left">
        <div class="staff-dash__greeting">
          Good {{ timeOfDay }}, <span class="greeting-name">{{ firstName }}</span>
        </div>
        <div class="staff-dash__sub">
          {{ currentDate }} · {{ unassignedTickets.length }} unassigned tickets awaiting action
        </div>
      </div>
      <div class="staff-dash__welcome-actions">
        <q-btn
          unelevated
          no-caps
          icon="inbox"
          label="My Tickets"
          color="primary"
          class="q-mr-sm"
          style="border-radius: 10px; font-weight: 600"
          to="/staff/ticket-management"
        />
        <q-btn
          flat
          no-caps
          icon="refresh"
          label="Refresh"
          color="grey-7"
          style="border-radius: 10px"
          :loading="loading"
          @click="refreshData"
        />
      </div>
    </div>

    <!-- ── Stats ─────────────────────────────────────────────────── -->
    <div class="staff-dash__stats">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="staff-stat"
        :class="`staff-stat--${stat.color}`"
      >
        <div class="staff-stat__icon-wrap">
          <q-icon :name="stat.icon" size="22px" />
        </div>
        <div class="staff-stat__body">
          <div class="staff-stat__value">
            <q-skeleton v-if="loading" type="text" width="30px" dense />
            <span v-else>{{ stat.value }}</span>
          </div>
          <div class="staff-stat__label">{{ stat.label }}</div>
        </div>
        <q-badge
          v-if="stat.badge"
          :color="stat.badgeColor || 'grey-4'"
          :text-color="stat.badgeTextColor || 'grey-7'"
          class="staff-stat__badge"
        >
          {{ stat.badge }}
        </q-badge>
      </div>
    </div>

    <!-- ── Main Content Row ───────────────────────────────────────── -->
    <div class="row q-col-gutter-lg q-mt-xs">
      <!-- Left column: Priority chart + Quick Actions + Recent Resolved -->
      <div class="col-12 col-md-4 col-lg-3">
        <!-- Priority Overview Card -->
        <div class="section-label">Priority Overview</div>
        <q-card flat bordered class="staff-priority-card q-mb-lg">
          <q-card-section class="text-center q-pb-sm">
            <div class="donut-wrap">
              <div class="staff-priority-pie" :style="{ background: priorityPieChart }"></div>
              <div class="donut-center">
                <div class="donut-total">{{ priorityStats.total }}</div>
                <div class="donut-label">Total</div>
              </div>
            </div>
          </q-card-section>
          <q-separator />
          <q-card-section class="q-pt-sm">
            <div class="staff-priority-legend">
              <div class="legend-item">
                <div class="legend-dot" style="background: #f97316"></div>
                <span>High</span>
                <q-space />
                <strong>{{ priorityStats.high }}</strong>
              </div>
              <div class="legend-item">
                <div class="legend-dot" style="background: #f59e0b"></div>
                <span>Normal</span>
                <q-space />
                <strong>{{ priorityStats.normal }}</strong>
              </div>
              <div class="legend-item">
                <div class="legend-dot" style="background: #10b981"></div>
                <span>Low</span>
                <q-space />
                <strong>{{ priorityStats.low }}</strong>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Quick Actions Card -->
        <div class="section-label">Quick Actions</div>
        <q-card flat bordered class="q-mb-lg">
          <q-list separator>
            <q-item clickable v-ripple to="/staff/ticket-management" class="quick-action-item">
              <q-item-section avatar>
                <q-avatar icon="list_alt" color="primary" text-color="white" size="36px" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">All Tickets</q-item-label>
                <q-item-label caption>View and manage all tickets</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-5" />
              </q-item-section>
            </q-item>
            <q-item
              clickable
              v-ripple
              class="quick-action-item"
              @click="refreshData"
            >
              <q-item-section avatar>
                <q-avatar icon="sync" color="teal" text-color="white" size="36px" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">Refresh Data</q-item-label>
                <q-item-label caption>Sync latest ticket updates</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-5" />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <!-- Recently Resolved -->
        <div class="section-label">Recently Resolved</div>
        <q-card flat bordered>
          <div v-if="loading" class="q-pa-md">
            <q-skeleton v-for="i in 3" :key="i" type="text" class="q-mb-sm" />
          </div>
          <q-list v-else-if="recentResolved.length" separator dense>
            <q-item
              v-for="t in recentResolved"
              :key="t.id"
              clickable
              class="q-py-sm"
              @click="openTicketModal(t)"
            >
              <q-item-section>
                <q-item-label class="text-weight-medium ellipsis" style="font-size: 0.82rem">
                  {{ t.issue || t.title || '—' }}
                </q-item-label>
                <q-item-label caption>{{ t.created }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge color="positive" label="Resolved" />
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="q-pa-md text-center text-grey-6 text-caption">
            No resolved tickets yet
          </div>
        </q-card>
      </div>

      <!-- Right column: Unassigned Tickets with lazy loading -->
      <div class="col-12 col-md-8 col-lg-9">
        <div class="row items-center justify-between q-mb-md">
          <div class="section-label" style="margin-bottom: 0">
            Unassigned Tickets
            <q-badge
              color="orange-2"
              text-color="orange-9"
              class="q-ml-sm"
              style="font-weight: 700; font-size: 0.72rem"
            >
              {{ filteredUnassignedTickets.length }}
            </q-badge>
          </div>
          <q-btn-group
            flat
            class="bg-white"
            style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden"
          >
            <q-btn
              :color="displayMode === 'table' ? 'primary' : 'grey-6'"
              :flat="displayMode !== 'table'"
              :unelevated="displayMode === 'table'"
              icon="list"
              dense
              padding="4px 10px"
              @click="displayMode = 'table'"
            >
              <q-tooltip>Table View</q-tooltip>
            </q-btn>
            <q-btn
              :color="displayMode === 'card' ? 'primary' : 'grey-6'"
              :flat="displayMode !== 'card'"
              :unelevated="displayMode === 'card'"
              icon="grid_view"
              dense
              padding="4px 10px"
              @click="displayMode = 'card'"
            >
              <q-tooltip>Card View</q-tooltip>
            </q-btn>
          </q-btn-group>
        </div>

        <!-- Toolbar: Search + Filter -->
        <div class="row q-gutter-sm q-mb-md items-center">
          <q-input
            v-model="ticketSearch"
            dense
            outlined
            clearable
            placeholder="Search unassigned tickets…"
            bg-color="white"
            style="min-width: 220px"
            class="col-12 col-sm-auto"
          >
            <template #prepend><q-icon name="search" color="grey-5" /></template>
          </q-input>

          <q-select
            v-model="ticketFilterPriority"
            :options="[
              { label: 'Low', value: 'LOW' },
              { label: 'Normal', value: 'NORMAL' },
              { label: 'High', value: 'HIGH' },
            ]"
            label="Priority"
            dense
            outlined
            clearable
            emit-value
            map-options
            bg-color="white"
            style="min-width: 140px"
          />

          <q-btn
            v-if="ticketSearch || ticketFilterPriority"
            flat
            dense
            no-caps
            color="negative"
            icon="restart_alt"
            label="Reset"
            style="border-radius: 8px"
            @click="resetFilters"
          />
        </div>

        <!-- TicketListView with built-in pagination (lazy loading) -->
        <TicketListView
          :tickets="filteredUnassignedTickets"
          :displayMode="displayMode"
          :loading="loading"
          :readonly="true"
          :selectable="false"
          :perPage="5"
          :hideTitle="true"
          @view-ticket="openTicketModal"
        />
      </div>
    </div>

    <!-- ── Ticket Details Modal ─────────────────────────────────── -->
    <ViewTicketModal
      v-model="showTicketModal"
      :ticket="selectedTicket"
      @refresh="refreshData"
    />
  </q-page>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'
import ViewTicketModal from '../../components/ViewTicketModal.vue'
import TicketListView from '../../components/TicketListView.vue'

const $q = useQuasar()
const authStore = inject('authStore')

const showTicketModal = ref(false)
const selectedTicket = ref(null)
const loading = ref(false)

const displayMode = ref('table')
const ticketSearch = ref('')
const ticketFilterPriority = ref(null)

async function openTicketModal(ticket) {
  selectedTicket.value = ticket
  showTicketModal.value = true
}

async function refreshData() {
  await fetchAssignedTickets()
  await fetchTickets()
}

function resetFilters() {
  ticketSearch.value = ''
  ticketFilterPriority.value = null
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

// ── Computed: unassigned open tickets ──────────────────────────
const unassignedTickets = computed(() =>
  tickets.value.filter((t) => t.status === 'OPEN' && t.assigned_staff_id === null),
)

const filteredUnassignedTickets = computed(() => {
  let data = unassignedTickets.value
  if (ticketFilterPriority.value) {
    data = data.filter(
      (t) => (t.urgency || t.priority || '').toUpperCase() === ticketFilterPriority.value,
    )
  }
  if (ticketSearch.value) {
    const q = ticketSearch.value.toLowerCase()
    data = data.filter(
      (t) =>
        (t.issue || t.title || '').toLowerCase().includes(q) ||
        (t.user?.name || t.requester || '').toLowerCase().includes(q) ||
        (t.problem_category?.categories || t.category || '').toLowerCase().includes(q),
    )
  }
  return data
})

// ── Computed: recently resolved from assigned tickets ─────────
const recentResolved = computed(() =>
  assignedTickets.value.filter((t) => t.status === 'RESOLVED').slice(0, 4),
)

const stats = computed(() => [
  {
    label: 'Assigned',
    icon: 'assignment_ind',
    value: assignedTickets.value.length,
    color: 'blue',
  },
  {
    label: 'Open',
    icon: 'radio_button_unchecked',
    value: assignedTickets.value.filter((t) => t.status === 'OPEN').length,
    color: 'orange',
  },
  {
    label: 'On-going',
    icon: 'autorenew',
    value: assignedTickets.value.filter((t) => t.status === 'ON-GOING').length,
    color: 'blue',
    badge: 'Active',
    badgeColor: 'blue-1',
    badgeTextColor: 'blue-8',
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
    value: unassignedTickets.value.length,
    color: 'red',
    badge: unassignedTickets.value.length > 0 ? 'Needs Action' : null,
    badgeColor: 'red-1',
    badgeTextColor: 'red-8',
  },
])

const priorityStats = computed(() => {
  const all = tickets.value
  let high = 0,
    normal = 0,
    low = 0

  all.forEach((t) => {
    const u = (t.urgency || '').toUpperCase()
    if (u === 'HIGH') high++
    else if (u === 'NORMAL') normal++
    else if (u === 'LOW') low++
  })

  return { high, normal, low, total: all.length }
})

const priorityPieChart = computed(() => {
  const { high, normal, total } = priorityStats.value
  if (!total) return 'conic-gradient(#e5e7eb 0 100%)'

  const pHigh = (high / total) * 100
  const pNormal = (normal / total) * 100
  return `conic-gradient(#f97316 0 ${pHigh}%, #f59e0b ${pHigh}% ${pHigh + pNormal}%, #10b981 ${pHigh + pNormal}% 100%)`
})

// ── Lifecycle ────────────────────────────────────────────────
onMounted(async () => {
  await fetchAssignedTickets()
  await fetchTickets()
})

async function fetchAssignedTickets() {
  loading.value = true
  try {
    const userId = authStore.user?.id
    const res = await api.get('/getTickets', { params: { user_id: userId } })
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
    // Map to the shape TicketListView expects
    tickets.value = data.map((t) => ({
      id: t.id,
      real_id: t.id,
      ticket_no: t.ticket_no,
      title: t.issue || 'No Title',
      issue: t.issue,
      requester: t.user ? t.user.first_name + ' ' + t.user.last_name : (t.user?.name ?? 'Unknown'),
      user: t.user,
      email: t.user ? t.user.email : '',
      assignedStaff: t.assigned_staff
        ? t.assigned_staff.name || `${t.assigned_staff.first_name} ${t.assigned_staff.last_name}`
        : '',
      assigned_staff_id: t.assigned_staff_id,
      category: t.problem_category ? t.problem_category.categories : 'Uncategorized',
      problem_category: t.problem_category,
      problem_category_id: t.problem_category_id,
      priority: t.urgency || 'NORMAL',
      urgency: t.urgency,
      status: t.status || 'OPEN',
      description: t.description || '',
      remarks: t.remarks || '',
      resolution: t.resolution || '',
      attachments: t.attachments || [],
      upload_intralab: t.upload_intralab,
      upload_limsportal: t.upload_limsportal,
      hasAttachments: Boolean(
        (t.attachments && t.attachments.length) || t.upload_intralab || t.upload_limsportal,
      ),
      created:
        t.date_submitted || t.created_at
          ? new Date(t.date_submitted || t.created_at).toLocaleDateString('en-US', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
            })
          : '—',
    }))
  } catch (err) {
    console.error('Failed to load tickets', err)
    $q.notify({ type: 'negative', message: 'Failed to load tickets.' })
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
@import '@/css/themes.scss';

// ── Page ─────────────────────────────────────────────────────
.staff-dash {
  padding: 28px 32px;
  background: $min-bg;
  min-height: 100vh;
}

// ── Section Label ─────────────────────────────────────────────
.section-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: $min-text-soft;
  margin-bottom: 10px;
}

// ── Welcome Banner ────────────────────────────────────────────
.staff-dash__welcome {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
  padding: 20px 24px;
  background: $min-surface;
  border: 1px solid $min-border;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.staff-dash__greeting {
  font-size: 1.35rem;
  font-weight: 800;
  color: $min-text;
  font-family: 'Nunito', sans-serif;
  display: flex;
  align-items: center;
  gap: 8px;

  .greeting-icon {
    color: #f59e0b;
  }

  .greeting-name {
    color: $primary;
  }
}

.staff-dash__sub {
  color: $min-text-soft;
  font-size: 0.83rem;
  margin-top: 4px;
}

.staff-dash__welcome-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

// ── Stats Grid ────────────────────────────────────────────────
.staff-dash__stats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}

.staff-stat {
  @include min-card();
  padding: 16px 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition:
    transform 0.18s ease,
    box-shadow 0.18s ease;
  position: relative;
  overflow: hidden;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
  }

  &__icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  &__body {
    flex: 1;
    min-width: 0;
  }

  &__value {
    font-size: 1.45rem;
    font-weight: 800;
    color: $min-text;
    font-family: 'Nunito', sans-serif;
    line-height: 1;
  }

  &__label {
    font-size: 0.71rem;
    color: $min-text-soft;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-top: 3px;
  }

  &__badge {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 0.65rem !important;
    border-radius: 6px;
    padding: 2px 6px;
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
  &--red .staff-stat__icon-wrap {
    background: #fef2f2;
    color: #ef4444;
    border: 1px solid #fecaca;
  }
}

// ── Priority Card ─────────────────────────────────────────────
.staff-priority-card {
  border-radius: 12px;
}

.donut-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 160px;
  height: 160px;
  margin: 8px auto;
}

.staff-priority-pie {
  width: 160px;
  height: 160px;
  border-radius: 50%;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
  border: 4px solid #fff;
  -webkit-mask-image: radial-gradient(circle, transparent 46px, black 47px);
  mask-image: radial-gradient(circle, transparent 46px, black 47px);

  &:hover {
    transform: scale(1.04);
  }
}

.donut-center {
  position: absolute;
  display: flex;
  flex-direction: column;
  align-items: center;
  pointer-events: none;
}

.donut-total {
  font-size: 1.6rem;
  font-weight: 800;
  color: $min-text;
  font-family: 'Nunito', sans-serif;
  line-height: 1;
}

.donut-label {
  font-size: 0.68rem;
  font-weight: 600;
  color: $min-text-soft;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.staff-priority-legend {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.84rem;
  font-weight: 500;
  color: $min-text-soft;

  strong {
    color: $min-text;
    font-weight: 700;
  }
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

// ── Quick Actions ─────────────────────────────────────────────
.quick-action-item {
  min-height: 56px;
  transition: background 0.15s ease;
}

// ── Category Browse ───────────────────────────────────────────
.staff-categories {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 14px;
}

.staff-cat-group {
  border-radius: 12px;
  transition: box-shadow 0.18s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
  }

  &__header {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  &__type {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: $min-text;
  }

  &__items {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
}
</style>
