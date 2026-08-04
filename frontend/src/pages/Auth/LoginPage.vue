<template>
  <div class="clay-page">
    <div class="clay-card">
      <h1 class="clay-title">Login</h1>

      <div class="clay-actions">
        <q-btn class="clay-btn clay-btn--login" unelevated label="Login" @click="login" />
        <q-btn class="clay-btn clay-btn--signup" unelevated label="Signup" @click="signup" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const login = () => {
  router.push('/login')
}

const signup = () => {
  router.push('/signup')
}
</script>

<style lang="scss" scoped>
@import './LoginPage.scss';
</style>