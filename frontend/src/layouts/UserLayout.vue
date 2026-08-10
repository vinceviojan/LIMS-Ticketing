<template>
  <q-layout view="hHh Lpr lFf">
    <AppHeader />

    <q-drawer
      v-model="drawerOpen"
      show-if-above
      :width="220"
      :breakpoint="768"
      class="user-sidebar"
    >
      <q-scroll-area class="fit">
        <div class="user-sidebar__logo">
          <q-icon name="person_outline" size="20px" class="user-sidebar__logo-icon" />
          <span>My Portal</span>
        </div>

        <q-separator class="q-my-sm" />

        <q-list padding class="user-sidebar__nav">
          <q-item
            v-for="item in navItems"
            :key="item.name"
            clickable
            v-ripple
            :to="item.to"
            active-class="user-sidebar__item--active"
            class="user-sidebar__item"
            exact-active-class="user-sidebar__item--active"
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
import { ref } from 'vue'
import AppHeader from '../components/AppHeader.vue'

const drawerOpen = ref(true)

const navItems = [
  { label: 'Dashboard',    icon: 'home',                 to: '/user/dashboard' },
  { label: 'My Tickets',   icon: 'confirmation_number',  to: '/user/ticket-management'   },
]
</script>

<style lang="scss" scoped>
@import '../css/themes.scss';

.user-sidebar {
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
    color: #7c6bbf;
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
      background: #faf5ff !important;
      color: #7c6bbf !important;
      font-weight: 600;
      border-left: 3px solid #7c6bbf;
      border-radius: 0 8px 8px 0;

      :deep(.q-icon) {
        color: #7c6bbf !important;
      }
    }
  }
}
</style>
