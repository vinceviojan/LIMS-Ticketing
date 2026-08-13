<template>
  <q-page class="user-dash">
    <!-- ── Welcome Banner ─────────────────────────────────────────── -->
    <div class="user-dash__welcome">
      <div class="user-dash__welcome-left">
        <div class="user-dash__greeting">
          Good {{ timeOfDay }}, <span class="greeting-name">{{ firstName }}</span>
        </div>
        <div class="user-dash__sub">
          {{ currentDate }} ·
          {{ myTickets.filter((t) => ['OPEN', 'ON-GOING', 'PENDING'].includes(t.status)).length }}
          active support tickets
        </div>
      </div>
      <div class="user-dash__welcome-actions">
        <q-btn
          color="primary"
          label="Submit a Ticket"
          icon="add_circle_outline"
          unelevated
          no-caps
          style="border-radius: 10px; font-weight: 600"
          :disable="Boolean(unratedTicket)"
          @click="openBlankDialog"
        >
          <q-tooltip v-if="unratedTicket" class="bg-amber-9">
            Please rate your resolved ticket before submitting a new one.
          </q-tooltip>
        </q-btn>
        <q-btn
          flat
          no-caps
          icon="refresh"
          label="Refresh"
          color="grey-7"
          style="border-radius: 10px"
          :loading="loading"
          @click="fetchTickets()"
        />
      </div>
    </div>

    <!-- ── Action Required Banner ─────────────────────────────── -->
    <q-banner
      v-if="unratedTicket"
      rounded
      class="q-mb-lg bg-amber-1 text-amber-10"
      style="border: 1px solid #fde68a; border-left: 5px solid #f59e0b; border-radius: 14px"
    >
      <template #avatar>
        <q-avatar color="amber-3" text-color="amber-10" icon="rate_review" />
      </template>
      <div class="text-subtitle2 text-weight-bold">
        Action Required: Rate & Review Resolved Ticket
      </div>
      <div class="text-caption">
        Ticket <strong>{{ unratedTicket.ticket_no || '#' + unratedTicket.id }}</strong> ({{
          unratedTicket.category
        }}) has been resolved. Please rate the service to submit new tickets.
      </div>
      <template #action>
        <q-btn
          flat
          unelevated
          no-caps
          color="amber-9"
          label="Rate Now"
          icon="star"
          class="text-weight-bold"
          @click="openRatingModal(unratedTicket)"
        />
      </template>
    </q-banner>

    <!-- ── Stats Grid ───────────────────────────────────────────── -->
    <div class="user-dash__stats">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="user-stat"
        :class="`user-stat--${stat.color}`"
      >
        <div class="user-stat__icon-wrap">
          <q-icon :name="stat.icon" size="22px" />
        </div>
        <div class="user-stat__body">
          <div class="user-stat__value">
            <q-skeleton v-if="loading" type="text" width="30px" dense />
            <span v-else>{{ stat.value }}</span>
          </div>
          <div class="user-stat__label">{{ stat.label }}</div>
        </div>
        <q-badge
          v-if="stat.badge"
          :color="stat.badgeColor || 'grey-4'"
          :text-color="stat.badgeTextColor || 'grey-7'"
          class="user-stat__badge"
        >
          {{ stat.badge }}
        </q-badge>
      </div>
    </div>

    <!-- ── My Tickets Section ───────────────────────────────────── -->
    <div class="row items-center justify-between q-mb-md">
      <div class="section-label" style="margin-bottom: 0">
        My Tickets
        <q-badge
          color="primary"
          text-color="white"
          class="q-ml-sm"
          style="font-weight: 700; font-size: 0.72rem"
        >
          {{ filteredMyTickets.length }}
        </q-badge>
      </div>
      <div class="row items-center q-gutter-sm">
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
        <q-btn
          flat
          no-caps
          dense
          color="primary"
          label="View All"
          icon-right="arrow_forward"
          size="sm"
          to="/user/ticket-management"
        />
      </div>
    </div>

    <!-- Toolbar: Search + Filter -->
    <div class="row q-gutter-sm q-mb-md items-center">
      <q-input
        v-model="ticketSearch"
        dense
        outlined
        clearable
        placeholder="Search my tickets…"
        bg-color="white"
        style="min-width: 220px"
        class="col-12 col-sm-auto"
      >
        <template #prepend><q-icon name="search" color="grey-5" /></template>
      </q-input>

      <q-select
        v-model="ticketFilterStatus"
        :options="[
          { label: 'Open', value: 'OPEN' },
          { label: 'On-going', value: 'ON-GOING' },
          { label: 'Pending', value: 'PENDING' },
          { label: 'Resolved', value: 'RESOLVED' },
        ]"
        label="Status"
        dense
        outlined
        clearable
        emit-value
        map-options
        bg-color="white"
        style="min-width: 140px"
      />

      <q-btn
        v-if="ticketSearch || ticketFilterStatus"
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

    <!-- Ticket ListView with Lazy Loading -->
    <div class="q-mb-xl">
      <TicketListView
        :tickets="filteredMyTickets"
        :displayMode="displayMode"
        :loading="loading"
        :readonly="true"
        :selectable="false"
        :perPage="5"
        :hideTitle="true"
        @view-ticket="openViewModal"
      />
    </div>

    <!-- ── Common Issues / Quick Ticket Filing ──────────────────── -->
    <div class="section-label q-mb-md">Common Issues (Quick File)</div>

    <div class="help-grid q-mb-xl">
      <q-card
        v-for="topic in helpTopics"
        :key="topic.title"
        flat
        bordered
        class="help-card cursor-pointer"
        @click="prefillTicket(topic)"
      >
        <q-card-section class="q-pa-lg">
          <div class="help-card__icon-wrap q-mb-md">
            <q-icon :name="topic.icon" size="24px" color="primary" />
          </div>
          <div class="text-subtitle2 text-weight-bold text-grey-9 q-mb-xs">{{ topic.title }}</div>
          <div class="text-caption text-grey-6 q-mb-md" style="line-height: 1.5">
            {{ topic.desc }}
          </div>
          <div class="row items-center text-primary" style="font-size: 0.75rem; font-weight: 700">
            File a ticket <q-icon name="arrow_forward" size="13px" class="q-ml-xs" />
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- ── Modals ─────────────────────────────────────────────────── -->
    <UserAddTicketModal
      v-model="showDialog"
      :category-options="categoryOptions"
      :prefill="dialogPrefill"
      @refresh="fetchTickets"
    />

    <ViewTicketModal v-model="showViewModal" :ticket="selectedTicket" @refresh="fetchTickets" />

    <RatingFeedbackModal
      v-model="showRatingModal"
      :ticket="ratingTicket"
      @submitted="fetchTickets"
    />
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'
import UserAddTicketModal from '../../components/UserAddTicketModal.vue'
import ViewTicketModal from '../../components/ViewTicketModal.vue'
import RatingFeedbackModal from '../../components/RatingFeedbackModal.vue'
import TicketListView from '../../components/TicketListView.vue'

