<template>
  <q-layout view="hHh Lpr lFf">
    <AppHeader />

    <!-- ── Sidebar ────────────────────────────────────────── -->
    <q-drawer
      v-model="drawerOpen"
      show-if-above
      :width="220"
      :breakpoint="768"
      class="admin-sidebar"
    >
      <q-scroll-area class="fit">
        <div class="admin-sidebar__logo">
          <q-icon name="science" size="22px" class="admin-sidebar__logo-icon" />
          <span>Admin Panel</span>
        </div>

        <q-separator class="q-my-sm" />

        <q-list padding class="admin-sidebar__nav">
          <q-item
            v-for="item in navItems"
            :key="item.name"
            clickable
            v-ripple
            :to="item.to"
            active-class="admin-sidebar__item--active"
            class="admin-sidebar__item"
            exact-active-class="admin-sidebar__item--active"
          >
            <q-item-section avatar>
              <q-icon :name="item.icon" size="20px" />
            </q-item-section>
            <q-item-section>{{ item.label }}</q-item-section>
          </q-item>
        </q-list>
      </q-scroll-area>
    </q-drawer>

    <!-- ── Page Content ───────────────────────────────────── -->
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref } from 'vue'
import AppHeader from '../components/AppHeader.vue'

const drawerOpen = ref(true)

const navItems = [
  { label: 'Dashboard',           icon: 'dashboard',            to: '/admin/dashboard'           },
  { label: 'User Management',     icon: 'manage_accounts',      to: '/admin/users'               },
  { label: 'Tickets',             icon: 'confirmation_number',  to: '/admin/ticket-management'   },
  { label: 'Problem Categories',  icon: 'category',             to: '/admin/problem-categories'  },
  { label: 'Reports',             icon: 'bar_chart',            to: '/admin/reports'             },
  { label: 'Logs',                icon: 'history',              to: '/admin/logs'                },
  { label: 'Settings',            icon: 'settings',             to: '/admin/settings'            },
]
</script>

<style lang="scss" scoped>
@import '../css/themes.scss';

.admin-sidebar {
  background: $min-surface !important;
  border-right: 1px solid $min-border !important;

  &__logo {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px 20px 12px;
    font-family: 'Nunito', 'Quicksand', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    color: $min-text;
  }

  &__logo-icon {
    color: $accent-login;
  }

  &__nav {
    padding: 4px 8px;
  }

  &__item {
    border-radius: 8px;
    margin-bottom: 2px;
    color: $min-text-soft;
    font-size: 0.88rem;
    font-weight: 500;
    transition: background 0.15s ease, color 0.15s ease;

    &:hover {
      background: $min-bg;
      color: $min-text;
    }

    &--active {
      background: #f0fdf4 !important;
      color: $accent-login !important;
      font-weight: 600;
      border-left: 3px solid $accent-login;
      border-radius: 0 8px 8px 0;

      :deep(.q-icon) {
        color: $accent-login !important;
      }
    }
  }
}
</style>
