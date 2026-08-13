<template>
  <q-layout view="hHh Lpr lFf">
    <!-- ── Header ───────────────────────────────────────── -->
    <AppHeader @toggle-drawer="toggleSidebar" />

    <!-- ── Sidebar ──────────────────────────────────────── -->
    <q-drawer
      v-model="drawerOpen"
      show-if-above
      :mini="miniState"
      :width="220"
      :breakpoint="768"
      class="staff-sidebar"
    >
      <q-scroll-area class="fit staff-sidebar__scroll">
        <!-- ── Sidebar Logos ───────────────────────────── -->
        <div class="staff-sidebar__logos" v-show="!miniState">
          <!-- BSWM Logo -->
          <img src="../assets/bswm-logo.png" alt="BSWM Logo" class="staff-sidebar__logo-image" />

          <!-- LIMS Logo -->
          <img src="../assets/75th_LOGO.png" alt="LSD Logo" class="staff-sidebar__logo-image" />
        </div>

        <!-- ── Divider ─────────────────────────────────── -->
        <q-separator class="q-my-sm" />

        <!-- ── Navigation ──────────────────────────────── -->
        <q-list padding class="staff-sidebar__nav">
          <template v-for="group in navGroups" :key="group.heading">
            <!-- Section Header -->
            <q-item-label v-show="!miniState" header class="staff-sidebar__section-header">
              {{ group.heading }}
            </q-item-label>

            <!-- Nav Items -->
            <q-item
              v-for="item in group.items"
              :key="item.label"
              clickable
              v-ripple
              :to="item.to"
              class="staff-sidebar__item"
              active-class="staff-sidebar__item--active"
              exact-active-class="staff-sidebar__item--active"
            >
              <!-- Icon -->
              <q-item-section avatar>
                <q-icon :name="item.icon" size="20px" />
                <q-tooltip
                  v-if="miniState"
                  anchor="center right"
                  self="center left"
                  :offset="[8, 0]"
                >
                  {{ item.label }}
                </q-tooltip>
              </q-item-section>

              <!-- Label -->
              <q-item-section class="staff-sidebar__item-label">
                {{ item.label }}
              </q-item-section>
            </q-item>
          </template>
        </q-list>
      </q-scroll-area>
    </q-drawer>

    <!-- ── Page Content ─────────────────────────────────── -->
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, provide } from 'vue'
import { useAuthStore } from '../stores/auth'
import AppHeader from '../components/AppHeader.vue'
import { useQuasar } from 'quasar'

// ─────────────────────────────────────────────────────────
// Authentication Store
// ─────────────────────────────────────────────────────────

const authStore = useAuthStore()
const $q = useQuasar()

provide('authStore', authStore)

// ─────────────────────────────────────────────────────────
// Sidebar State
// ─────────────────────────────────────────────────────────

const drawerOpen = ref(true)
const miniState = ref(false)

const toggleSidebar = () => {
  if ($q.screen.width < 768) {
    drawerOpen.value = !drawerOpen.value
    miniState.value = false
  } else {
    miniState.value = !miniState.value
  }
}

// ─────────────────────────────────────────────────────────
// Sidebar Navigation — Grouped
// ─────────────────────────────────────────────────────────

const navGroups = [
  {
    heading: 'Core Modules',
    items: [
      { label: 'Dashboard', icon: 'dashboard', to: '/staff/dashboard' },
      { label: 'My Tickets', icon: 'confirmation_number', to: '/staff/ticket-management' },
      { label: 'Accomplishments', icon: 'summarize', to: '/staff/accomplishment' },
    ],
  },
  {
    heading: 'System',
    items: [{ label: 'Settings', icon: 'settings', to: '/staff/settings' }],
  },
]
</script>

<style lang="scss">
@import './StaffLayout.scss';
</style>
