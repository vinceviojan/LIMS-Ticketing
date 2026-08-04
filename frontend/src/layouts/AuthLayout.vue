<template>
  <q-layout view="hHh Lpr lFf">
    <q-page-container>
      <router-view />
    </q-page-container>
    <Footer />
  </q-layout>
</template>

<script setup>
import Footer from '../components/Footer.vue'
</script>

<style scoped>
.q-page-container {
  background-color: #f4f6f9;
  min-height: 100vh;
}
</style>
