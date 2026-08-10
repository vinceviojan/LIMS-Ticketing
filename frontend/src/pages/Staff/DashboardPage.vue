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
      <div v-for="stat in stats" :key="stat.label" class="staff-stat" :class="`staff-stat--${stat.color}`">
        <div class="staff-stat__icon-wrap">
          <q-icon :name="stat.icon" size="24px" />
        </div>
        <div>
          <div class="staff-stat__value">{{ stat.value }}</div>
          <div class="staff-stat__label">{{ stat.label }}</div>
        </div>
      </div>
    </div>

    <!-- ── Ticket Queue ─────────────────────────────────────────── -->
    <div class="staff-dash__section-title">My Assigned Tickets</div>

    <div class="staff-queue">
      <div
        v-for="ticket in assignedTickets"
        :key="ticket.id"
        class="staff-queue__item"
        :class="`staff-queue__item--${ticket.priority.toLowerCase()}`"
      >
        <div class="staff-queue__priority-dot" :class="`staff-queue__priority-dot--${ticket.priority.toLowerCase()}`" />
        <div class="staff-queue__body">
          <div class="staff-queue__title">{{ ticket.title }}</div>
          <div class="staff-queue__meta">
            <q-icon name="person" size="13px" />
            {{ ticket.requester }} &nbsp;·&nbsp;
            <q-icon name="category" size="13px" />
            {{ ticket.category }} &nbsp;·&nbsp;
            {{ ticket.created }}
          </div>
        </div>
        <div class="staff-queue__actions">
          <span :class="['staff-queue__status', `staff-queue__status--${ticket.status.toLowerCase()}`]">
            {{ ticket.status }}
          </span>
          <q-btn flat dense icon="task_alt" size="sm" color="positive" @click="resolve(ticket)">
            <q-tooltip>Mark Resolved</q-tooltip>
          </q-btn>
          <q-btn flat dense icon="arrow_forward_ios" size="sm" color="primary">
            <q-tooltip>View Details</q-tooltip>
          </q-btn>
        </div>
      </div>

      <div v-if="assignedTickets.length === 0" class="staff-dash__empty">
        <q-icon name="task_alt" size="48px" color="positive" />
        <p>All tickets resolved — great work!</p>
      </div>
    </div>

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
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { useAuthStore } from '../../stores/auth'

const $q = useQuasar()
const authStore = useAuthStore()

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
  new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

const assignedTickets = ref([
  { id: 1001, title: 'Laptop not turning on',          requester: 'Juan dela Cruz', category: 'Hardware', priority: 'HIGH',    status: 'OPEN',    created: 'Aug 9' },
  { id: 1002, title: 'Cannot access LIMS portal',      requester: 'Maria Santos',   category: 'Software', priority: 'CRITICAL', status: 'OPEN',    created: 'Aug 9' },
  { id: 1003, title: 'Slow internet in Lab 3',         requester: 'Pedro Reyes',    category: 'Network',  priority: 'MEDIUM',  status: 'PENDING', created: 'Aug 8' },
  { id: 1006, title: 'Software installation request',  requester: 'Rosa Lim',       category: 'Software', priority: 'LOW',     status: 'OPEN',    created: 'Aug 10' },
])

const stats = computed(() => [
  { label: 'Assigned',  icon: 'inbox',          value: assignedTickets.value.length,                                                    color: 'blue'   },
  { label: 'Open',      icon: 'radio_button_unchecked', value: assignedTickets.value.filter(t => t.status === 'OPEN').length,     color: 'orange' },
  { label: 'Pending',   icon: 'pending_actions', value: assignedTickets.value.filter(t => t.status === 'PENDING').length,               color: 'yellow' },
  { label: 'Resolved',  icon: 'task_alt',        value: 8,                                                                              color: 'green'  },
])

const categories = ref([
  { type: 'Hardware', items: ['Laptop Issue', 'Printer Problem', 'Monitor Fault', 'Peripheral Device'] },
  { type: 'Software', items: ['OS Crash', 'Application Error', 'Installation Request', 'Update Issue'] },
  { type: 'Network',  items: ['Slow Connection', 'No Internet', 'VPN Problem', 'Wi-Fi Issue'] },
  { type: 'Account',  items: ['Password Reset', 'Access Request', 'Account Lock'] },
])

function resolve(ticket) {
  ticket.status = 'RESOLVED'
  const idx = assignedTickets.value.indexOf(ticket)
  setTimeout(() => assignedTickets.value.splice(idx, 1), 800)
  $q.notify({ type: 'positive', message: `Ticket #${ticket.id} marked as resolved.`, position: 'top-right', timeout: 2000 })
}
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
  &:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }

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

  &--blue   .staff-stat__icon-wrap { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; }
  &--orange .staff-stat__icon-wrap { background: #fff7ed; color: #f97316; border: 1px solid #fed7aa; }
  &--yellow .staff-stat__icon-wrap { background: #fefce8; color: #eab308; border: 1px solid #fef08a; }
  &--green  .staff-stat__icon-wrap { background: #f0fdf4; color: $positive; border: 1px solid #bbf7d0; }
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

    &:last-child { border-bottom: none; }
    &:hover { background: $min-bg; }

    &--low      { border-left-color: $min-text-soft; }
    &--medium   { border-left-color: #f59e0b; }
    &--high     { border-left-color: $accent-login; }
    &--critical { border-left-color: #ef4444; }
  }

  &__priority-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;

    &--low      { background: $min-text-soft; }
    &--medium   { background: #f59e0b; }
    &--high     { background: $accent-login; }
    &--critical { background: #ef4444; }
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

    &--open    { background: $accent-login; }
    &--pending { background: #f59e0b; }
    &--resolved { background: $positive; }
    &--closed  { background: $min-text-soft; }
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
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
</style>
