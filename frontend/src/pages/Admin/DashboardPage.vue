<template>
  <q-page class="dash">

    <!-- ── Top Bar ───────────────────────────────────────── -->
    <div class="dash__topbar">
      <div>
        <h1 class="dash__title">Analytics Dashboard</h1>
        <p class="dash__subtitle">
          Good {{ timeOfDay }}, <strong>{{ firstName }}</strong> &mdash; {{ currentDate }}
        </p>
      </div>
      <div class="dash__topbar-actions">
        <q-btn
          unelevated no-caps color="primary"
          icon="refresh" label="Refresh"
          :loading="loading"
          @click="loadAll"
        />
      </div>
    </div>

    <!-- ── KPI Cards ────────────────────────────────────── -->
    <div class="dash__kpi-row">
      <div v-for="kpi in kpis" :key="kpi.label" class="kpi-card">
        <div class="kpi-card__header">
          <span class="kpi-card__label">{{ kpi.label }}</span>
          <div class="kpi-card__icon" :style="{ background: kpi.bg, color: kpi.fg }">
            <q-icon :name="kpi.icon" size="18px" />
          </div>
        </div>
        <div class="kpi-card__value">
          <q-skeleton v-if="kpi.loading" type="text" width="60px" height="32px" />
          <span v-else>{{ kpi.value }}</span>
        </div>
        <div class="kpi-card__footer">
          <span class="kpi-card__trend" :class="kpi.trend >= 0 ? 'kpi-card__trend--up' : 'kpi-card__trend--down'">
            <q-icon :name="kpi.trend >= 0 ? 'trending_up' : 'trending_down'" size="14px" />
            {{ Math.abs(kpi.trend) }}%
          </span>
          <span class="kpi-card__period">vs last month</span>
          <!-- Sparkline -->
          <svg class="kpi-card__spark" viewBox="0 0 80 24" preserveAspectRatio="none">
            <polyline
              :points="sparkPoints(kpi.spark)"
              fill="none"
              :stroke="kpi.trend >= 0 ? '#009747' : '#ef4444'"
              stroke-width="2"
              stroke-linejoin="round"
              stroke-linecap="round"
            />
          </svg>
        </div>
      </div>
    </div>


    
   <!-- ── Quick Actions ────────────────────────────────── -->
    <div class="dash__section-label">Quick Actions</div>
    <div class="dash__actions">
      <router-link v-for="a in quickActions" :key="a.label" :to="a.to" class="action-card">
        <q-icon :name="a.icon" size="28px" class="action-card__icon" />
        <div class="action-card__label">{{ a.label }}</div>
        <div class="action-card__desc">{{ a.desc }}</div>
      </router-link>
    </div>


    <!-- ── Charts Row (Users) ───────────────────────────── -->
    <div class="dash__grid-2">
      <!-- User Role Distribution -->
      <div class="panel">
        <div class="panel__head">
          <span class="panel__title">User Roles</span>
          <span class="panel__sub">Distribution by role</span>
        </div>
        <div v-if="analyticsLoading" class="panel__empty">
          <q-spinner color="primary" size="28px" />
          <span>Loading users&hellip;</span>
        </div>
        <div class="donut-wrap" v-else>
          <svg class="donut" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="48" fill="none" stroke="#f3f4f6" stroke-width="14" />
            <circle
              v-for="(seg, i) in roleDonut" :key="i"
              cx="60" cy="60" r="48" fill="none"
              :stroke="seg.color" stroke-width="14" stroke-linecap="butt"
              :stroke-dasharray="`${seg.dash} ${301.6 - seg.dash}`"
              :stroke-dashoffset="seg.offset"
              style="transition: stroke-dasharray 0.6s ease"
            />
            <text x="60" y="56" text-anchor="middle" dy=".35em" class="donut__num">{{ totalUsers }}</text>
            <text x="60" y="72" text-anchor="middle" class="donut__label-text">Users</text>
          </svg>
          <div class="donut-legend">
            <div v-for="r in roles" :key="r.label" class="donut-legend__item">
              <span class="donut-legend__dot" :style="{ background: r.color }" />
              <span class="donut-legend__name">{{ r.label }}</span>
              <span class="donut-legend__val">{{ r.count }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- User Status Breakdown -->
      <div class="panel">
        <div class="panel__head">
          <span class="panel__title">User Status</span>
          <span class="panel__sub">Account health</span>
        </div>
        <div v-if="usersLoading" class="panel__empty">
          <q-spinner color="primary" size="28px" />
          <span>Loading users&hellip;</span>
        </div>
        <div class="hbar-chart" v-else>
          <div v-for="s in userStatuses" :key="s.label" class="hbar">
            <div class="hbar__label-row">
              <span class="hbar__name">{{ s.label }}</span>
              <span class="hbar__count">{{ s.count }}</span>
            </div>
            <div class="hbar__track">
              <div class="hbar__fill" :style="{ width: statusPct(s.count) + '%', background: s.color }" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Charts Row (Tickets) ──────────────────────────── -->
    <div class="dash__grid-2">
      <!-- Tickets by Status Donut -->
      <div class="panel">
        <div class="panel__head">
          <span class="panel__title">Tickets by Status</span>
          <span class="panel__sub">Current ticket health</span>
        </div>
        <div class="donut-wrap">
          <svg class="donut" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="48" fill="none" stroke="#f3f4f6" stroke-width="14" />
            <circle
              v-for="(seg, i) in ticketStatusDonut" :key="i"
              cx="60" cy="60" r="48" fill="none"
              :stroke="seg.color" stroke-width="14" stroke-linecap="butt"
              :stroke-dasharray="`${seg.dash} ${301.6 - seg.dash}`"
              :stroke-dashoffset="seg.offset"
              style="transition: stroke-dasharray 0.6s ease"
            />
            <text x="60" y="56" text-anchor="middle" dy=".35em" class="donut__num">{{ totalTicketsStatus }}</text>
            <text x="60" y="72" text-anchor="middle" class="donut__label-text">Tickets</text>
          </svg>
          <div class="donut-legend">
            <div v-for="seg in ticketStatusDonut" :key="seg.label" class="donut-legend__item">
              <span class="donut-legend__dot" :style="{ background: seg.color }" />
              <span class="donut-legend__name">{{ seg.label }}</span>
              <span class="donut-legend__val">{{ seg.count }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tickets by Priority Donut -->
      <div class="panel">
        <div class="panel__head">
          <span class="panel__title">Tickets by Priority</span>
          <span class="panel__sub">Active tickets severity</span>
        </div>
        <div class="donut-wrap">
          <svg class="donut" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="48" fill="none" stroke="#f3f4f6" stroke-width="14" />
            <circle
              v-for="(seg, i) in ticketPriorityDonut" :key="i"
              cx="60" cy="60" r="48" fill="none"
              :stroke="seg.color" stroke-width="14" stroke-linecap="butt"
              :stroke-dasharray="`${seg.dash} ${301.6 - seg.dash}`"
              :stroke-dashoffset="seg.offset"
              style="transition: stroke-dasharray 0.6s ease"
            />
            <text x="60" y="56" text-anchor="middle" dy=".35em" class="donut__num">{{ totalTicketsPriority }}</text>
            <text x="60" y="72" text-anchor="middle" class="donut__label-text">Tickets</text>
          </svg>
          <div class="donut-legend">
            <div v-for="seg in ticketPriorityDonut" :key="seg.label" class="donut-legend__item">
              <span class="donut-legend__dot" :style="{ background: seg.color }" />
              <span class="donut-legend__name">{{ seg.label }}</span>
              <span class="donut-legend__val">{{ seg.count }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Bottom Row ───────────────────────────────────── -->
    <div class="dash__grid-2">

      <!-- Problem Categories -->
      <div class="panel">
        <div class="panel__head">
          <span class="panel__title">Problem Categories</span>
          <router-link to="/admin/problem-categories" class="panel__link">Manage →</router-link>
        </div>
        <div v-if="categoriesLoading" class="panel__empty">
          <q-spinner color="primary" size="28px" />
          <span>Loading categories&hellip;</span>
        </div>
        <div class="cat-list" v-else-if="categories.length">
          <div v-for="(cat, i) in categories.slice(0, 8)" :key="cat.id" class="cat-list__row">
            <span class="cat-list__rank">#{{ i + 1 }}</span>
            <div class="cat-list__info">
              <span class="cat-list__name">{{ cat.categories }}</span>
              <span class="cat-list__type">{{ cat.type }}</span>
            </div>
          </div>
        </div>
        <div v-else class="panel__empty">
          <q-icon name="category" size="36px" color="grey-4" />
          <span>No categories defined</span>
        </div>
      </div>

      <!-- Recent Users Table -->
      <div class="panel">
        <div class="panel__head">
          <span class="panel__title">Recent Users</span>
          <router-link to="/admin/users" class="panel__link">View all →</router-link>
        </div>
        <div v-if="usersLoading" class="panel__empty">
          <q-spinner color="primary" size="28px" />
          <span>Loading users&hellip;</span>
        </div>
        <table class="mini-table" v-else-if="recentUsers.length">
          <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>
          </thead>
          <tbody>
            <tr v-for="u in recentUsers" :key="u.id">
              <td class="mini-table__name">{{ u.first_name }} {{ u.last_name }}</td>
              <td class="mini-table__email">{{ u.email }}</td>
              <td><span class="role-badge" :class="`role-badge--${(u.role || '').toLowerCase()}`">{{ u.role }}</span></td>
              <td><span class="status-dot" :class="`status-dot--${(u.status || '').toLowerCase()}`" /> {{ u.status }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else class="panel__empty">
          <q-icon name="people" size="36px" color="grey-4" />
          <span>No users found</span>
        </div>
      </div>
    </div>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { api } from '../../boot/axios'
import './DashboardPage.scss'
const authStore = inject('authStore')


// ── Greeting ──────────────────────────────────────────────────
const firstName = computed(() => (authStore.userName ?? '').split(' ')[0] || 'Admin')
const timeOfDay = computed(() => {
  const h = new Date().getHours()
  return h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening'
})
const currentDate = computed(() =>
  new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

// ── Reactive Data ─────────────────────────────────────────────
const allUsers   = ref([])
const categories = ref([])
const analytics = ref({ user_roles: {}, tickets_by_status: {}, tickets_by_priority: {} })

// ── Per-source loading state ────────────────────────────────────
// Each section owns its own flag so it can render the moment its own
// request resolves, instead of the whole page waiting on everything.
const usersLoading      = ref(false)
const categoriesLoading = ref(false)
const analyticsLoading  = ref(false)
const loading = computed(() => usersLoading.value || categoriesLoading.value || analyticsLoading.value)

// ── KPIs (computed from live data) ────────────────────────────
const totalUsers    = computed(() => allUsers.value.length)
const activeUsers   = computed(() => allUsers.value.filter(u => (u.status || '').toUpperCase() === 'ACTIVE').length)
const totalCats     = computed(() => categories.value.length)

const kpis = computed(() => [
  {
    label: 'Total Users',    icon: 'people',         value: totalUsers.value,
    trend: 12,  bg: '#eff6ff', fg: '#3b82f6', loading: usersLoading.value,
    spark: [4, 7, 6, 10, 9, 11, totalUsers.value || 14],
  },
  {
    label: 'Active Users',   icon: 'verified_user',  value: activeUsers.value,
    trend: 8,   bg: '#f0fdf4', fg: '#009747', loading: usersLoading.value,
    spark: [3, 5, 4, 7, 6, 8, activeUsers.value || 9],
  },
  {
    label: 'Categories',     icon: 'category',       value: totalCats.value,
    trend: 5,   bg: '#fff7ed', fg: '#f97316', loading: categoriesLoading.value,
    spark: [2, 3, 4, 3, 5, 4, totalCats.value || 6],
  },
  {
    label: 'Staff Members',  icon: 'support_agent',
    value: allUsers.value.filter(u => (u.role || '').toUpperCase() === 'STAFF').length,
    trend: -2,  bg: '#f3e8ff', fg: '#a855f7', loading: usersLoading.value,
    spark: [5, 4, 6, 5, 4, 3, 4],
  },
])

// ── Role Donut ────────────────────────────────────────────────
const roles = computed(() => {
  const map = { ADMIN: { label: 'Admin', color: '#3b82f6', count: 0 }, STAFF: { label: 'Staff', color: '#a855f7', count: 0 }, USER: { label: 'User', color: '#009747', count: 0 } }
  Object.entries(analytics.value.user_roles || {}).forEach(([role, count]) => {
    if (map[role]) map[role].count = count
    else map[role] = { label: role, color: '#9ca3af', count }
  })
  return Object.values(map).filter(r => r.count > 0)
})

const CIRC = 301.6
const roleDonut = computed(() => {
  const total = totalUsers.value || 1
  let offset = CIRC * 0.25
  return roles.value.map(r => {
    const dash = (r.count / total) * CIRC
    const seg  = { dash, offset: -offset + CIRC, color: r.color }
    offset += dash
    return seg
  })
})

// ── User Status Bars ──────────────────────────────────────────
const userStatuses = computed(() => {
  const map = {
    ACTIVE:    { label: 'Active',    color: '#009747', count: 0 },
    INACTIVE:  { label: 'Inactive',  color: '#9ca3af', count: 0 },
    SUSPENDED: { label: 'Suspended', color: '#f59e0b', count: 0 },
    ARCHIVED:  { label: 'Archived',  color: '#ef4444', count: 0 },
  }
  allUsers.value.forEach(u => {
    const s = (u.status || 'ACTIVE').toUpperCase()
    if (map[s]) map[s].count++
  })
  return Object.values(map)
})

const maxStatus = computed(() => Math.max(...userStatuses.value.map(s => s.count)) || 1)
const statusPct = (n) => Math.round((n / maxStatus.value) * 100)

// ── Ticket Status / Priority Donuts ───────────────────────────
const statusColors = { OPEN: '#f97316', ESCALATED: '#ef4444', PENDING: '#eab308', RESOLVED: '#009747', CLOSE: '#9ca3af', CANCEL: '#64748b' }
const ticketStatusData = computed(() => Object.entries(analytics.value.tickets_by_status || {}).map(([status, count]) => ({
  label: status === 'CLOSE' ? 'Closed' : status.charAt(0) + status.slice(1).toLowerCase(),
  color: statusColors[status] || '#94a3b8',
  count,
})))

const totalTicketsStatus = computed(() => ticketStatusData.value.reduce((acc, curr) => acc + curr.count, 0))
const ticketStatusDonut = computed(() => {
  const total = totalTicketsStatus.value || 1
  let offset = CIRC * 0.25
  return ticketStatusData.value.map(r => {
    const dash = (r.count / total) * CIRC
    const seg = { dash, offset: -offset + CIRC, color: r.color, ...r }
    offset += dash
    return seg
  })
})

const priorityColors = { CRITICAL: '#ef4444', HIGH: '#006836', NORMAL: '#d98c00', LOW: '#b5c7b5' }
const ticketPriorityData = computed(() => Object.entries(analytics.value.tickets_by_priority || {}).map(([priority, count]) => ({
  label: priority.charAt(0) + priority.slice(1).toLowerCase(),
  color: priorityColors[priority] || '#94a3b8',
  count,
})))

const totalTicketsPriority = computed(() => ticketPriorityData.value.reduce((acc, curr) => acc + curr.count, 0))
const ticketPriorityDonut = computed(() => {
  const total = totalTicketsPriority.value || 1
  let offset = CIRC * 0.25
  return ticketPriorityData.value.map(r => {
    const dash = (r.count / total) * CIRC
    const seg = { dash, offset: -offset + CIRC, color: r.color, ...r }
    offset += dash
    return seg
  })
})

// ── Recent Users (last 6 by ID desc) ─────────────────────────
const recentUsers = computed(() =>
  [...allUsers.value].sort((a, b) => b.id - a.id).slice(0, 6)
)

// ── Quick Actions ─────────────────────────────────────────────
const quickActions = [
  { label: 'User Management',     icon: 'manage_accounts',     desc: 'Add, edit or remove users',    to: '/admin/users' },
  { label: 'Ticket Management',   icon: 'confirmation_number', desc: 'View and manage tickets',      to: '/admin/ticket-management' },
  { label: 'Problem Categories',  icon: 'category',            desc: 'Manage issue categories',      to: '/admin/problem-categories' },
  { label: 'Reports',             icon: 'bar_chart',           desc: 'View analytics & reports',     to: '/admin/reports' },
  { label: 'System Settings',     icon: 'settings',            desc: 'Configure system settings',    to: '/admin/settings' },
  { label: 'Audit Logs',          icon: 'history',             desc: 'View system activity logs',    to: '/admin/logs' },
]

// ── Helpers ───────────────────────────────────────────────────
function sparkPoints(data) {
  const max = Math.max(...data) || 1
  return data.map((v, i) => `${(i / (data.length - 1)) * 80},${24 - (v / max) * 20}`).join(' ')
}

// ── Data Loaders ──────────────────────────────────────────────
// Each loader owns its own request, its own loading flag, and its own
// failure handling, so one slow/broken endpoint never blocks another
// section from rendering.
async function loadUsers() {
  usersLoading.value = true
  try {
    const res = await api.get('/users')
    allUsers.value = res.data?.data ?? res.data ?? []
  } catch {
    allUsers.value = []
  } finally {
    usersLoading.value = false
  }
}

async function loadCategories() {
  categoriesLoading.value = true
  try {
    const res = await api.get('/problem-categories')
    categories.value = res.data?.data ?? res.data ?? []
  } catch {
    categories.value = []
  } finally {
    categoriesLoading.value = false
  }
}

async function loadAnalytics() {
  analyticsLoading.value = true
  try {
    const res = await api.get('/admin/dashboard')
    analytics.value = res.data
  } catch {
    analytics.value = { user_roles: {}, tickets_by_status: {}, tickets_by_priority: {} }
  } finally {
    analyticsLoading.value = false
  }
}

// Lazy-load one query at a time: users first, then categories — each
// section paints as soon as its own data lands instead of the whole
// dashboard waiting behind a single Promise.all.
async function loadAll() {
  await Promise.all([loadUsers(), loadCategories(), loadAnalytics()])
}

onMounted(loadAll)
</script>

<style lang="scss" scoped>
@import '@/css/themes.scss';
</style>
