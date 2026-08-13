<template>
  <q-page class="user-dash">

    <!-- ── Welcome ─────────────────────────────────────────────── -->
    <div class="user-dash__welcome">
      <div>
        <div class="user-dash__greeting">Good {{ timeOfDay }}, {{ firstName }} 👋</div>
        <div class="user-dash__sub">How can we help you today?</div>
      </div>
      <q-btn
        color="primary"
        label="Submit a Ticket"
        icon="add_circle_outline"
        unelevated no-caps
        class="submit-btn text-weight-bold"
        :disable="Boolean(unratedTicket)"
        @click="openBlankDialog"
      >
        <q-tooltip v-if="unratedTicket" class="bg-amber-9">
          Please rate your resolved ticket before submitting a new one.
        </q-tooltip>
      </q-btn>
    </div>

    <!-- ── Action Required Banner ─────────────────────────────── -->
    <q-banner
      v-if="unratedTicket"
      rounded
      class="q-mb-lg bg-amber-1 text-amber-10"
      style="border: 1px solid #fde68a; border-left: 5px solid #f59e0b;"
    >
      <template #avatar>
        <q-avatar color="amber-3" text-color="amber-10" icon="rate_review" />
      </template>
      <div class="text-subtitle2 text-weight-bold">Action Required: Rate &amp; Review Resolved Ticket</div>
      <div class="text-caption">
        Ticket <strong>{{ unratedTicket.ticket_no || '#' + unratedTicket.id }}</strong>
        ({{ unratedTicket.title }}) has been resolved. Please rate the service to submit new tickets.
      </div>
      <template #action>
        <q-btn
          flat unelevated no-caps
          color="amber-9"
          label="Rate Now"
          icon="star"
          class="text-weight-bold"
          @click="openRatingModal(unratedTicket)"
        />
      </template>
    </q-banner>

    <!-- ── My Tickets ───────────────────────────────────────────── -->
    <div class="section-header q-mb-md">
      <div class="section-title">My Tickets</div>
      <q-btn
        flat no-caps dense
        color="primary"
        label="View All"
        icon-right="arrow_forward"
        size="sm"
        to="/user/ticket-management"
      />
    </div>

    <!-- Fixed Height Ticket Container -->
    <div class="ticket-container-wrapper q-mb-xl">
      <!-- Tickets List -->
      <q-card v-if="latestTickets.length" flat bordered class="rounded-lg ticket-list-card full-height">
        <q-scroll-area class="full-height">
          <q-list separator>
            <q-item
              v-for="ticket in latestTickets"
              :key="ticket.id"
              clickable
              v-ripple
              class="ticket-item"
              @click="openViewModal(ticket)"
            >
              <q-item-section avatar>
                <q-avatar
                  size="42px"
                  :color="getStatusBg(ticket.status)"
                  :text-color="getStatusColor(ticket.status)"
                  :icon="getStatusIcon(ticket.status)"
                />
              </q-item-section>

              <q-item-section>
                <q-item-label class="text-weight-semibold text-grey-9 ellipsis">
                  {{ ticket.title }}
                </q-item-label>
                <q-item-label caption class="row items-center gap-sm q-mt-xs">
                  <span class="text-grey-6">{{ ticket.ticket_no || '#' + ticket.id }}</span>
                  <q-separator vertical inset />
                  <q-icon name="category" size="12px" color="grey-5" />
                  <span class="text-grey-6">{{ ticket.category }}</span>
                  <q-separator vertical inset />
                  <q-icon name="schedule" size="12px" color="grey-5" />
                  <span class="text-grey-6">{{ ticket.created }}</span>
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                <div class="column items-end gap-xs">
                  <!-- Status -->
                  <span
                    class="status-text text-weight-bold text-caption"
                    :style="{ color: getStatusHex(ticket.status) }"
                  >
                    <q-icon :name="getStatusIcon(ticket.status)" size="13px" />
                    {{ statusLabel(ticket.status) }}
                  </span>
                  <!-- Priority -->
                  <span
                    class="priority-text text-caption"
                    :style="{ color: getPriorityHex(ticket.priority) }"
                  >
                    <q-icon name="flag" size="12px" />
                    {{ ticket.priority }}
                  </span>
                  <!-- Rating chip -->
                  <q-chip v-if="ticket.rating" dense size="sm" color="amber-1" text-color="amber-9" icon="star">
                    {{ ticket.rating }}/5
                  </q-chip>
                  <q-chip
                    v-else-if="['CLOSE'].includes(ticket.status)"
                    dense size="sm" color="amber-9" text-color="white" icon="rate_review"
                    clickable @click.stop="openRatingModal(ticket)"
                  >
                    Rate
                  </q-chip>
                </div>
              </q-item-section>
            </q-item>
          </q-list>
        </q-scroll-area>
      </q-card>

      <!-- Empty State -->
      <q-card v-else flat bordered class="rounded-lg empty-ticket-card flex flex-center full-height">
        <div class="column items-center text-center q-pa-lg">
          <q-icon name="confirmation_number" size="52px" color="grey-4" />
          <div class="text-h6 text-grey-6 q-mt-sm">No tickets yet</div>
          <div class="text-caption text-grey-5 q-mt-xs q-mb-md">You haven't submitted any support tickets.</div>
          <q-btn
            color="primary" label="Submit First Ticket" icon="add"
            unelevated no-caps class="text-weight-bold"
            :disable="Boolean(unratedTicket)"
            @click="openBlankDialog"
          />
        </div>
      </q-card>
    </div>

    <!-- ── Common Issues ─────────────────────────────────────────── -->
    <div class="section-header q-mb-md">
      <div class="section-title">Common Issues</div>
      <div class="text-caption text-grey-5">Click to file a ticket</div>
    </div>

    <div class="help-grid q-mb-xl">
      <q-card
        v-for="topic in helpTopics"
        :key="topic.title"
        flat bordered
        class="help-card cursor-pointer"
        @click="prefillTicket(topic)"
      >
        <q-card-section class="q-pa-lg">
          <div class="help-card__icon-wrap q-mb-md">
            <q-icon :name="topic.icon" size="26px" color="primary" />
          </div>
          <div class="text-subtitle2 text-weight-bold text-grey-9 q-mb-xs">{{ topic.title }}</div>
          <div class="text-caption text-grey-6 q-mb-md" style="line-height: 1.5;">{{ topic.desc }}</div>
          <div class="row items-center text-primary" style="font-size: 0.75rem; font-weight: 700;">
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

    <ViewTicketModal
      v-model="showViewModal"
      :ticket="selectedTicket"
      @refresh="fetchTickets"
    />

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

