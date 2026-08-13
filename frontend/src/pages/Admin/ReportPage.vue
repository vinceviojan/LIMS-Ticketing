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
          @click="changePeriod(p.value)"
        />
      </q-btn-group>
    </div>

    <!-- ── Loading Overlay / Inner ─────────────────────────────── -->
    <q-inner-loading :showing="loading">
      <q-spinner-dots size="50px" color="primary" />
    </q-inner-loading>

    <!-- ── KPI Cards ────────────────────────────────────────────── -->
    <div class="report-page__kpis">
      <div
        v-for="kpi in kpiCards"
        :key="kpi.label"
        class="kpi-card"
        :class="`kpi-card--${kpi.color}`"
      >
        <div class="kpi-card__header">
          <span class="kpi-card__label">{{ kpi.label }}</span>
          <div class="kpi-card__icon">
            <q-icon :name="kpi.icon" size="18px" />
          </div>
        </div>
        <div class="kpi-card__value">{{ kpi.value }}</div>
        <div class="kpi-card__footer">
          <span
            class="kpi-card__trend"
            :class="kpi.up ? 'kpi-card__trend--up' : 'kpi-card__trend--down'"
          >
            <q-icon :name="kpi.up ? 'trending_up' : 'trending_down'" size="14px" />
            {{ kpi.trend }}
          </span>
        </div>
      </div>
    </div>

    <div class="report-page__grid">
      <!-- ── Tickets by Status (Pie Chart) ─────────────────────── -->
      <div class="report-card">
        <div class="report-card__title">Tickets by Status</div>
        <div class="status-chart-container">
          <!-- Donut Pie Chart -->
          <div class="pie-chart-wrap">
            <div class="pie-chart" :style="{ background: pieChartStyle }">
              <div class="pie-chart__hole">
                <div class="pie-chart__total">{{ totalStatusTickets }}</div>
                <div class="pie-chart__total-label">Tickets</div>
              </div>
            </div>
          </div>
          <!-- Legend / List -->
          <div class="status-legend">
            <div v-for="item in ticketsByStatus" :key="item.label" class="legend-row">
              <div class="legend-row__dot" :class="`legend-row__dot--${item.color}`" />
              <span class="legend-row__label">{{ item.label }}</span>
              <span class="legend-row__count">{{ item.count }}</span>
              <span class="legend-row__pct">({{ getStatusPercentage(item.count) }}%)</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tickets by Category (Paginated) ────────────────────── -->
      <div class="report-card flex flex-col justify-between">
        <div>
          <div class="row items-center justify-between q-mb-sm">
            <div class="report-card__title">Tickets by Category</div>
            <div class="text-caption text-grey-7">{{ ticketsByCategory.length }} categories</div>
          </div>
          <div class="report-card__bars">
            <div v-for="item in paginatedCategories" :key="item.label" class="bar-row">
              <span class="bar-row__label" :title="item.label">{{ item.label }}</span>
              <div class="bar-row__track">
                <div
                  class="bar-row__fill bar-row__fill--primary"
                  :style="{ width: item.pct + '%' }"
                />
              </div>
              <span class="bar-row__count">{{ item.count }}</span>
            </div>
            <div
              v-if="!ticketsByCategory.length"
              class="text-grey-6 text-caption text-center q-py-md"
            >
              No categories found
            </div>
          </div>
        </div>

        <!-- Pagination Controls for Categories -->
        <div v-if="maxCategoryPages > 1" class="report-card__pagination">
          <q-pagination
            v-model="categoryPage"
            :max="maxCategoryPages"
            :max-pages="5"
            direction-links
            boundary-links
            dense
            color="primary"
            active-color="primary"
            active-text-color="white"
            size="sm"
          />
        </div>
      </div>

      <!-- ── Resolution Time ───────────────────────────────────── -->
      <div class="report-card">
        <div class="report-card__title">Avg. Resolution Time</div>
        <div class="report-card__resolution">
          <div v-for="item in resolutionTimes" :key="item.priority" class="res-row">
            <span
              :class="['res-row__priority', `res-row__priority--${item.priority.toLowerCase()}`]"
            >
              {{ item.priority }}
            </span>
            <div class="res-row__time">{{ item.hours }}h avg</div>
            <div class="res-row__track">
              <div
                class="res-row__fill"
                :style="{ width: item.pct + '%', background: item.color }"
              />
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
          <div v-if="!topRequesters.length" class="text-grey-6 text-caption text-center q-py-md">
            No requester data available
          </div>
        </div>
      </div>
    </div>

    <!-- ── Monthly Trend (bar chart) ───────────── -->
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

    <!-- ── Top Resolving Staff Table ────────────── -->
    <div class="report-card report-card--full q-mt-md">
      <div class="row items-center justify-between q-mb-md">
        <div>
          <div class="report-card__title">Staff Resolution & Rating Leaderboard</div>
          <div class="text-caption text-grey-7">
            Staff members with resolved tickets and average user satisfaction ratings
          </div>
        </div>
      </div>

      <q-table
        flat
        bordered
        :rows="topStaff"
        :columns="staffColumns"
        row-key="id"
        hide-bottom
        :pagination="{ rowsPerPage: 10 }"
        class="staff-table"
      >
        <template #body-cell-rank="props">
          <q-td :props="props">
            <q-badge
              :color="
                props.rowIndex === 0
                  ? 'amber-9'
                  : props.rowIndex === 1
                    ? 'grey-7'
                    : props.rowIndex === 2
                      ? 'deep-orange-7'
                      : 'blue-grey-5'
              "
              rounded
              class="q-px-sm text-weight-bold"
            >
              #{{ props.rowIndex + 1 }}
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-staff="props">
          <q-td :props="props">
            <div class="row items-center gap-sm">
              <q-avatar size="32px" color="primary" text-color="white" class="text-weight-bold">
                {{ props.row.name ? props.row.name[0].toUpperCase() : 'S' }}
              </q-avatar>
              <div>
                <div class="text-weight-bold text-slate-800">{{ props.row.name }}</div>
                <div class="text-caption text-grey-6">{{ props.row.dept }}</div>
              </div>
            </div>
          </q-td>
        </template>

        <template #body-cell-resolved_count="props">
          <q-td :props="props">
            <q-chip
              dense
              color="green-1"
              text-color="green-9"
              icon="task_alt"
              class="text-weight-bold"
            >
              {{ props.row.resolved_count }} tickets
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-rating="props">
          <q-td :props="props">
            <div v-if="props.row.avg_rating" class="row items-center gap-xs">
              <q-rating
                :model-value="props.row.avg_rating"
                size="16px"
                color="amber"
                icon="star"
                icon-half="star_half"
                readonly
              />
              <span class="text-weight-bold text-caption text-slate-700 q-ml-xs">
                {{ props.row.avg_rating }} / 5.0
              </span>
            </div>
            <span v-else class="text-caption text-grey-5">No ratings yet</span>
          </q-td>
        </template>
      </q-table>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '../../boot/axios'
