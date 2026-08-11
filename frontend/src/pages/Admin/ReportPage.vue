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

    <!-- Error Banner -->
    <q-banner v-if="error" class="bg-negative text-white q-mb-md rounded-borders" style="border-radius: 8px;">
      {{ error }}
      <template v-slot:action>
        <q-btn flat color="white" label="Retry" no-caps @click="fetchReportData" />
      </template>
    </q-banner>

    <!-- Loading State -->
    <div v-if="loading && !kpiCards.length" class="row justify-center items-center q-py-xl">
      <q-spinner-dots size="48px" color="primary" />
    </div>

    <template v-else>
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
            <div v-if="!ticketsByStatus.length" class="text-caption text-grey-6 text-center q-py-md">
              No ticket status data available
            </div>
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
            <div v-if="!ticketsByCategory.length" class="text-caption text-grey-6 text-center q-py-md">
              No ticket category data available
            </div>
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
            <div v-if="!resolutionTimes.length" class="text-caption text-grey-6 text-center q-py-md">
              No resolution time data available
            </div>
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
            <div v-if="!topRequesters.length" class="text-caption text-grey-6 text-center q-py-md">
              No top requesters found
            </div>
            <div v-for="(req, idx) in topRequesters" :key="req.name" class="req-row">
              <span class="req-row__rank">{{ idx + 1 }}</span>
              <div class="req-row__avatar">{{ req.name ? req.name[0].toUpperCase() : 'U' }}</div>
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
          <div v-if="!monthlyData.length" class="text-caption text-grey-6 text-center full-width q-py-md">
            No monthly trend data available
          </div>
          <div v-for="month in monthlyData" :key="month.label" class="monthly-bar">
            <div class="monthly-bar__col">
              <div class="monthly-bar__fill" :style="{ height: month.pct + '%' }" />
            </div>
            <div class="monthly-bar__count">{{ month.count }}</div>
            <div class="monthly-bar__label">{{ month.label }}</div>
          </div>
        </div>
      </div>
    </template>

    <q-inner-loading :showing="loading && kpiCards.length > 0">
      <q-spinner-dots size="40px" color="primary" />
    </q-inner-loading>

  </q-page>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from '../../boot/axios'

const activePeriod = ref('month')
const loading = ref(true)
const error = ref(null)

const periods = [
  { label: 'Week',  value: 'week'  },
  { label: 'Month', value: 'month' },
  { label: 'Year',  value: 'year'  },
]

const kpiCards = ref([])
const ticketsByStatus = ref([])
const ticketsByCategory = ref([])
const resolutionTimes = ref([])
const topRequesters = ref([])
const monthlyData = ref([])

const fetchReportData = async () => {
  loading.value = true
  error.value = null
  try {
    const { data } = await api.get('/reports/summary', {
      params: { period: activePeriod.value },
    })

    console.log(data)
    kpiCards.value = data.kpis || []
    ticketsByStatus.value = data.tickets_by_status || []
    ticketsByCategory.value = data.tickets_by_category || []
    resolutionTimes.value = data.resolution_times || []
    topRequesters.value = data.top_requesters || []
    monthlyData.value = data.monthly_data || []
  } catch (err) {
    console.error('Failed to fetch admin reports:', err)
    error.value = err.response?.data?.message || 'Failed to load report analytics. Please check backend connection.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchReportData()
})

watch(activePeriod, () => {
  fetchReportData()
})
</script>

<style lang="scss" scoped>
@import '@/css/themes.scss';

.report-page {
  padding: 32px;
  background: $min-bg;
  min-height: 100vh;

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 28px;
  }

  &__title {
    color: $min-text;
    font-weight: 800;
    font-family: 'Nunito', sans-serif;
  }

  &__subtitle {
    color: $min-text-soft;
    font-size: 0.85rem;
    margin-top: 2px;
  }

  &__period-switcher {
    border-radius: 8px;
    background: $min-surface;
    border: 1px solid $min-border;
    overflow: hidden;
    gap: 0;
  }

  &__kpis {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 18px;
    margin-bottom: 28px;
  }

  &__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }
}

// ── Period Btn ────────────────────────────────────────────────
.period-btn {
  background: transparent;
  color: $min-text-soft;
  font-weight: 600;
  font-size: 0.82rem;
  padding: 7px 18px;
  border-right: 1px solid $min-border;
  border-radius: 0;

  &:last-child {
    border-right: none;
  }

  &--active {
    background: $min-bg;
    color: $accent-login;
  }
}

