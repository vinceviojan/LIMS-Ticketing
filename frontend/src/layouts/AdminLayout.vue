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
      class="admin-sidebar"
    >
      <q-scroll-area class="fit">

        <!-- ── Sidebar Logos ───────────────────────────── -->
        <div class="admin-sidebar__logos">

          <!-- BSWM Logo -->
          <img
            src="../assets/bswm-logo.png"
            alt="BSWM Logo"
            class="admin-sidebar__logo-image"
          />

          <!-- LIMS Logo -->
          <img
            src="../assets/bswm-logo-sidebar.png"
            alt="LSD Logo"
            class="admin-sidebar__logo-image"
          />

        </div>

        <!-- ── Sidebar Title ─────────────────────────────
        <div class="admin-sidebar__title">

          <q-icon
            name="science"
            size="22px"
            class="admin-sidebar__logo-icon"
          />

          <span>Admin Panel</span>

        </div> -->

        <!-- ── Divider ─────────────────────────────────── -->
        <q-separator class="q-my-sm" />

        <!-- ── Navigation ──────────────────────────────── -->
        <q-list
          padding
          class="admin-sidebar__nav"
        >

          <q-item
            v-for="item in navItems"
            :key="item.label"
            clickable
            v-ripple
            :to="item.to"
            class="admin-sidebar__item"
            active-class="admin-sidebar__item--active"
            exact-active-class="admin-sidebar__item--active"
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
    to: '/admin/dashboard'
  },

  {
    label: 'User Management',
    icon: 'manage_accounts',
    to: '/admin/users'
  },

  {
    label: 'Organization',
    icon: 'account_tree',
    to: '/admin/organization'
  },

  {
    label: 'Tickets',
    icon: 'confirmation_number',
    to: '/admin/ticket-management'
  },

  {
    label: 'Problem Categories',
    icon: 'category',
    to: '/admin/problem-categories'
  },

  {
    label: 'Reports',
    icon: 'bar_chart',
    to: '/admin/reports'
  },

  {
    label: 'Logs',
    icon: 'history',
    to: '/admin/logs'
  },

  {
    label: 'Settings',
    icon: 'settings',
    to: '/admin/settings'
  }
]
</script>

<style lang="scss">
@import './AdminLayout.scss';
</style>