const $q = useQuasar()
const authStore = inject('authStore')

const showDialog = ref(false)
const dialogPrefill = ref({})
const showViewModal = ref(false)
const selectedTicket = ref({})
const showRatingModal = ref(false)
const ratingTicket = ref({})

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

const categoryOptions = ref([])
const myTickets = ref([])

// Latest 5 tickets
const latestTickets = computed(() => myTickets.value.slice(0, 5))

const unratedTicket = computed(() =>
  myTickets.value.find((t) => ['CLOSE'].includes(t.status) && (!t.rating || !t.feedback)),
)

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
  const { data } = await api.get('/problem-categories')
  categoryOptions.value = (data.data || data || []).map(c => ({ label: c.categories, value: c.id }))
}

async function fetchTickets() {
  try {
    const { data } = await api.get('/tickets')
    const rawList = data.data || data || []
    myTickets.value = rawList.map(ticket => ({
      id: ticket.id,
      real_id: ticket.id,
      ticket_no: ticket.ticket_no,
      title: ticket.issue || 'Untitled ticket',
      requester: ticket.user ? `${ticket.user.first_name} ${ticket.user.last_name}` : 'User',
      assignedStaff: ticket.assigned_staff
        ? ticket.assigned_staff.name || `${ticket.assigned_staff.first_name} ${ticket.assigned_staff.last_name}`
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
      created: new Date(ticket.date_submitted || ticket.created_at).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
      }),
    }))

    if (unratedTicket.value && !showRatingModal.value) {
      openRatingModal(unratedTicket.value)
    }
  } catch (error) {
    console.error('Failed to load tickets', error)
    $q.notify({ type: 'negative', message: 'Failed to load your tickets.' })
  }
}

// ── Status Helpers ───────────────────────────────────────────────
function getStatusHex(status) {
  const map = {
    'OPEN': '#c2410c',
    'ON-GOING': '#1d4ed8',
    'PENDING': '#ca8a04',
    'ESCALATED': '#9333ea',
    'RESOLVED': '#16a34a',
    'CLOSE': '#475569',
    'CANCEL': '#dc2626',
  }
  return map[(status || '').toUpperCase()] || '#475569'
}