// ── KPI Card ──────────────────────────────────────────────────
.kpi-card {
  @include min-card();
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  transition: transform 0.18s;
  &:hover { transform: translateY(-3px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }

  &__icon-wrap {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  &__value {
    font-size: 1.55rem;
    font-weight: 800;
    color: $min-text;
    font-family: 'Nunito', sans-serif;
    line-height: 1;
  }

  &__label {
    font-size: 0.75rem;
    color: $min-text-soft;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin: 3px 0;
  }

  &__trend {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.72rem;
    font-weight: 600;
    &--up   { color: $positive; }
    &--down { color: #ef4444; }
  }

  &--blue   .kpi-card__icon-wrap { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; }
  &--orange .kpi-card__icon-wrap { background: #fff7ed; color: #f97316; border: 1px solid #fed7aa; }
  &--green  .kpi-card__icon-wrap { background: #f0fdf4; color: $positive; border: 1px solid #bbf7d0; }
  &--purple .kpi-card__icon-wrap { background: #f5f3ff; color: #8b5cf6; border: 1px solid #ddd6fe; }
}

// ── Report Card ───────────────────────────────────────────────
.report-card {
  @include min-card();
  padding: 20px;

  &--full { grid-column: 1 / -1; }

  &__title {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: $min-text-soft;
    margin-bottom: 16px;
  }

  &__bars { display: flex; flex-direction: column; gap: 12px; }
  &__resolution { display: flex; flex-direction: column; gap: 10px; }
  &__list { display: flex; flex-direction: column; gap: 10px; }
}

// ── Bar Row ───────────────────────────────────────────────────
.bar-row {
  display: flex;
  align-items: center;
  gap: 10px;

  &__label {
    width: 76px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $min-text;
    flex-shrink: 0;
  }

  &__track {
    flex: 1;
    height: 10px;
    border-radius: 99px;
    background: $min-bg;
    overflow: hidden;
  }

  &__fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.6s ease;

    &--primary { background: $accent-login; }
    &--orange  { background: #f97316; }
    &--yellow  { background: #eab308; }
    &--green   { background: $positive; }
    &--grey    { background: $min-text-soft; }
  }

  &__count {
    width: 36px;
    font-size: 0.78rem;
    font-weight: 700;
    color: $min-text-soft;
    text-align: right;
    flex-shrink: 0;
  }
}

// ── Resolution Row ────────────────────────────────────────────
.res-row {
  display: flex;
  align-items: center;
  gap: 10px;

  &__priority {
    width: 68px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 3px 8px;
    border-radius: 6px;
    flex-shrink: 0;
    text-align: center;
    color: #fff;

    &--critical { background: #ef4444; }
    &--high     { background: $accent-login; }
    &--medium   { background: #f59e0b; }
    &--low      { background: $min-text-soft; }
  }

  &__time {
    width: 56px;
    font-size: 0.8rem;
    font-weight: 700;
    color: $min-text;
    flex-shrink: 0;
  }

  &__track {
    flex: 1;
    height: 8px;
    border-radius: 99px;
    background: $min-bg;
    overflow: hidden;
  }

  &__fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.6s ease;
  }
}

// ── Requester Row ─────────────────────────────────────────────
.req-row {
  display: flex;
  align-items: center;
  gap: 12px;

  &__rank {
    width: 22px;
    font-size: 0.76rem;
    font-weight: 700;
    color: $min-text-soft;
    flex-shrink: 0;
    text-align: center;
  }

  &__avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #ecfdf5;
    border: 1px solid #d1fae5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.85rem;
    color: $accent-login;
    flex-shrink: 0;
  }

  &__info { flex: 1; }

  &__name {
    font-size: 0.86rem;
    font-weight: 700;
    color: $min-text;
  }

  &__dept {
    font-size: 0.72rem;
    color: $min-text-soft;
  }

  &__count {
    font-size: 0.78rem;
    font-weight: 700;
    color: $accent-login;
    flex-shrink: 0;
  }
}

// ── Monthly Chart ─────────────────────────────────────────────
.monthly-chart {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  height: 140px;
  padding-top: 10px;
}

.monthly-bar {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  flex: 1;

  &__col {
    width: 100%;
    max-width: 52px;
    flex: 1;
    display: flex;
    align-items: flex-end;
    background: $min-bg;
    border-radius: 6px 6px 0 0;
    overflow: hidden;
  }

  &__fill {
    width: 100%;
    background: $accent-login;
    border-radius: 6px 6px 0 0;
    transition: height 0.7s ease;
  }

  &__count {
    font-size: 0.75rem;
    font-weight: 700;
    color: $min-text;
  }

  &__label {
    font-size: 0.7rem;
    font-weight: 600;
    color: $min-text-soft;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
}
</style>
