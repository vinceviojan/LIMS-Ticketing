<template>
  <q-layout view="hHh Lpr lFf">
    <AppHeader />

    <q-drawer
      v-model="drawerOpen"
      show-if-above
      :width="220"
      :breakpoint="768"
      class="staff-sidebar"
    >
      <q-scroll-area class="fit">
        <div class="staff-sidebar__logo">
          <q-icon name="support_agent" size="20px" class="staff-sidebar__logo-icon" />
          <span>Staff Portal</span>
        </div>

        <q-separator class="q-my-sm" />

        <q-list padding class="staff-sidebar__nav">
          <q-item
            v-for="item in navItems"
            :key="item.name"
            clickable
            v-ripple
            :to="item.to"
            active-class="staff-sidebar__item--active"
            class="staff-sidebar__item"
            exact-active-class="staff-sidebar__item--active"
          >
            <q-item-section avatar>
              <q-icon :name="item.icon" size="20px" />
            </q-item-section>
            <q-item-section>{{ item.label }}</q-item-section>
          </q-item>
        </q-list>
      </q-scroll-area>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, provide } from 'vue'
import { useAuthStore } from '../stores/auth'
import AppHeader from '../components/AppHeader.vue'

const authStore = useAuthStore()
provide('authStore', authStore)


const drawerOpen = ref(true)

const navItems = [
  { label: 'Dashboard', icon: 'dashboard',             to: '/staff/dashboard' },
  { label: 'My Tickets', icon: 'confirmation_number',  to: '/staff/ticket-management'   },
  { label: 'Settings', icon: 'settings', to: '/staff/settings' },
]
</script>

<style lang="scss" scoped>
@import '../css/themes.scss';

.staff-sidebar {
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
    color: $accent-signup;
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
      background: #fff7ed !important;
      color: $accent-signup !important;
      font-weight: 600;
      border-left: 3px solid $accent-signup;
      border-radius: 0 8px 8px 0;

      :deep(.q-icon) {
        color: $accent-signup !important;
      }
    }
  }
}
</style>
