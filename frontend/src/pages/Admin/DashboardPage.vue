<template>
  <q-page class="dash-page">

    <!-- ── Welcome Banner ──────────────────────────────────── -->
    <div class="dash-page__welcome">
      <div>
        <div class="dash-page__welcome-greeting">Good {{ timeOfDay }}, {{ firstName }} 👋</div>
        <div class="dash-page__welcome-sub">Here's what's happening in the system today.</div>
      </div>
      <div class="dash-page__welcome-date">{{ currentDate }}</div>
    </div>

    <!-- ── Stat Cards ───────────────────────────────────────── -->
    <div class="dash-page__stats">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="dash-stat"
        :class="`dash-stat--${stat.color}`"
      >
        <div class="dash-stat__icon-wrap">
          <q-icon :name="stat.icon" size="28px" />
        </div>
        <div class="dash-stat__body">
          <div class="dash-stat__value">
            <span v-if="!statsLoading">{{ stat.value }}</span>
            <q-skeleton v-else type="text" width="40px" />
          </div>
          <div class="dash-stat__label">{{ stat.label }}</div>
        </div>
      </div>
    </div>

    <!-- ── Quick Actions ────────────────────────────────────── -->
    <div class="dash-page__section-title">Quick Actions</div>
    <div class="dash-page__actions">
      <router-link
        v-for="action in quickActions"
        :key="action.label"
        :to="action.to"
        class="dash-action"
      >
        <q-icon :name="action.icon" size="32px" class="dash-action__icon" />
        <div class="dash-action__label">{{ action.label }}</div>
        <div class="dash-action__desc">{{ action.desc }}</div>
      </router-link>
    </div>

    <!-- ── Recent Activity ──────────────────────────────────── -->
    <div class="dash-page__section-title">Recent Activity</div>
    <div class="dash-page__activity">
      <div
        v-for="item in recentActivity"
        :key="item.id"
        class="dash-activity"
      >
        <div class="dash-activity__dot" :class="`dash-activity__dot--${item.type}`" />
        <div class="dash-activity__body">
          <div class="dash-activity__msg">{{ item.message }}</div>
          <div class="dash-activity__time">{{ item.time }}</div>
        </div>
      </div>

      <div v-if="recentActivity.length === 0" class="dash-page__empty">
        <q-icon name="inbox" size="40px" color="grey-5" />
        <p>No recent activity</p>
      </div>
    </div>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
// import { api } from 'src/boot/axios'

const authStore = useAuthStore()