const $q = useQuasar()
const authStore = inject('authStore')

const loading = ref(false)
const showDialog = ref(false)
const dialogPrefill = ref({})
const showViewModal = ref(false)
const selectedTicket = ref({})
const showRatingModal = ref(false)
const ratingTicket = ref({})

const displayMode = ref('table')
const ticketSearch = ref('')
const ticketFilterStatus = ref(null)

const firstName = computed(() => {
  const name = authStore.userName ?? ''
  return name.split(' ')[0] || 'User'
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

const categoryOptions = ref([])
const myTickets = ref([])

const filteredMyTickets = computed(() => {
  let data = myTickets.value
  if (ticketFilterStatus.value) {
    data = data.filter((t) => (t.status || '').toUpperCase() === ticketFilterStatus.value)
  }
  if (ticketSearch.value) {
    const q = ticketSearch.value.toLowerCase()
    data = data.filter(
      (t) =>
        (t.title || '').toLowerCase().includes(q) ||
        (t.category || '').toLowerCase().includes(q) ||
        (t.ticket_no || '').toLowerCase().includes(q),
    )
  }
  return data
})

const stats = computed(() => [
  {
    label: 'Total Tickets',
    icon: 'confirmation_number',
    value: myTickets.value.length,
    color: 'blue',
  },
  {
    label: 'Open',
    icon: 'radio_button_unchecked',
    value: myTickets.value.filter((t) => t.status === 'OPEN').length,
    color: 'orange',
  },
  {
    label: 'On-going',
    icon: 'autorenew',
    value: myTickets.value.filter((t) => t.status === 'ON-GOING').length,
    color: 'blue',
    badge: 'Active',
    badgeColor: 'blue-1',
    badgeTextColor: 'blue-8',
  },
  {
    label: 'Pending',
    icon: 'pending_actions',
    value: myTickets.value.filter((t) => t.status === 'PENDING').length,
    color: 'yellow',
  },
  {
    label: 'Resolved',
    icon: 'task_alt',
    value: myTickets.value.filter((t) => t.status === 'RESOLVED').length,
    color: 'green',
  },
])

const unratedTicket = computed(() =>
  myTickets.value.find((t) => ['CLOSE'].includes(t.status) && (!t.rating || !t.feedback)),
)

function resetFilters() {
  ticketSearch.value = ''
  ticketFilterStatus.value = null
}

function openViewModal(ticket) {
  selectedTicket.value = ticket
  showViewModal.value = true
}

function openRatingModal(ticket) {
  ratingTicket.value = ticket
  showRatingModal.value = true
}

onMounted(async () => {
  await Promise.all([fetchCategories(), fetchTickets()])
})

async function fetchCategories() {
  try {
    const { data } = await api.get('/problem-categories')
    categoryOptions.value = (data.data || data || []).map((c) => ({
      label: c.categories,
      value: c.id,
    }))
  } catch (err) {
    console.error('Failed to load categories', err)
  }
}

async function fetchTickets() {
  loading.value = true
  try {
    const { data } = await api.get('/tickets')
    const rawList = data.data || data || []
    myTickets.value = rawList.map((ticket) => ({
      id: ticket.id,
      real_id: ticket.id,
      ticket_no: ticket.ticket_no,
      title: ticket.issue || 'Untitled ticket',
      requester: ticket.user ? `${ticket.user.first_name} ${ticket.user.last_name}` : 'User',
      assignedStaff: ticket.assigned_staff
        ? ticket.assigned_staff.name ||
          `${ticket.assigned_staff.first_name} ${ticket.assigned_staff.last_name}`
        : '',
      category: ticket.problem_category?.categories || 'Uncategorized',
      priority: ticket.urgency || 'NORMAL',
      status: ticket.status || 'OPEN',
      description: ticket.description || '',
      remarks: ticket.remarks || '',
      rating: ticket.rating,
      feedback: ticket.feedback,
      attachments: ticket.attachments || [],
      upload_intralab: ticket.upload_intralab,
      upload_limsportal: ticket.upload_limsportal,
      created:
        ticket.date_submitted || ticket.created_at
          ? new Date(ticket.date_submitted || ticket.created_at).toLocaleDateString('en-US', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
            })
          : '—',
    }))

    if (unratedTicket.value && !showRatingModal.value) {
      openRatingModal(unratedTicket.value)
    }
  } catch (error) {
    console.error('Failed to load tickets', error)
    $q.notify({ type: 'negative', message: 'Failed to load your tickets.' })
  } finally {
    loading.value = false
  }
}

// ── Help Topics ──────────────────────────────────────────────────
const helpTopics = [
  {
    title: 'Laptop / Hardware',
    icon: 'laptop',
    desc: 'Device not powering on, broken peripherals, screen issues.',
    category: 'Hardware',
  },
  {
    title: 'Software / App Error',
    icon: 'bug_report',
    desc: "Application crashes, errors, or software won't respond.",
    category: 'Software',
  },
  {
    title: 'Network / Internet',
    icon: 'wifi',
    desc: 'Slow or no connection, VPN issues, Wi-Fi problems.',
    category: 'Network',
  },
  {
    title: 'Account Access',
    icon: 'manage_accounts',
    desc: 'Password reset, account locked or permission issues.',
    category: 'Account',
  },
  {
    title: 'Scheduling / Requesting',
    icon: 'event_note',
    desc: 'Book facility/equipment reservation, request lab slot, or schedule support.',
    category: 'Scheduling Request',
  },
]

function openBlankDialog() {
  if (unratedTicket.value) {
    $q.notify({
      type: 'warning',
      message: 'Please rate your resolved ticket first.',
      icon: 'rate_review',
    })
    openRatingModal(unratedTicket.value)
    return
  }
  dialogPrefill.value = {}
  showDialog.value = true
}

function prefillTicket(topic) {
  if (unratedTicket.value) {
    $q.notify({
      type: 'warning',
      message: 'Please rate your resolved ticket first.',
      icon: 'rate_review',
    })
    openRatingModal(unratedTicket.value)
    return
  }
  dialogPrefill.value = { title: topic.title, category: null }
  showDialog.value = true
}
</script>

<style lang="scss" scoped>
@import '@/css/themes.scss';

.user-dash {
  padding: 28px 32px;
  background: $min-bg;
  min-height: 100vh;
}

.section-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: $min-text-soft;
  margin-bottom: 10px;
}

