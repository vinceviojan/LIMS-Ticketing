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
      class="user-sidebar"
    >
      <q-scroll-area class="fit">

        <!-- ── Sidebar Logos ───────────────────────────── -->
        <div class="user-sidebar__logos" v-show="!miniState">

          <!-- BSWM Logo -->
          <img
            src="../assets/bswm-logo.png"
            alt="BSWM Logo"
            class="user-sidebar__logo-image"
          />

          <!-- LIMS Logo -->
          <img
            src="../assets/bswm-logo-sidebar.png"
            alt="LSD Logo"
            class="user-sidebar__logo-image"
          />

        </div>

        <!-- ── Sidebar Title ─────────────────────────────
        <div class="user-sidebar__title">

          <q-icon
            name="person_outline"
            size="22px"
            class="user-sidebar__logo-icon"
          />

          <span>User Portal</span>

        </div> -->

        <!-- ── Divider ─────────────────────────────────── -->
        <q-separator class="q-my-sm" />

        <!-- ── Navigation ──────────────────────────────── -->
        <q-list
          padding
          class="user-sidebar__nav"
        >

          <q-item
            v-for="item in navItems"
            :key="item.label"
            clickable
            v-ripple
            :to="item.to"
            class="user-sidebar__item"
            active-class="user-sidebar__item--active"
            exact-active-class="user-sidebar__item--active"
          >

            <!-- Icon -->
            <q-item-section avatar>
              <q-icon
                :name="item.icon"
                size="20px"
              />
            </q-item-section>

            <!-- Label -->
            <q-item-section>
              {{ item.label }}
            </q-item-section>

          </q-item>

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
// Sidebar Navigation
// ─────────────────────────────────────────────────────────

const navItems = [
  {
    label: 'Dashboard',
    icon: 'home',
    to: '/user/dashboard'
  },

  {
    label: 'My Tickets',
    icon: 'confirmation_number',
    to: '/user/ticket-management'
  },

  {
    label: 'Settings',
    icon: 'settings',
    to: '/user/settings'
  }
]
</script>

<style lang="scss">
@import './UserLayout.scss';
</style>
