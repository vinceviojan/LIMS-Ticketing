<template>
  <div class="clay-page">
    <div class="clay-card">
      
      <!-- Lock Icon -->
      <div class="forbidden-icon-wrapper">
        <q-icon name="lock" class="forbidden-icon" />
      </div>

      <h1 class="clay-title text-center">403</h1>
      <h2 class="forbidden-subtitle">Access Forbidden</h2>
      
      <p class="forbidden-text">
        You do not have the required permissions to view this page. If you believe this is an error, please contact your administrator.
      </p>

      <div class="clay-actions">
        <q-btn 
          class="clay-btn clay-btn--login" 
          unelevated 
          label="Go Back" 
          @click="$router.back()" 
        />
        <q-btn 
          class="clay-btn clay-btn--home" 
          unelevated 
          label="Return Home" 
          @click="goHome" 
        />
      </div>

    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const goHome = () => {
    const role = authStore.userRole
    if (role === 'admin') return router.push('/admin/dashboard')
    if (role === 'staff') return router.push('/staff/dashboard')
    if (role === 'user') return router.push('/user/dashboard')
    router.push('/login')
}
</script>

<style lang="scss" scoped>
@import './Auth/LoginPage.scss';

.forbidden-icon-wrapper {
  margin-bottom: -10px;
}

.forbidden-icon {
  font-size: 64px;
  color: #ef4444;
}

.clay-title {
  margin: 0;
  padding: 10px 32px;
  color: #ef4444;
}

.forbidden-subtitle {
  font-family: 'Nunito', 'Quicksand', sans-serif;
  font-weight: 800;
  font-size: 1.4rem;
  color: $min-text;
  margin: 0;
}

.forbidden-text {
  text-align: center;
  color: $min-text-soft;
  font-size: 0.95rem;
  line-height: 1.5;
  margin: 0 0 10px 0;
}

.clay-btn--home {
  @include min-button($min-text-soft);
}
</style>