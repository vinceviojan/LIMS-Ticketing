<template>
  <q-layout view="hHh Lpr lFf">
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { provide } from 'vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
provide('authStore', authStore)
</script>

<style scoped>
.q-page-container {
  background-color: #ffffff;
  min-height: 100vh;
  padding: 0 !important;
}
</style>
