<template>
  <div class="clay-page">
    <div class="clay-card">
      <h1 class="clay-title">Login</h1>

      <!-- Error Alert -->
      <q-banner v-if="authStore.error" class="clay-error" dense rounded>
        <template v-slot:avatar>
          <q-icon name="warning" color="white" />
        </template>
        {{ authStore.error }}
      </q-banner>

      <!-- Login Form -->
      <q-form @submit.prevent="handleLogin" class="clay-form">
        <q-input
          v-model="form.email"
          label="Email Address"
          type="email"
          outlined
          rounded
          :rules="[
            (val) => !!val || 'Email is required',
            (val) => /.+@.+\..+/.test(val) || 'Enter a valid email',
          ]"
          lazy-rules
          class="clay-input"
        >
          <template v-slot:prepend>
            <q-icon name="email" />
          </template>
        </q-input>

        <q-input
          v-model="form.password"
          label="Password"
          :type="showPassword ? 'text' : 'password'"
          outlined
          rounded
          :rules="[(val) => !!val || 'Password is required']"
          lazy-rules
          class="clay-input"
        >
          <template v-slot:prepend>
            <q-icon name="lock" />
          </template>
          <template v-slot:append>
            <q-icon
              :name="showPassword ? 'visibility_off' : 'visibility'"
              class="cursor-pointer"
              @click="showPassword = !showPassword"
            />
          </template>
        </q-input>

        <div class="clay-actions">
          <q-btn
            class="clay-btn clay-btn--login"
            unelevated
            label="Login"
            type="submit"
            :loading="authStore.loading"
            :disable="authStore.loading"
          />
          <q-btn
            class="clay-btn clay-btn--signup"
            unelevated
            label="Sign Up"
            @click="$router.push('/signup')"
          />
        </div>
      </q-form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
})

const showPassword = ref(false)

const handleLogin = async () => {
  try {
    const data = await authStore.login(form.email, form.password)
    const role = data.user.role.toLowerCase()

    // Redirect based on role
    switch (role) {
      case 'admin':
        router.push('/admin/dashboard')
        break
      case 'staff':
        router.push('/staff/dashboard')
        break
      case 'user':
        router.push('/user/dashboard')
        break
      default:
        router.push('/login')
    }
  } catch {
    // Error is already set in the store
  }
}
</script>

<style lang="scss" scoped>
@import './LoginPage.scss';
</style>
