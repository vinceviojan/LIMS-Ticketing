<template>
  <q-layout view="hHh Lpr lFf">

    <!-- ── Header ───────────────────────────────────────── -->
    <AppHeader />

    <!-- ── Sidebar ──────────────────────────────────────── -->
    <q-drawer
      v-model="drawerOpen"
      show-if-above
      :width="220"
      :breakpoint="768"
      class="staff-sidebar"
    >
      <q-scroll-area class="fit">

        <!-- ── Sidebar Logos ───────────────────────────── -->
        <div class="staff-sidebar__logos">

          <!-- BSWM Logo -->
          <img
            src="../assets/bswm-logo.png"
            alt="BSWM Logo"
            class="staff-sidebar__logo-image"
          />

          <!-- LIMS Logo -->
          <img
            src="../assets/bswm-logo-sidebar.png"
            alt="LSD Logo"
            class="staff-sidebar__logo-image"
          />

        </div>

        <!-- ── Sidebar Title ───────────────────────────── -->
        <div class="staff-sidebar__title">

          <q-icon
            name="support_agent"
            size="22px"
            class="staff-sidebar__logo-icon"
          />

          <span>Staff Panel</span>

        </div>

        <!-- ── Divider ─────────────────────────────────── -->
        <q-separator class="q-my-sm" />

        <!-- ── Navigation ──────────────────────────────── -->
        <q-list
          padding
          class="staff-sidebar__nav"
        >

          <q-item
            v-for="item in navItems"
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


// ─────────────────────────────────────────────────────────
// Authentication Store
// ─────────────────────────────────────────────────────────

const authStore = useAuthStore()

provide('authStore', authStore)


// ─────────────────────────────────────────────────────────
// Sidebar State
// ─────────────────────────────────────────────────────────

const drawerOpen = ref(true)


// ─────────────────────────────────────────────────────────
// Sidebar Navigation
// ─────────────────────────────────────────────────────────

const navItems = [
  {
    label: 'Dashboard',
    icon: 'dashboard',
    to: '/staff/dashboard'
  },

  {
    label: 'My Tickets',
    icon: 'confirmation_number',
    to: '/staff/ticket-management'
  },

  {
    label: 'Accomplishments',
    icon: 'summarize',
    to: '/staff/accomplishment'
  },

  {
    label: 'Settings',
    icon: 'settings',
    to: '/staff/settings'
  }
]
</script>

<style lang="scss">
@import './StaffLayout.scss';
</style>
