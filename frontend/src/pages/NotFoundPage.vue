<template>
  <div class="error-page">
    <div class="error-page__content">

      <span class="error-page__code">404</span>

      <div class="error-page__rule" />

      <h1 class="error-page__title">Page not found</h1>
      <p class="error-page__text">
        The page you're looking for doesn't exist or may have been moved.
      </p>

      <div class="error-page__actions">
        <button class="error-page__btn error-page__btn--ghost" @click="$router.back()">
          Go back
        </button>
        <button class="error-page__btn error-page__btn--solid" @click="goHome">
          Return home
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { inject } from 'vue'

const router = useRouter()
const authStore = inject('authStore', null)

const goHome = () => {
  const role = authStore?.userRole || (JSON.parse(localStorage.getItem('user') || '{}')?.role || '').toLowerCase()
  if (role === 'admin') return router.push('/admin/dashboard')
  if (role === 'staff') return router.push('/staff/dashboard')
  if (role === 'user') return router.push('/user/dashboard')
  router.push('/login')
}
</script>

<style lang="scss" scoped>
.error-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fafafa;
  padding: 24px;
}

.error-page__content {
  width: 100%;
  max-width: 380px;
  text-align: center;
}

.error-page__code {
  display: block;
  font-family: 'JetBrains Mono', 'IBM Plex Mono', 'SFMono-Regular', ui-monospace, monospace;
  font-size: 15px;
  font-weight: 500;
  letter-spacing: 0.12em;
  color: #71717a;
}

.error-page__rule {
  width: 32px;
  height: 1px;
  background: #d4d4d8;
  margin: 20px auto;
}

.error-page__title {
  font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif;
  font-size: 1.5rem;
  font-weight: 600;
  letter-spacing: -0.01em;
  color: #18181b;
  margin: 0 0 12px;
}

.error-page__text {
  font-size: 0.9rem;
  line-height: 1.6;
  color: #71717a;
  margin: 0 0 32px;
}

.error-page__actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.error-page__btn {
  font-family: inherit;
  font-size: 0.85rem;
  font-weight: 500;
  padding: 9px 20px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.error-page__btn--ghost {
  background: transparent;
  border: 1px solid #e4e4e7;
  color: #52525b;

  &:hover {
    border-color: #a1a1aa;
    color: #18181b;
  }
}

.error-page__btn--solid {
  background: #18181b;
  border: 1px solid #18181b;
  color: #fafafa;

  &:hover {
    background: #27272a;
  }
}

@media (max-width: 420px) {
  .error-page__actions {
    flex-direction: column;
  }
}
</style>