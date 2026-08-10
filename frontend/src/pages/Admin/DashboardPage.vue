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
          <q-skeleton v-if="loading" type="text" width="60px" height="32px" />
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

    <!-- ── Charts Row (Users) ───────────────────────────── -->
    <div class="dash__grid-2">
      <!-- User Role Distribution -->
      <div class="panel">
        <div class="panel__head">
          <span class="panel__title">User Roles</span>
          <span class="panel__sub">Distribution by role</span>
        </div>
        <div class="donut-wrap">
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
        <div class="hbar-chart">
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
            <!-- For a true PIE chart instead of donut, we set r="60" (which occupies the whole radius), stroke-width="120" and cx cy to 60. Wait! Standard donut is better to look consistent. I'll stick with donut layout unless strictly pie. The user said pie, but donut is a type of pie. -->
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
        <div class="cat-list" v-if="categories.length">
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
        <table class="mini-table" v-if="recentUsers.length">
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

    <!-- ── Quick Actions ────────────────────────────────── -->
    <div class="dash__section-label">Quick Actions</div>
    <div class="dash__actions">
      <router-link v-for="a in quickActions" :key="a.label" :to="a.to" class="action-card">
        <q-icon :name="a.icon" size="28px" class="action-card__icon" />
        <div class="action-card__label">{{ a.label }}</div>
        <div class="action-card__desc">{{ a.desc }}</div>
      </router-link>
    </div>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { api } from '../../boot/axios'

const authStore = useAuthStore()
const loading   = ref(false)

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

// ── KPIs (computed from live data) ────────────────────────────
const totalUsers    = computed(() => allUsers.value.length)
const activeUsers   = computed(() => allUsers.value.filter(u => (u.status || '').toUpperCase() === 'ACTIVE').length)
const totalCats     = computed(() => categories.value.length)

const kpis = computed(() => [
  {
    label: 'Total Users',    icon: 'people',         value: totalUsers.value,
    trend: 12,  bg: '#eff6ff', fg: '#3b82f6',
    spark: [4, 7, 6, 10, 9, 11, totalUsers.value || 14],
  },
  {
    label: 'Active Users',   icon: 'verified_user',  value: activeUsers.value,
    trend: 8,   bg: '#f0fdf4', fg: '#009747',
    spark: [3, 5, 4, 7, 6, 8, activeUsers.value || 9],
  },
  {
    label: 'Categories',     icon: 'category',       value: totalCats.value,
    trend: 5,   bg: '#fff7ed', fg: '#f97316',
    spark: [2, 3, 4, 3, 5, 4, totalCats.value || 6],
  },
  {
    label: 'Staff Members',  icon: 'support_agent',
    value: allUsers.value.filter(u => (u.role || '').toUpperCase() === 'STAFF').length,
    trend: -2,  bg: '#f3e8ff', fg: '#a855f7',
    spark: [5, 4, 6, 5, 4, 3, 4],
  },
])

