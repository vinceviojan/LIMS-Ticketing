<template>
  <q-page class="logs-page">

    <!-- ── Header ──────────────────────────────────────────────── -->
    <div class="logs-page__header">
      <div>
        <div class="text-h5 logs-page__title">System Logs</div>
        <div class="logs-page__subtitle">Audit trail of all system activity and events</div>
      </div>
      <q-btn
        class="clay-btn clay-btn--primary"
        icon="download"
        label="Export Logs"
        unelevated no-caps
        @click="exportLogs"
      />
    </div>

    <!-- ── Toolbar ─────────────────────────────────────────────── -->
    <div class="logs-page__toolbar">
      <q-input
        v-model="search"
        dense outlined clearable
        placeholder="Search logs..."
        class="logs-page__search"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>

      <q-select
        v-model="filterLevel"
        :options="levelOptions"
        label="Level"
        dense outlined clearable emit-value map-options
        class="logs-page__filter"
      />

      <q-select
        v-model="filterAction"
        :options="actionOptions"
        label="Action"
        dense outlined clearable emit-value map-options
        class="logs-page__filter"
      />

      <div class="logs-page__stat-chip">
        <q-icon name="format_list_bulleted" size="15px" />
        <span>{{ filteredLogs.length }} entries</span>
      </div>
    </div>

    <!-- ── Log Table ───────────────────────────────────────────── -->
    <div class="clay-log-table">
      <div class="clay-log-table__head">
        <span style="width:90px">Timestamp</span>
        <span style="width:72px">Level</span>
        <span style="width:80px">Action</span>
        <span style="width:160px">User</span>
        <span style="flex:1">Message</span>
        <span style="width:100px">IP Address</span>
      </div>

      <div
        v-for="log in filteredLogs"
        :key="log.id"
        class="clay-log-row"
        :class="`clay-log-row--${log.level}`"
      >
        <span class="clay-log-row__time">{{ log.time }}</span>
        <span :class="['clay-log-row__level', `clay-log-row__level--${log.level}`]">{{ log.level }}</span>
        <span class="clay-log-row__action">{{ log.action }}</span>
        <div class="clay-log-row__user">
          <div class="clay-log-row__avatar">{{ log.user[0] }}</div>
          <span>{{ log.user }}</span>
        </div>
        <span class="clay-log-row__msg">{{ log.message }}</span>
        <span class="clay-log-row__ip">{{ log.ip }}</span>
      </div>

      <div v-if="filteredLogs.length === 0" class="logs-page__empty">
        <q-icon name="inbox" size="48px" color="grey-5" />
        <p>No log entries match your filters</p>
      </div>
    </div>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '../../boot/axios'
import './LogsPage.scss'

const $q = useQuasar()

const search = ref('')
const filterLevel = ref(null)
const filterAction = ref(null)

const levelOptions = [
  { label: 'Info',    value: 'info'    },
  { label: 'Warning', value: 'warning' },
  { label: 'Error',   value: 'error'   },
  { label: 'Success', value: 'success' },
]

const actionOptions = [
  { label: 'Login',   value: 'LOGIN'   },
  { label: 'Logout',  value: 'LOGOUT'  },
  { label: 'Create',  value: 'CREATE'  },
  { label: 'Update',  value: 'UPDATE'  },
  { label: 'Delete',  value: 'DELETE'  },
]

const logs = ref([/*
  { id: 1,  time: '05:12:04', level: 'success', action: 'LOGIN',   user: 'admin@lims.gov.ph',   message: 'Admin user authenticated successfully.',                ip: '192.168.1.5'  },
  { id: 2,  time: '05:14:31', level: 'info',    action: 'CREATE',  user: 'admin@lims.gov.ph',   message: 'New user "juan.delacruz@lims.gov.ph" created.',         ip: '192.168.1.5'  },
  { id: 3,  time: '05:20:17', level: 'success', action: 'UPDATE',  user: 'admin@lims.gov.ph',   message: 'User id:3 status changed to ACTIVE.',                  ip: '192.168.1.5'  },
  { id: 4,  time: '05:41:08', level: 'info',    action: 'LOGIN',   user: 'juan.delacruz',       message: 'Staff user authenticated successfully.',               ip: '192.168.1.11' },
  { id: 5,  time: '06:02:55', level: 'warning', action: 'LOGIN',   user: 'unknown',             message: 'Failed login attempt for email "hacker@test.com".',    ip: '203.44.12.99' },
  { id: 6,  time: '06:23:40', level: 'warning', action: 'LOGIN',   user: 'unknown',             message: 'Failed login attempt – wrong password (3/5).',         ip: '203.44.12.99' },
  { id: 7,  time: '07:00:00', level: 'info',    action: 'CREATE',  user: 'juan.delacruz',       message: 'Ticket #1006 created: "Software installation request".', ip: '192.168.1.11' },
  { id: 8,  time: '07:15:22', level: 'info',    action: 'UPDATE',  user: 'juan.delacruz',       message: 'Ticket #1003 status updated to RESOLVED.',             ip: '192.168.1.11' },
  { id: 9,  time: '07:52:01', level: 'error',   action: 'DELETE',  user: 'admin@lims.gov.ph',   message: 'Failed to delete category id:9: foreign key constraint.', ip: '192.168.1.5' },
  { id: 10, time: '08:04:18', level: 'success', action: 'DELETE',  user: 'admin@lims.gov.ph',   message: 'Problem category "Printer Issue" deleted.',            ip: '192.168.1.5'  },
  { id: 11, time: '08:30:00', level: 'info',    action: 'LOGOUT',  user: 'juan.delacruz',       message: 'User logged out.',                                     ip: '192.168.1.11' },
  { id: 12, time: '08:45:10', level: 'success', action: 'LOGIN',   user: 'maria.santos',        message: 'User authenticated successfully.',                     ip: '192.168.1.14' },
*/])

onMounted(fetchLogs)

async function fetchLogs() {
  try {
    const { data } = await api.get('/logs')
    logs.value = (data.data || data || []).map(log => ({
      id: log.id,
      time: new Date(log.created_at).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'medium' }),
      level: log.action === 'DELETE' ? 'warning' : log.action === 'CREATE' ? 'success' : 'info',
      action: log.action,
      user: log.user?.name || log.user?.email || 'System',
      message: log.message || `${log.action} ticket activity.`,
      ip: log.address || '—',
    }))
  } catch (error) {
    console.error('Failed to load logs', error)
    $q.notify({ type: 'negative', message: 'Failed to load system logs.' })
  }
}

const filteredLogs = computed(() => {
  let data = [...logs.value]
  if (filterLevel.value) data = data.filter(l => l.level === filterLevel.value)
  if (filterAction.value) data = data.filter(l => l.action === filterAction.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    data = data.filter(l =>
      l.message.toLowerCase().includes(q) ||
      l.user.toLowerCase().includes(q) ||
      l.ip.includes(q)
    )
  }
  return data
})

function exportLogs() {
  const rows = ['Timestamp,Level,Action,User,Message,IP Address', ...filteredLogs.value.map(log =>
    [log.time, log.level, log.action, log.user, log.message, log.ip].map(value => `"${String(value).replaceAll('"', '""')}"`).join(',')
  )]
  const url = URL.createObjectURL(new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8' }))
  const link = document.createElement('a')
  link.href = url
  link.download = 'ticket-audit-logs.csv'
  link.click()
  URL.revokeObjectURL(url)
}
</script>