// ── Welcome Banner ────────────────────────────────────────────
.user-dash__welcome {
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

.user-dash__greeting {
  font-size: 1.35rem;
  font-weight: 800;
  color: $min-text;
  font-family: 'Nunito', sans-serif;

  .greeting-name {
    color: $primary;
  }
}

.user-dash__sub {
  color: $min-text-soft;
  font-size: 0.83rem;
  margin-top: 4px;
}

.user-dash__welcome-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

// ── Stats Grid ────────────────────────────────────────────────
.user-dash__stats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}

.user-stat {
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

  &--blue .user-stat__icon-wrap {
    background: #eff6ff;
    color: #3b82f6;
    border: 1px solid #bfdbfe;
  }
  &--orange .user-stat__icon-wrap {
    background: #fff7ed;
    color: #f97316;
    border: 1px solid #fed7aa;
  }
  &--yellow .user-stat__icon-wrap {
    background: #fefce8;
    color: #eab308;
    border: 1px solid #fef08a;
  }
  &--green .user-stat__icon-wrap {
    background: #f0fdf4;
    color: $positive;
    border: 1px solid #bbf7d0;
  }
}

// ── Help Grid ─────────────────────────────────────────────────
.help-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 14px;
}

.help-card {
  border-radius: 14px !important;
  transition:
    transform 0.18s ease,
    box-shadow 0.18s ease;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    border-color: var(--q-primary) !important;
  }

  &__icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(var(--q-primary-rgb, 25, 118, 210), 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>