import { useQuasar } from 'quasar'
import './ReportPage.scss'

const $q = useQuasar()
const loading = ref(true)
const activePeriod = ref('today')
const periods = [
  { label: 'Today', value: 'today' },
  { label: 'Week', value: 'week' },
  { label: 'Month', value: 'month' },
  { label: 'Year', value: 'year' },
]

const kpiCards = ref([])
const ticketsByStatus = ref([])
const ticketsByCategory = ref([])
const resolutionTimes = ref([])
const topRequesters = ref([])
const monthlyData = ref([])
const topStaff = ref([])

const staffColumns = [
  { name: 'rank', label: 'Rank', align: 'center', style: 'width: 70px' },
  { name: 'staff', label: 'Staff Member', align: 'left', field: 'name', sortable: true },
  { name: 'dept', label: 'Department / Section', align: 'left', field: 'dept', sortable: true },
  {
    name: 'resolved_count',
    label: 'Resolved Tickets',
    align: 'center',
    field: 'resolved_count',
    sortable: true,
  },
  { name: 'rating', label: 'Average Rating', align: 'left', field: 'avg_rating', sortable: true },
]

// Category Pagination
const categoryPage = ref(1)
const categoryPerPage = ref(4)

const totalStatusTickets = computed(() => {
  return (ticketsByStatus.value || []).reduce((acc, curr) => acc + (curr.count || 0), 0)
})

function getStatusPercentage(count) {
  if (!totalStatusTickets.value) return 0
  return Math.round((count / totalStatusTickets.value) * 100)
}

const colorMap = {
  orange: '#f97316',
  yellow: '#eab308',
  green: '#006836',
  grey: '#9ca3af',
}

const pieChartStyle = computed(() => {
  const total = totalStatusTickets.value
  if (!total) {
    return 'conic-gradient(#e5e7eb 0deg 360deg)'
  }

  let currentDeg = 0
  const stops = []

  const list = ticketsByStatus.value || []
  list.forEach((item) => {
    const hex = colorMap[item.color] || '#9ca3af'
    const pct = item.count / total
    const deg = pct * 360
    const nextDeg = currentDeg + deg
    stops.push(`${hex} ${currentDeg}deg ${nextDeg}deg`)
    currentDeg = nextDeg
  })

  return `conic-gradient(${stops.join(', ')})`
})

const maxCategoryPages = computed(() => {
  return Math.ceil((ticketsByCategory.value?.length || 0) / categoryPerPage.value) || 1
})

const paginatedCategories = computed(() => {
  const start = (categoryPage.value - 1) * categoryPerPage.value
  const end = start + categoryPerPage.value
  return (ticketsByCategory.value || []).slice(start, end)
})

async function fetchAnalytics() {
  loading.value = true
  try {
    const res = await api.get('/reports/analytics', {
      params: { period: activePeriod.value },
    })
    const data = res.data
    kpiCards.value = data.kpis || []
    ticketsByStatus.value = data.tickets_by_status || []
    ticketsByCategory.value = data.tickets_by_category || []
    resolutionTimes.value = data.resolution_times || []
    topRequesters.value = data.top_requesters || []
    monthlyData.value = data.monthly_data || []
    topStaff.value = data.top_staff || []
    categoryPage.value = 1 // reset pagination page on refetch
  } catch (err) {
    console.error('Failed to load analytics report', err)
    $q.notify({ type: 'negative', message: 'Failed to load report analytics data.' })
  } finally {
    loading.value = false
  }
}

function changePeriod(val) {
  activePeriod.value = val
  fetchAnalytics()
}

onMounted(fetchAnalytics)
</script>

<style lang="scss" scoped></style>
