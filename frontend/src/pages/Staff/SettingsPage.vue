<template>
  <q-page class="settings-page">
    <div class="settings-page__header">
      <div>
        <div class="text-h5 settings-page__title">Settings</div>
        <div class="settings-page__subtitle">Manage your profile and system configuration</div>
      </div>
    </div>
    <div class="settings-page__layout">
      <div class="settings-nav">
        <button
          v-for="tab in settingsTabs"
          :key="tab.value"
          :class="['settings-nav__item', activeTab === tab.value ? 'settings-nav__item--active' : '']"
          @click="activeTab = tab.value"
        >
          <q-icon :name="tab.icon" size="18px" />
          {{ tab.label }}
        </button>
      </div>
      <div class="settings-panel">
        <div v-if="activeTab === 'profile'">
          <div class="settings-panel__section-title">My Profile</div>
          <div class="settings-panel__card">
            <div class="settings-profile">
              <div class="settings-profile__avatar">
                {{ initials }}
              </div>
              <div>
                <div class="settings-profile__name">{{ authStore.userName }}</div>
                <div class="settings-profile__role">{{ authStore.user?.role }}</div>
                <div class="settings-profile__email">{{ authStore.user?.email }}</div>
              </div>
            </div>
          </div>

          <div class="settings-panel__section-title">Edit Profile</div>
          <div class="settings-panel__card">
            <div class="settings-form">
              <div class="settings-form__row">
                <q-input v-model="profileForm.first_name" label="First Name" outlined dense />
                <q-input v-model="profileForm.last_name"  label="Last Name"  outlined dense />
              </div>
              <q-input v-model="profileForm.email" label="Email Address" outlined dense type="email" class="q-mt-sm" />
              <div class="settings-form__row q-mt-sm">
                <q-input v-model="profileForm.division"  label="Division"  outlined dense />
                <q-input v-model="profileForm.sections"  label="Sections"  outlined dense />
              </div>
              <q-input v-model="profileForm.position" label="Position" outlined dense class="q-mt-sm" />
              <div class="settings-form__actions">
                <q-btn class="clay-btn clay-btn--primary" label="Save Profile" icon="save" unelevated no-caps @click="saveProfile" />
              </div>
            </div>
          </div>
        </div>

        <!-- Security Tab -->
        <div v-if="activeTab === 'security'">
          <div class="settings-panel__section-title">Change Password</div>
          <div class="settings-panel__card">
            <div class="settings-form">
              <q-input v-model="passwordForm.current" label="Current Password" outlined dense type="password" />
              <q-input v-model="passwordForm.new"     label="New Password"     outlined dense type="password" class="q-mt-sm" />
              <q-input v-model="passwordForm.confirm" label="Confirm New Password" outlined dense type="password" class="q-mt-sm" />
              <div class="settings-form__actions">
                <q-btn class="clay-btn clay-btn--primary" label="Update Password" icon="lock" unelevated no-caps @click="savePassword" />
              </div>
            </div>
          </div>

          <div class="settings-panel__section-title">Sessions</div>
          <div class="settings-panel__card">
            <div v-for="session in sessions" :key="session.id" class="session-row">
              <q-icon :name="session.device === 'Desktop' ? 'computer' : 'smartphone'" size="20px" color="primary" />
              <div class="session-row__info">
                <div class="session-row__device">{{ session.device }} — {{ session.browser }}</div>
                <div class="session-row__meta">{{ session.ip }} · {{ session.time }}</div>
              </div>
              <q-badge v-if="session.current" color="positive" label="Current" />
              <q-btn v-else flat no-caps dense label="Revoke" color="negative" size="sm" />
            </div>
          </div>
        </div>

        <!-- System Tab -->
        <div v-if="activeTab === 'system'">
          <div class="settings-panel__section-title">System Configuration</div>
          <div class="settings-panel__card">
            <div class="settings-toggle-row">
              <div>
                <div class="settings-toggle-row__label">Maintenance Mode</div>
                <div class="settings-toggle-row__desc">Disable access for all non-admin users</div>
              </div>
              <q-toggle v-model="systemConfig.maintenance" color="primary" />
            </div>
            <q-separator class="q-my-sm" />
            <div class="settings-toggle-row">
              <div>
                <div class="settings-toggle-row__label">Email Notifications</div>
                <div class="settings-toggle-row__desc">Send email alerts for new tickets</div>
              </div>
              <q-toggle v-model="systemConfig.emailNotifs" color="primary" />
            </div>
            <q-separator class="q-my-sm" />
            <div class="settings-toggle-row">
              <div>
                <div class="settings-toggle-row__label">Auto-close Resolved Tickets</div>
                <div class="settings-toggle-row__desc">Automatically close tickets after 7 days of resolution</div>
              </div>
              <q-toggle v-model="systemConfig.autoClose" color="primary" />
            </div>
            <q-separator class="q-my-sm" />
            <div class="settings-toggle-row">
              <div>
                <div class="settings-toggle-row__label">Audit Logging</div>
                <div class="settings-toggle-row__desc">Log all create, update, delete actions</div>
              </div>
              <q-toggle v-model="systemConfig.auditLog" color="primary" />
            </div>
          </div>

          <div class="settings-panel__section-title">Ticket SLA</div>
          <div class="settings-panel__card">
            <div class="settings-form">
              <div class="settings-form__row">
                <q-input v-model.number="slaConfig.critical" label="Critical SLA (hours)" outlined dense type="number" />
                <q-input v-model.number="slaConfig.high"     label="High SLA (hours)"     outlined dense type="number" />
              </div>
              <div class="settings-form__row q-mt-sm">
                <q-input v-model.number="slaConfig.medium" label="Medium SLA (hours)" outlined dense type="number" />
                <q-input v-model.number="slaConfig.low"    label="Low SLA (hours)"    outlined dense type="number" />
              </div>
              <div class="settings-form__actions">
                <q-btn class="clay-btn clay-btn--primary" label="Save Configuration" icon="settings" unelevated no-caps @click="saveSystem" />
              </div>
            </div>
          </div>
        </div>

        <!-- About Tab -->
        <div v-if="activeTab === 'about'">
          <div class="settings-panel__section-title">About this System</div>
          <div class="settings-panel__card settings-about">
            <q-icon name="science" size="52px" color="primary" />
            <div class="settings-about__name">LIMS Support Ticketing System</div>
            <div class="settings-about__org">Bureau of Soils and Water Management – LSD</div>
            <div class="settings-about__version">Version 1.0.0 — August 2026</div>
            <q-separator class="q-my-md" style="width:100%" />
            <div class="settings-about__tech">
              <span>Laravel 12</span>
              <span>Quasar 2 (Vue 3)</span>
              <span>MySQL</span>
              <span>Laravel Sanctum</span>
            </div>
          </div>
        </div>

      </div>
    </div>

  </q-page>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { useAuthStore } from '../../stores/auth'
