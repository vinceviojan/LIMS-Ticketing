<template>
  <div class="login-wrapper">
    <!-- Main 2-Column Container -->
    <div class="login-card">
      
      <!-- Left Column: Inner Rounded Graphic Card -->
      <div class="login-left-card">
        <!-- Watermark Logo -->
        <img src="../../assets/bswm-logo-sidebar.png" alt="" class="card-watermark" />

        <div class="left-header">
          <div class="brand-mark">
            <img src="../../assets/bswm-logo-sidebar.png" alt="BSWM Logo" class="brand-mark-logo" />
            <span class="brand-mark-title">LIMS Ticketing</span>
          </div>
        </div>

        <div class="left-body">
          <div class="brand-title-group">
            <h1 class="agency-title">Bureau of Soils and Water Management</h1>
            <div class="division-badge">
              <q-icon name="science" size="16px" class="q-mr-xs" />
              <span>Laboratory Services Division</span>
            </div>
          </div>

          <div class="feature-grid">
            <div class="feature-card">
              <div class="feature-icon-wrapper">
                <q-icon name="confirmation_number" size="20px" />
              </div>
              <div class="feature-info">
                <span class="feature-heading">Seamless Ticket Tracking</span>
                <span class="feature-subtext">Monitor lab requests end-to-end</span>
              </div>
            </div>

            <div class="feature-card">
              <div class="feature-icon-wrapper">
                <q-icon name="verified_user" size="20px" />
              </div>
              <div class="feature-info">
                <span class="feature-heading">User Access Security (UAS)</span>
                <span class="feature-subtext">Strict compliance & authority standards</span>
              </div>
            </div>

            <div class="feature-card">
              <div class="feature-icon-wrapper">
                <q-icon name="bolt" size="20px" />
              </div>
              <div class="feature-info">
                <span class="feature-heading">Real-Time Laboratory Requests</span>
                <span class="feature-subtext">Instant updates & queue management</span>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column: Login Form -->
      <div class="login-right-content">
        <div class="form-wrapper">
          <!-- Mobile Branding Header (Visible only on small screens) -->
          <div class="mobile-brand-header">
            <img src="../../assets/bswm-logo-sidebar.png" alt="BSWM Logo" class="mobile-logo" />
            <span class="mobile-brand-title">LIMS Ticketing</span>
          </div>

          <div class="form-header">
            <h1 class="welcome-title">Welcome Back!</h1>
            <p class="welcome-subtitle">Please enter your details to sign in.</p>
          </div>

          <!-- Error Alert -->
          <q-banner v-if="authStore.error" class="bg-negative text-white q-mb-lg rounded-borders shadow-2" dense>
            <template v-slot:avatar>
              <q-icon name="warning" color="white" size="xs" />
            </template>
            {{ authStore.error }}
          </q-banner>

          <!-- Login Form -->
          <q-form @submit.prevent="handleLogin" class="form-body">
            <div class="input-group">
              <label class="input-label">Email Address</label>
              <q-input
                v-model="form.email"
                placeholder="name@bswm.da.gov.ph"
                outlined
                dense
                class="custom-field"
                :rules="[
                  (val) => !!val || 'Email is required',
                  (val) => /.+@.+\..+/.test(val) || 'Enter a valid email address',
                ]"
                lazy-rules
              >
                <template v-slot:prepend>
                  <q-icon name="mail_outline" size="xs" class="field-icon" />
                </template>
              </q-input>
            </div>

            <div class="input-group">
              <div class="label-row flex justify-between items-center">
                <label class="input-label">Password</label>
                <a href="#" @click.prevent class="forgot-password-link">Forgot password?</a>
              </div>
              <q-input
                v-model="form.password"
                placeholder="••••••••"
                :type="showPassword ? 'text' : 'password'"
                outlined
                dense
                class="custom-field"
                :rules="[(val) => !!val || 'Password is required']"
                lazy-rules
              >
                <template v-slot:prepend>
                  <q-icon name="lock_outline" size="xs" class="field-icon" />
                </template>
                <template v-slot:append>
                  <q-icon
                    :name="showPassword ? 'visibility_off' : 'visibility'"
                    class="cursor-pointer field-icon-toggle"
                    size="xs"
                    @click="showPassword = !showPassword"
                  />
                </template>
              </q-input>
            </div>

            <div class="action-group">
              <q-btn
                class="full-width gradient-submit-btn"
                unelevated
                type="submit"
                :loading="authStore.loading"
                :disable="authStore.loading"
              >
                <div class="row items-center no-wrap justify-center full-width">
                  <span class="q-mr-xs text-weight-bold">Login</span>
                  <q-icon name="arrow_forward" size="18px" />
                </div>
              </q-btn>

              <div class="signup-footer-text text-center q-mt-lg">
                <span class="text-grey-7">Don't have an account? </span>
                <router-link to="/signup" class="text-weight-bold link-signup">
                  Sign Up
                </router-link>
              </div>
            </div>
          </q-form>

        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, inject } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const authStore = inject('authStore')

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
