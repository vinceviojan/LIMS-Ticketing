<template>
  <q-header class="app-header" elevated>
    <q-toolbar class="app-header__toolbar">

      <!-- ── Brand / Logo ─────────────────────────────── -->
      <div class="app-header__brand">
        <q-icon name="science" class="app-header__logo-icon" />
        <span class="app-header__title">LIMS Ticketing</span>
      </div>

      <q-space />

      <!-- ── Role Badge ────────────────────────────────── -->
      <div class="app-header__badge" :class="`app-header__badge--${authStore.userRole}`">
        {{ rolLabel }}
      </div>

      <!-- ── User Info + Dropdown ──────────────────────── -->
      <q-btn flat no-caps class="app-header__user-btn" id="header-user-menu">
        <span class="app-header__user-name">{{ authStore.userName }}</span>
        <q-icon name="arrow_drop_down" />

        <q-menu
          anchor="bottom right"
          self="top right"
          class="app-header__menu"
          transition-show="jump-down"
          transition-hide="jump-up"
        >
          <q-list>
            <!-- User info row inside menu -->
            <div class="app-header__menu-info">
              <div class="app-header__menu-avatar">
                {{ initials }}
              </div>
              <div>
                <p class="app-header__menu-name">{{ authStore.userName }}</p>
                <p class="app-header__menu-email">{{ authStore.user?.email }}</p>
              </div>
            </div>

            <q-separator class="q-my-sm" />

            <q-item
              clickable
              v-close-popup
              class="app-header__menu-item app-header__menu-item--logout"
              id="header-menu-logout"
              @click="handleLogout"
              :disable="loggingOut"
            >
              <q-item-section avatar>
                <q-icon name="logout" size="18px" />
              </q-item-section>
              <q-item-section>
                {{ loggingOut ? 'Logging out…' : 'Logout' }}
              </q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>

    </q-toolbar>

    <!-- ── Logout Confirm Dialog ─────────────────────── -->
    <q-dialog v-model="showConfirm" persistent>
      <q-card class="app-header__dialog">
        <q-card-section class="app-header__dialog-head">
          <q-icon name="logout" size="32px" color="negative" />
          <span class="app-header__dialog-title">Confirm Logout</span>
        </q-card-section>

        <q-card-section class="app-header__dialog-body">
          Are you sure you want to log out? Your session will be ended.
        </q-card-section>

        <q-card-actions align="right" class="app-header__dialog-actions">
          <q-btn
            flat
            no-caps
            label="Cancel"
            class="app-header__dialog-cancel"
            v-close-popup
          />
          <q-btn
            unelevated
            no-caps
            label="Yes, Logout"
            class="app-header__dialog-confirm"
            :loading="loggingOut"
            @click="confirmLogout"
            id="confirm-logout-btn"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-header>
</template>

<script setup>
import { ref, computed, inject } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const authStore = inject('authStore') // Using injected auth store

const showConfirm = ref(false)
const loggingOut = ref(false)

// Role display label
const rolLabel = computed(() => {
  const map = { admin: 'Admin', staff: 'Staff', user: 'User' }
  return map[authStore.userRole] ?? authStore.userRole
})

// User initials for avatar
const initials = computed(() => {
  const name = authStore.userName || ''
  return name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? '')
    .join('')
})

// Opens the confirmation dialog
const handleLogout = () => {
  showConfirm.value = true
}

// Executes the logout after confirmation
const confirmLogout = async () => {
  loggingOut.value = true
  try {
    await authStore.logout()
    router.push('/login')
  } finally {
    loggingOut.value = false
    showConfirm.value = false
  }
}
</script>

<style lang="scss" scoped>
@import './AppHeader.scss';
</style>