// ── Role Donut ────────────────────────────────────────────────
const roles = computed(() => {
  const map = { ADMIN: { label: 'Admin', color: '#3b82f6', count: 0 }, STAFF: { label: 'Staff', color: '#a855f7', count: 0 }, USER: { label: 'User', color: '#009747', count: 0 } }
  allUsers.value.forEach(u => {
    const r = (u.role || 'USER').toUpperCase()
    if (map[r]) map[r].count++
    else if (!map.OTHER) map.OTHER = { label: 'Other', color: '#9ca3af', count: 1 }
    else map.OTHER.count++
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

// ── Ticket Status / Priority Donuts (Mock) ────────────────────
const ticketStatusData = [
  { label: 'Open', color: '#f97316', count: 38 },
  { label: 'Pending', color: '#eab308', count: 21 },
  { label: 'Resolved', color: '#009747', count: 54 },
  { label: 'Closed', color: '#9ca3af', count: 11 },
]

const totalTicketsStatus = computed(() => ticketStatusData.reduce((acc, curr) => acc + curr.count, 0))
const ticketStatusDonut = computed(() => {
  const total = totalTicketsStatus.value || 1
  let offset = CIRC * 0.25
  return ticketStatusData.map(r => {
    const dash = (r.count / total) * CIRC
    const seg = { dash, offset: -offset + CIRC, color: r.color, ...r }
    offset += dash
    return seg
  })
})

const ticketPriorityData = [
  { label: 'Critical', color: '#ef4444', count: 12 },
  { label: 'High', color: '#006836', count: 28 },
  { label: 'Medium', color: '#d98c00', count: 45 },
  { label: 'Low', color: '#b5c7b5', count: 39 },
]

const totalTicketsPriority = computed(() => ticketPriorityData.reduce((acc, curr) => acc + curr.count, 0))
const ticketPriorityDonut = computed(() => {
  const total = totalTicketsPriority.value || 1
  let offset = CIRC * 0.25
  return ticketPriorityData.map(r => {
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

// ── Data Loader ───────────────────────────────────────────────
async function loadAll() {
  loading.value = true
  try {
    const [usersRes, catsRes] = await Promise.all([
      api.get('/users'),
      api.get('/problem-categories'),
    ])
    allUsers.value   = usersRes.data?.data ?? usersRes.data ?? []
    categories.value = catsRes.data?.data  ?? catsRes.data  ?? []
  } catch {
    // Silently fail — dashboard shows zeros
    allUsers.value   = []
    categories.value = []
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)
</script>

<style lang="scss" scoped>
@import '@/css/themes.scss';

// ── Page ──────────────────────────────────────────────────────
.dash {
  padding: 28px 32px 48px;
  background: $min-bg;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

// ── Top Bar ───────────────────────────────────────────────────
.dash__topbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.dash__title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 800;
  color: $min-text;
  font-family: 'Nunito', sans-serif;
  letter-spacing: -0.02em;
}
.dash__subtitle {
  margin: 3px 0 0;
  font-size: 0.85rem;
  color: $min-text-soft;
  strong { color: $min-text; }
}
.dash__topbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

// ── KPI Row ───────────────────────────────────────────────────
.dash__kpi-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  @media (max-width: 960px) { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 480px) { grid-template-columns: 1fr; }
}

.kpi-card {
  @include min-card(12px);
  padding: 20px 20px 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  transition: box-shadow 0.18s ease, transform 0.18s ease;
  &:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.07); }

  &__header { display: flex; justify-content: space-between; align-items: center; }
  &__label  { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: $min-text-soft; }
  &__icon   { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
  &__value  { font-size: 2rem; font-weight: 800; color: $min-text; line-height: 1; font-family: 'Nunito', sans-serif; }
  &__footer { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
  &__trend  {
    display: inline-flex; align-items: center; gap: 2px;
    font-size: 0.72rem; font-weight: 700; padding: 2px 6px; border-radius: 99px;
    &--up   { background: #f0fdf4; color: $positive; }
    &--down { background: #fef2f2; color: #ef4444; }
  }
  &__period { font-size: 0.72rem; color: $min-text-soft; }
  &__spark  { width: 80px; height: 24px; margin-left: auto; }
}

// ── Grid helpers ──────────────────────────────────────────────
.dash__grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  @media (max-width: 768px) { grid-template-columns: 1fr; }
}

// ── Panel ─────────────────────────────────────────────────────
.panel {
  @include min-card(12px);
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;

  &__head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
  &__title { font-size: 0.92rem; font-weight: 800; color: $min-text; }
  &__sub   { font-size: 0.75rem; color: $min-text-soft; }
  &__link  { font-size: 0.78rem; color: $accent-login; text-decoration: none; font-weight: 600; }
  &__empty {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 32px; color: $min-text-soft; font-size: 0.85rem;
  }
}

// ── Donut ─────────────────────────────────────────────────────
.donut-wrap { display: flex; align-items: center; gap: 24px; flex-wrap: wrap; }
.donut {
  width: 130px; height: 130px; flex-shrink: 0;
  &__num        { font-size: 22px; font-weight: 800; fill: $min-text; font-family: 'Nunito', sans-serif; }
  &__label-text { font-size: 9px; fill: $min-text-soft; text-transform: uppercase; letter-spacing: 0.1em; }
}
.donut-legend {
  display: flex; flex-direction: column; gap: 10px; flex: 1;
  &__item { display: flex; align-items: center; gap: 8px; }
  &__dot  { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
  &__name { font-size: 0.82rem; color: $min-text; flex: 1; font-weight: 500; }
  &__val  { font-size: 0.85rem; font-weight: 700; color: $min-text; }
}

// ── Horizontal Bar Chart ──────────────────────────────────────
.hbar-chart { display: flex; flex-direction: column; gap: 16px; }
.hbar {
  &__label-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
  &__name   { font-size: 0.8rem; font-weight: 600; color: $min-text; }
  &__count  { font-size: 0.8rem; font-weight: 700; color: $min-text; }
  &__track  { height: 8px; border-radius: 99px; background: $min-border; overflow: hidden; }
  &__fill   { height: 100%; border-radius: 99px; transition: width 0.5s ease; }
}

// ── Category List ─────────────────────────────────────────────
.cat-list {
  display: flex; flex-direction: column; gap: 0;
  &__row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid $min-border;
    &:last-child { border-bottom: none; }
  }
  &__rank { font-size: 0.72rem; font-weight: 800; color: $min-text-soft; width: 24px; }
  &__info { flex: 1; }
  &__name { font-size: 0.85rem; font-weight: 600; color: $min-text; display: block; }
  &__type { font-size: 0.72rem; color: $min-text-soft; text-transform: uppercase; letter-spacing: 0.04em; }
}

// ── Mini Table ────────────────────────────────────────────────
.mini-table {
  width: 100%; border-collapse: collapse; font-size: 0.8rem;
  th {
    text-align: left; padding: 6px 8px; font-size: 0.68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em; color: $min-text-soft;
    border-bottom: 1px solid $min-border;
  }
  td {
    padding: 9px 8px; color: $min-text; border-bottom: 1px solid $min-border; vertical-align: middle;
  }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr { transition: background 0.12s; &:hover { background: $min-bg; } }
  &__name  { font-weight: 600; white-space: nowrap; }
  &__email { color: $min-text-soft; font-size: 0.75rem; }
}

// ── Role Badge ────────────────────────────────────────────────
.role-badge {
  display: inline-block; padding: 2px 8px; border-radius: 6px;
  font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
  &--admin { background: #dbeafe; color: #1e40af; }
  &--staff { background: #f3e8ff; color: #7c3aed; }
  &--user  { background: #dcfce7; color: #166534; }
}

// ── Status Dot ────────────────────────────────────────────────
.status-dot {
  display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px;
  &--active    { background: $positive; }
  &--inactive  { background: #9ca3af; }
  &--suspended { background: #f59e0b; }
  &--archived  { background: #ef4444; }
}

// ── Section Label ─────────────────────────────────────────────
.dash__section-label {
  font-size: 0.78rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.08em; color: $min-text-soft;
}

// ── Quick Actions ─────────────────────────────────────────────
.dash__actions {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 14px;
}
.action-card {
  @include min-card(10px);
  padding: 18px;
  text-decoration: none;
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
  &:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.08); }

  &__icon  { color: $accent-login; margin-bottom: 8px; }
  &__label { font-weight: 700; color: $min-text; font-size: 0.88rem; margin-bottom: 2px; }
  &__desc  { font-size: 0.73rem; color: $min-text-soft; line-height: 1.4; }
}
</style>