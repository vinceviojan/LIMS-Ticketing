<template>
  <q-page class="report-page">

    <!-- ── Header ──────────────────────────────────────────────── -->
    <div class="report-page__header">
      <div>
        <div class="text-h5 report-page__title">Reports & Analytics</div>
        <div class="report-page__subtitle">Insights on tickets, users, and system performance</div>
      </div>
      <q-btn-group flat class="report-page__period-switcher">
        <q-btn
          v-for="p in periods"
          :key="p.value"
          :label="p.label"
          no-caps
          :class="['period-btn', activePeriod === p.value ? 'period-btn--active' : '']"
          @click="activePeriod = p.value"
        />
      </q-btn-group>
    </div>

    
    <!-- ── KPI Cards ────────────────────────────────────────────── -->
    <div class="report-page__kpis">
      <div v-for="kpi in kpiCards" :key="kpi.label" class="kpi-card" :class="`kpi-card--${kpi.color}`">
        <div class="kpi-card__icon-wrap">
          <q-icon :name="kpi.icon" size="24px" />
        </div>
        <div class="kpi-card__body">
          <div class="kpi-card__value">{{ kpi.value }}</div>
          <div class="kpi-card__label">{{ kpi.label }}</div>
          <div class="kpi-card__trend" :class="kpi.up ? 'kpi-card__trend--up' : 'kpi-card__trend--down'">
            <q-icon :name="kpi.up ? 'trending_up' : 'trending_down'" size="14px" />
            {{ kpi.trend }}
          </div>
        </div>
      </div>
    </div>

    <div class="report-page__grid">
      <!-- ── Ticket by Status ───────────────────────────────────── -->
      <div class="report-card">
        <div class="report-card__title">Tickets by Status</div>
        <div class="report-card__bars">
          <div v-for="item in ticketsByStatus" :key="item.label" class="bar-row">
            <span class="bar-row__label">{{ item.label }}</span>
            <div class="bar-row__track">
              <div
                class="bar-row__fill"
                :class="`bar-row__fill--${item.color}`"
                :style="{ width: item.pct + '%' }"
              />
            </div>
            <span class="bar-row__count">{{ item.count }}</span>
          </div>
        </div>
      </div>

      <!-- ── Tickets by Category ───────────────────────────────── -->
      <div class="report-card">
        <div class="report-card__title">Tickets by Category</div>
        <div class="report-card__bars">
          <div v-for="item in ticketsByCategory" :key="item.label" class="bar-row">
            <span class="bar-row__label">{{ item.label }}</span>
            <div class="bar-row__track">
              <div
                class="bar-row__fill bar-row__fill--primary"
                :style="{ width: item.pct + '%' }"
              />
            </div>
            <span class="bar-row__count">{{ item.count }}</span>
          </div>
        </div>
      </div>

      <!-- ── Resolution Time ───────────────────────────────────── -->
      <div class="report-card">
        <div class="report-card__title">Avg. Resolution Time</div>
        <div class="report-card__resolution">
          <div v-for="item in resolutionTimes" :key="item.priority" class="res-row">
            <span :class="['res-row__priority', `res-row__priority--${item.priority.toLowerCase()}`]">
              {{ item.priority }}
            </span>
            <div class="res-row__time">{{ item.hours }}h avg</div>
            <div class="res-row__track">
              <div class="res-row__fill" :style="{ width: item.pct + '%', background: item.color }" />
            </div>
          </div>
        </div>
      </div>

      <!-- ── Top Requesters ────────────────────────────────────── -->
      <div class="report-card">
        <div class="report-card__title">Top Requesters</div>
        <div class="report-card__list">
          <div v-for="(req, idx) in topRequesters" :key="req.name" class="req-row">
            <span class="req-row__rank">{{ idx + 1 }}</span>
            <div class="req-row__avatar">{{ req.name[0] }}</div>
            <div class="req-row__info">
              <div class="req-row__name">{{ req.name }}</div>
              <div class="req-row__dept">{{ req.dept }}</div>
            </div>
            <span class="req-row__count">{{ req.tickets }} tickets</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Monthly Trend (simple ASCII-art bar chart) ───────────── -->
    <div class="report-card report-card--full">
      <div class="report-card__title">Monthly Ticket Volume</div>
      <div class="monthly-chart">
        <div v-for="month in monthlyData" :key="month.label" class="monthly-bar">
          <div class="monthly-bar__col">
            <div class="monthly-bar__fill" :style="{ height: month.pct + '%' }" />
          </div>
          <div class="monthly-bar__count">{{ month.count }}</div>
          <div class="monthly-bar__label">{{ month.label }}</div>
        </div>
      </div>
    </div>

  </q-page>
</template>

<script setup>
import { ref } from 'vue'

const activePeriod = ref('month')
const periods = [
  { label: 'Week',  value: 'week'  },
  { label: 'Month', value: 'month' },
  { label: 'Year',  value: 'year'  },
]

const kpiCards = [
  { label: 'Total Tickets',    value: '124', icon: 'confirmation_number', color: 'blue',   trend: '+12 this week', up: true  },
  { label: 'Open Tickets',     value: '38',  icon: 'inbox',               color: 'orange', trend: '+5 since yesterday', up: true  },
  { label: 'Resolved Today',   value: '14',  icon: 'task_alt',            color: 'green',  trend: '-2 vs yesterday', up: false },
  { label: 'Avg Response Time',value: '4.2h', icon: 'timer',              color: 'purple', trend: '-0.8h vs last week', up: false },
]

const ticketsByStatus = [
  { label: 'Open',     count: 38, pct: 76, color: 'orange' },
  { label: 'Pending',  count: 21, pct: 42, color: 'yellow' },
  { label: 'Resolved', count: 54, pct: 100, color: 'green' },
  { label: 'Closed',   count: 11, pct: 22, color: 'grey'   },
]

const ticketsByCategory = [
  { label: 'Hardware', count: 48, pct: 100 },
  { label: 'Software', count: 35, pct: 73  },
  { label: 'Network',  count: 22, pct: 46  },
  { label: 'Account',  count: 12, pct: 25  },
  { label: 'Other',    count: 7,  pct: 15  },
]

const resolutionTimes = [
  { priority: 'Critical', hours: 2.1,  pct: 100, color: '#e74c3c' },
  { priority: 'High',     hours: 6.4,  pct: 60,  color: '#006836' },
  { priority: 'Medium',   hours: 12.3, pct: 40,  color: '#d98c00' },
  { priority: 'Low',      hours: 24.8, pct: 20,  color: '#b5c7b5' },
]

const topRequesters = [
  { name: 'Juan dela Cruz',  dept: 'Laboratory Division', tickets: 18 },
  { name: 'Maria Santos',    dept: 'IT Services',          tickets: 14 },
  { name: 'Pedro Reyes',     dept: 'Research Section',     tickets: 11 },
  { name: 'Ana Garcia',      dept: 'Admin Office',         tickets: 9  },
  { name: 'Rosa Lim',        dept: 'Technology Division',  tickets: 7  },
]

const monthlyData = [
  { label: 'Mar', count: 18, pct: 33 },
  { label: 'Apr', count: 24, pct: 44 },
  { label: 'May', count: 31, pct: 57 },
  { label: 'Jun', count: 27, pct: 50 },
  { label: 'Jul', count: 42, pct: 78 },
  { label: 'Aug', count: 54, pct: 100 },
]
</script>

<style lang="scss" scoped>
</style>
