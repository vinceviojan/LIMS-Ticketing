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
</style>