import './SettingsPage.scss'

const $q = useQuasar()
const authStore = useAuthStore()

const activeTab = ref('profile')

const settingsTabs = [
  { label: 'Profile',  value: 'profile',  icon: 'person'       },
  { label: 'Security', value: 'security', icon: 'lock'          },
  { label: 'About',    value: 'about',    icon: 'info_outline'  },
]

const initials = computed(() => {
  const name = authStore.userName ?? ''
  return name.split(' ').map(n => n[0]?.toUpperCase()).slice(0, 2).join('')
})

const profileForm = ref({
  first_name: authStore.user?.first_name ?? '',
  last_name:  authStore.user?.last_name  ?? '',
  email:      authStore.user?.email      ?? '',
  division:   authStore.user?.division   ?? '',
  sections:   authStore.user?.sections   ?? '',
  position:   authStore.user?.position   ?? '',
})

const passwordForm = ref({ current: '', new: '', confirm: '' })

const systemConfig = ref({
  maintenance: false,
  emailNotifs: true,
  autoClose: true,
  auditLog: true,
})

const slaConfig = ref({ critical: 2, high: 8, medium: 24, low: 72 })

const sessions = ref([
  { id: 1, device: 'Desktop', browser: 'Chrome 126', ip: '192.168.1.5',  time: 'Active now',    current: true  },
  { id: 2, device: 'Mobile',  browser: 'Safari 17',  ip: '192.168.1.20', time: '2 hours ago',   current: false },
  { id: 3, device: 'Desktop', browser: 'Firefox 128',ip: '192.168.1.5',  time: 'Yesterday',     current: false },
])

function notify(type, message) {
  $q.notify({ type, message, position: 'top-right', timeout: 2000 })
}

function saveProfile() {
  notify('positive', 'Profile saved successfully.')
}

function savePassword() {
  if (passwordForm.value.new !== passwordForm.value.confirm) {
    notify('negative', 'Passwords do not match.')
    return
  }
  notify('positive', 'Password updated successfully.')
  passwordForm.value = { current: '', new: '', confirm: '' }
}

function saveSystem() {
  notify('positive', 'System configuration saved.')
}
</script>

<style lang="scss" scoped>
</style>