// ── Greeting ─────────────────────────────────────────────────
const firstName = computed(() => {
  const name = authStore.userName ?? ''
  return name.split(' ')[0] || 'Admin'
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

// ── Stats ─────────────────────────────────────────────────────
const statsLoading = ref(false)

const stats = ref([
  { label: 'Total Users',     icon: 'people',           value: 0,  color: 'blue'   },
  { label: 'Open Tickets',    icon: 'confirmation_number', value: 0, color: 'orange' },
  { label: 'Resolved Today',  icon: 'task_alt',         value: 0,  color: 'green'  },
  { label: 'Pending Review',  icon: 'pending_actions',  value: 0,  color: 'purple' },
])

async function fetchStats() {
  statsLoading.value = true
  try {
    // const { data } = await api.get('/api/dashboard/stats')
    // stats.value[0].value = data.total_users
    // stats.value[1].value = data.open_tickets
    // stats.value[2].value = data.resolved_today
    // stats.value[3].value = data.pending_review
    // Placeholder values — wire up to real API when ready
    stats.value[0].value = 0
    stats.value[1].value = 0
    stats.value[2].value = 0
    stats.value[3].value = 0
  } finally {
    statsLoading.value = false
  }
}

// ── Quick Actions ─────────────────────────────────────────────
const quickActions = [
  { label: 'User Management', icon: 'manage_accounts', desc: 'Add, edit or remove users', to: '/admin/users' },
  { label: 'Tickets',         icon: 'confirmation_number', desc: 'View and manage tickets', to: '/admin/ticket-management' },
  { label: 'Reports',         icon: 'bar_chart',       desc: 'View analytics & reports',  to: '/admin/reports' },
  { label: 'Settings',        icon: 'settings',        desc: 'Configure system settings', to: '/admin/settings' },
]

// ── Recent Activity (placeholder) ────────────────────────────
const recentActivity = ref([])

onMounted(fetchStats)
</script>

<style lang="scss" scoped>
@import '@/css/themes.scss';

.dash-page {
  padding: 32px;
  background: $clay-bg;
  min-height: 100vh;

  // ── Welcome Banner ──────────────────────────────────────────
  &__welcome {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 32px;
  }

  &__welcome-greeting {
    font-size: 1.6rem;
    font-weight: 800;
    color: $clay-text;
    font-family: 'Nunito', sans-serif;
  }

  &__welcome-sub {
    color: $clay-text-soft;
    font-size: 0.88rem;
    margin-top: 4px;
  }

  &__welcome-date {
    color: $clay-text-soft;
    font-size: 0.82rem;
    font-weight: 600;
    align-self: center;
  }

  // ── Stats Grid ──────────────────────────────────────────────
  &__stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 36px;
  }

  // ── Section title ───────────────────────────────────────────
  &__section-title {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: $clay-text-soft;
    margin-bottom: 14px;
  }

  // ── Quick Actions ───────────────────────────────────────────
  &__actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 36px;
  }

  // ── Activity ────────────────────────────────────────────────
  &__activity {
    @include clay-raised(20px);
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  &__empty {
    padding: 32px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: $clay-text-soft;
    font-size: 0.88rem;
  }
}

// ── Stat Card ─────────────────────────────────────────────────
.dash-stat {
  @include clay-raised(20px);
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  transition: transform 0.18s ease;

  &:hover {
    transform: translateY(-3px);
  }

  &__icon-wrap {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  &__value {
    font-size: 1.6rem;
    font-weight: 800;
    color: $clay-text;
    line-height: 1;
    font-family: 'Nunito', sans-serif;
  }

  &__label {
    font-size: 0.78rem;
    color: $clay-text-soft;
    margin-top: 3px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  &--blue   .dash-stat__icon-wrap { background: rgba($accent-login, 0.12); color: $accent-login; }
  &--orange .dash-stat__icon-wrap { background: rgba(#f0a500, 0.12);       color: #d98c00; }
  &--green  .dash-stat__icon-wrap { background: rgba($accent-signup, 0.14); color: $accent-signup; }
  &--purple .dash-stat__icon-wrap { background: rgba(#7c6bbf, 0.12);       color: #7c6bbf; }
}

// ── Quick Action Card ─────────────────────────────────────────
.dash-action {
  @include clay-raised(18px);
  padding: 20px;
  cursor: pointer;
  text-decoration: none;
  transition: transform 0.18s ease, box-shadow 0.18s ease;

  &:hover {
    transform: translateY(-3px);
    box-shadow: 10px 10px 22px $clay-dark, -8px -8px 20px $clay-light;
  }

  &__icon {
    color: $accent-login;
    margin-bottom: 10px;
  }

  &__label {
    font-weight: 700;
    color: $clay-text;
    font-size: 0.92rem;
    margin-bottom: 4px;
  }

  &__desc {
    font-size: 0.76rem;
    color: $clay-text-soft;
    line-height: 1.4;
  }
}

// ── Activity Item ─────────────────────────────────────────────
.dash-activity {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 14px 8px;
  border-bottom: 1px solid rgba($clay-dark, 0.25);

  &:last-child { border-bottom: none; }

  &__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;

    &--info    { background: $accent-login; }
    &--success { background: $accent-signup; }
    &--warning { background: #d98c00; }
    &--danger  { background: #e74c3c; }
  }

  &__msg {
    font-size: 0.88rem;
    color: $clay-text;
    font-weight: 500;
  }

  &__time {
    font-size: 0.75rem;
    color: $clay-text-soft;
    margin-top: 2px;
  }
}
</style>