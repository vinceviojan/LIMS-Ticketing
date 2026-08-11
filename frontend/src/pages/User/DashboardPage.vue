<template>
  <q-page class="user-dash">

    <!-- ── Welcome ─────────────────────────────────────────────── -->
    <div class="user-dash__welcome">
      <div>
        <div class="user-dash__greeting">Good {{ timeOfDay }}, {{ firstName }} 👋</div>
        <div class="user-dash__sub">How can we help you today?</div>
      </div>
      <q-btn
        class="clay-btn clay-btn--primary"
        label="Submit a Ticket"
        icon="add_circle_outline"
        unelevated no-caps
        @click="openBlankDialog"
      />
    </div>

    <!-- ── My Tickets ───────────────────────────────────────────── -->
    <div class="user-dash__section-title">My Tickets</div>

    <div v-if="myTickets.length" class="user-dash__tickets">
      <div
        v-for="ticket in myTickets"
        :key="ticket.id"
        class="user-ticket"
        :class="`user-ticket--${ticket.priority.toLowerCase()}`"
      >
        <div class="user-ticket__left">
          <div class="user-ticket__id">#{{ ticket.id }}</div>
          <span :class="['user-ticket__status', `user-ticket__status--${ticket.status.toLowerCase()}`]">
            {{ ticket.status }}
          </span>
        </div>
        <div class="user-ticket__body">
          <div class="user-ticket__title">{{ ticket.title }}</div>
          <div class="user-ticket__meta">
            <q-icon name="category" size="13px" />
            {{ ticket.category }} &nbsp;·&nbsp;
            <q-icon name="flag" size="13px" />
            {{ ticket.priority }} &nbsp;·&nbsp;
            {{ ticket.created }}
          </div>
        </div>
        <q-icon
          :name="ticket.status === 'RESOLVED' ? 'task_alt' : ticket.status === 'PENDING' ? 'pending_actions' : 'radio_button_unchecked'"
          :color="ticket.status === 'RESOLVED' ? 'positive' : ticket.status === 'PENDING' ? 'warning' : 'primary'"
          size="20px"
        />
      </div>
    </div>

    <div v-else class="user-dash__empty">
      <q-icon name="confirmation_number" size="52px" color="grey-5" />
      <p>You haven't submitted any tickets yet.</p>
      <q-btn class="clay-btn clay-btn--primary" label="Submit First Ticket" icon="add" unelevated no-caps @click="openBlankDialog" />
    </div>

    <!-- ── Help Topics ──────────────────────────────────────────── -->
    <div class="user-dash__section-title">Common Issues</div>
    <div class="user-dash__help">
      <div v-for="topic in helpTopics" :key="topic.title" class="help-card" @click="prefillTicket(topic)">
        <q-icon :name="topic.icon" size="28px" class="help-card__icon" />
        <div class="help-card__title">{{ topic.title }}</div>
        <div class="help-card__desc">{{ topic.desc }}</div>
        <div class="help-card__action">
          Click to file <q-icon name="arrow_forward" size="13px" />
        </div>
      </div>
    </div>

    <!-- ── Submit Ticket Dialog ─────────────────────────────────── -->
    <AddTicketModal
      v-model="showDialog"
      :category-options="categoryOptions"
      :priority-options="priorityOptions"
      :prefill="dialogPrefill"
      @refresh="fetchTickets"
    />

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'
import AddTicketModal from '../../components/AddTicketModal.vue'

const $q = useQuasar()
const authStore = inject('authStore')

const showDialog = ref(false)
const dialogPrefill = ref({})

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

const priorityOptions = [
  { label: 'Low',    value: 'LOW'    },
  { label: 'Normal', value: 'NORMAL' },
  { label: 'High',   value: 'HIGH'   },
]

const categoryOptions = ref([])

const myTickets = ref([])

onMounted(async () => {
  await Promise.all([fetchCategories(), fetchTickets()])
})

async function fetchCategories() {
  const { data } = await api.get('/problem-categories')
  categoryOptions.value = (data.data || data || []).map(category => ({ label: category.categories, value: category.id }))
}

async function fetchTickets() {
  try {
    const { data } = await api.get('/tickets')
    myTickets.value = (data.data || data || []).map(ticket => ({
      id: ticket.id,
      ticket_no: ticket.ticket_no,
      title: ticket.issue || 'Untitled ticket',
      category: ticket.problem_category?.categories || 'Uncategorized',
      priority: ticket.urgency || 'NORMAL',
      status: ticket.status || 'OPEN',
      created: new Date(ticket.date_submitted || ticket.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    }))
  } catch (error) {
    console.error('Failed to load tickets', error)
    $q.notify({ type: 'negative', message: 'Failed to load your tickets.' })
  }
}

const helpTopics = [
  { title: 'Laptop / Hardware',   icon: 'laptop',      desc: 'Device not powering on, broken peripherals, screen issues.', category: 'Hardware' },
  { title: 'Software / App Error',icon: 'bug_report',  desc: 'Application crashes, errors, or software won\'t respond.', category: 'Software' },
  { title: 'Network / Internet',  icon: 'wifi',        desc: 'Slow or no connection, VPN issues, Wi-Fi problems.', category: 'Network' },
  { title: 'Account Access',      icon: 'manage_accounts', desc: 'Password reset, account locked or permission issues.', category: 'Account' },
]

function openBlankDialog() {
  dialogPrefill.value = {}
  showDialog.value = true
}

function prefillTicket(topic) {
  const category = categoryOptions.value.find(option => option.label.toLowerCase().includes(topic.category.toLowerCase()))
  dialogPrefill.value = { title: topic.title, category: category?.value || null }
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

  &__section-title {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: $min-text-soft;
    margin-bottom: 12px;
  }

  &__tickets {
    @include min-card();
    margin-bottom: 32px;
    overflow: hidden;
  }

  &__help {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
  }

  &__empty {
    @include min-card();
    padding: 48px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    color: $min-text-soft;
    margin-bottom: 32px;
  }
}

// ── Ticket Row ────────────────────────────────────────────────
.user-ticket {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 20px;
  border-bottom: 1px solid $min-border;
  border-left: 4px solid transparent;
  transition: background 0.15s ease;

  &:last-child { border-bottom: none; }
  &:hover { background: $min-bg; }

  &--low      { border-left-color: $min-text-soft; }
  &--medium   { border-left-color: #f59e0b; }
  &--high     { border-left-color: $accent-login; }
  &--critical { border-left-color: #ef4444; }

  &__left {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    width: 60px;
  }

  &__id {
    font-size: 0.72rem;
    font-weight: 700;
    color: $min-text-soft;
  }

  &__status {
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #fff;

    &--open     { background: $accent-login; }
    &--pending  { background: #f59e0b; }
    &--resolved { background: $positive; }
    &--closed   { background: $min-text-soft; }
  }

  &__body { flex: 1; }

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
}

// ── Help Card ─────────────────────────────────────────────────
.help-card {
  @include min-card();
  padding: 20px;
  cursor: pointer;
  transition: transform 0.18s ease;

  &:hover { transform: translateY(-3px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }

  &__icon {
    color: $accent-login;
    margin-bottom: 10px;
    display: block;
  }

  &__title {
    font-size: 0.9rem;
    font-weight: 700;
    color: $min-text;
    margin-bottom: 5px;
  }

  &__desc {
    font-size: 0.76rem;
    color: $min-text-soft;
    line-height: 1.45;
    margin-bottom: 12px;
  }

  &__action {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    color: $accent-login;
  }
}

// ── Buttons ───────────────────────────────────────────────────
.clay-btn {
  &--primary { @include min-button($accent-login); }
}
</style>