function getStatusBg(status) {
  const map = {
    'OPEN': 'orange-1',
    'ON-GOING': 'blue-1',
    'PENDING': 'amber-1',
    'ESCALATED': 'purple-1',
    'RESOLVED': 'green-1',
    'CLOSE': 'grey-3',
    'CANCEL': 'red-1',
  }
  return map[(status || '').toUpperCase()] || 'grey-3'
}

function getStatusColor(status) {
  const map = {
    'OPEN': 'orange-8',
    'ON-GOING': 'blue-8',
    'PENDING': 'amber-9',
    'ESCALATED': 'purple-8',
    'RESOLVED': 'green-8',
    'CLOSE': 'grey-6',
    'CANCEL': 'red-8',
  }
  return map[(status || '').toUpperCase()] || 'grey-6'
}

function getStatusIcon(status) {
  const map = {
    'OPEN': 'inbox',
    'ON-GOING': 'autorenew',
    'PENDING': 'pending_actions',
    'ESCALATED': 'trending_up',
    'RESOLVED': 'task_alt',
    'CLOSE': 'check_box',
    'CANCEL': 'cancel',
  }
  return map[(status || '').toUpperCase()] || 'help_outline'
}

function statusLabel(status) {
  const map = {
    'OPEN': 'Open',
    'ON-GOING': 'On-going',
    'PENDING': 'Pending',
    'ESCALATED': 'Escalated',
    'RESOLVED': 'Resolved',
    'CLOSE': 'Closed',
    'CANCEL': 'Canceled',
  }
  return map[(status || '').toUpperCase()] || status
}

function getPriorityHex(priority) {
  const map = {
    'HIGH': '#dc2626',
    'CRITICAL': '#7c3aed',
    'NORMAL': '#2563eb',
    'MEDIUM': '#2563eb',
    'LOW': '#16a34a',
  }
  return map[(priority || '').toUpperCase()] || '#475569'
}

// ── Help Topics ──────────────────────────────────────────────────
const helpTopics = [
  { title: 'Laptop / Hardware',       icon: 'laptop',          desc: 'Device not powering on, broken peripherals, screen issues.', category: 'Hardware' },
  { title: 'Software / App Error',    icon: 'bug_report',      desc: 'Application crashes, errors, or software won\'t respond.', category: 'Software' },
  { title: 'Network / Internet',      icon: 'wifi',            desc: 'Slow or no connection, VPN issues, Wi-Fi problems.', category: 'Network' },
  { title: 'Account Access',          icon: 'manage_accounts', desc: 'Password reset, account locked or permission issues.', category: 'Account' },
  { title: 'Scheduling / Requesting', icon: 'event_note',      desc: 'Book facility/equipment reservation, request lab slot, or schedule support.', category: 'Scheduling Request' },
]

function openBlankDialog() {
  if (unratedTicket.value) {
    $q.notify({ type: 'warning', message: 'Please rate your resolved ticket first.', icon: 'rate_review' })
    openRatingModal(unratedTicket.value)
    return
  }
  dialogPrefill.value = {}
  showDialog.value = true
}

function prefillTicket(topic) {
  if (unratedTicket.value) {
    $q.notify({ type: 'warning', message: 'Please rate your resolved ticket first.', icon: 'rate_review' })
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
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.section-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: $min-text-soft;
}

.submit-btn {
  border-radius: 10px;
  padding: 8px 20px;
}

// ── Fixed Position Ticket Container ────────────────────────────
.ticket-container-wrapper {
  height: 270px;
}

.full-height {
  height: 100%;
}

.ticket-list-card {
  border-radius: 14px !important;
}

.empty-ticket-card {
  border-radius: 14px !important;
}

// ── Clean Ticket Item (No accent left border) ─────────────────
.ticket-item {
  padding: 14px 20px;
  transition: background 0.15s ease;

  &:hover {
    background: rgba(0, 0, 0, 0.02);
  }
}

.status-text {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 0.78rem;
}

.priority-text {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 0.74rem;
}

// ── Help Grid ─────────────────────────────────────────────────
.help-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
}

.help-card {
  border-radius: 14px !important;
  transition: transform 0.18s ease, box-shadow 0.18s ease;

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

.gap-xs { gap: 4px; }
.gap-sm { gap: 8px; }
.rounded-lg { border-radius: 14px !important; }
</style>
