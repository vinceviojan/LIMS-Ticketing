<template>
  <div class="signup-page">
    <!-- Main Signup Card -->
    <div class="signup-card">
      <!-- ==========================================================
           Left Column: Branding / Features
           ========================================================== -->
      <div class="login-left-card">
        <!-- Watermark Logo -->
        <img src="../../assets/bswm-logo-sidebar.png" alt="" class="card-watermark" />

        <!-- Brand Header -->
        <div class="left-header">
          <div class="brand-mark">
            <img src="../../assets/bswm-logo.png" alt="BSWM Logo" class="brand-mark-logo" />

            <span class="brand-mark-title"> LIMS Ticketing </span>
          </div>
        </div>

        <!-- Left Body -->
        <div class="left-body">
          <!-- Agency Information -->
          <div class="brand-title-group">
            <h1 class="agency-title">Bureau of Soils and Water Management</h1>

            <div class="division-badge">
              <q-icon name="science" size="35px" class="q-mr-xs" />

              <span> Laboratory Services Division </span>
            </div>
          </div>

          <!-- Features -->
          <div class="feature-grid">
            <!-- Feature 1 -->
            <div class="feature-card">
              <div class="feature-icon-wrapper">
                <q-icon name="confirmation_number" size="20px" />
              </div>

              <div class="feature-info">
                <span class="feature-heading"> Continuous Ticket Tracking </span>

                <span class="feature-subtext"> Monitor lab requests end-to-end </span>
              </div>
            </div>

            <!-- Feature 2 -->
            <div class="feature-card">
              <div class="feature-icon-wrapper">
                <q-icon name="verified_user" size="20px" />
              </div>

              <div class="feature-info">
                <span class="feature-heading"> User Access Security (UAS) </span>

                <span class="feature-subtext"> Strict compliance & authority standards </span>
              </div>
            </div>

            <!-- Feature 3 -->
            <div class="feature-card">
              <div class="feature-icon-wrapper">
                <q-icon name="bolt" size="20px" />
              </div>

              <div class="feature-info">
                <span class="feature-heading"> Real-Time Laboratory Requests </span>

                <span class="feature-subtext"> Instant updates & queue management </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==========================================================
           Right Column: Signup Form
           ========================================================== -->
      <div class="login-right-content">
        <div class="form-wrapper">
          <!-- Mobile Branding -->
          <div class="mobile-brand-header">
            <img src="../../assets/bswm-logo-sidebar.png" alt="BSWM Logo" class="mobile-logo" />

            <span class="mobile-brand-title"> LIMS Helpdesk </span>
          </div>

          <!-- Signup Header -->
          <div class="form-header">
            <h1 class="welcome-title">Create an Account</h1>

            <p class="welcome-subtitle">Please enter your details to create your account.</p>
          </div>

          <!-- ======================================================
               Signup Form
               ====================================================== -->
          <q-form @submit.prevent="signup" class="form-body">
            <!-- First Name / Last Name -->
            <div class="signup-row">
              <!-- First Name -->
              <div class="input-group">
                <label class="input-label"> First Name </label>

                <q-input
                  v-model="form.first_name"
                  placeholder="First name"
                  outlined
                  dense
                  class="custom-field"
                  :error="!!errors.first_name"
                  :error-message="errors.first_name"
                  :rules="[(val) => !!val || 'First name is required']"
                  lazy-rules
                >
                  <template #prepend>
                    <q-icon name="person_outline" size="xs" class="field-icon" />
                  </template>
                </q-input>
              </div>

              <!-- Last Name -->
              <div class="input-group">
                <label class="input-label"> Last Name </label>

                <q-input
                  v-model="form.last_name"
                  placeholder="Last name"
                  outlined
                  dense
                  class="custom-field"
                  :error="!!errors.last_name"
                  :error-message="errors.last_name"
                  :rules="[(val) => !!val || 'Last name is required']"
                  lazy-rules
                >
                  <template #prepend>
                    <q-icon name="person_outline" size="xs" class="field-icon" />
                  </template>
                </q-input>
              </div>
            </div>

            <!-- Email -->
            <div class="input-group">
              <label class="input-label"> Email Address </label>

              <q-input
                v-model="form.email"
                type="email"
                placeholder="name@bswm.da.gov.ph"
                outlined
                dense
                class="custom-field"
                :error="!!errors.email"
                :error-message="errors.email"
                :rules="[
                  (val) => !!val || 'Email is required',

                  (val) => /.+@.+\..+/.test(val) || 'Enter a valid email address',
                ]"
                lazy-rules
              >
                <template #prepend>
                  <q-icon name="mail_outline" size="xs" class="field-icon" />
                </template>
              </q-input>
            </div>

            <!-- Password -->
            <div class="input-group">
              <label class="input-label"> Password </label>

              <q-input
                v-model="form.password"
                placeholder="••••••••"
                :type="showPassword ? 'text' : 'password'"
                outlined
                dense
                class="custom-field"
                :error="!!errors.password"
                :error-message="errors.password"
                :rules="[
                  (val) => !!val || 'Password is required',

                  (val) => val.length >= 8 || 'Password must be at least 8 characters',
                ]"
                lazy-rules
              >
                <template #prepend>
                  <q-icon name="lock_outline" size="xs" class="field-icon" />
                </template>

                <template #append>
                  <q-icon
                    :name="showPassword ? 'visibility_off' : 'visibility'"
                    class="cursor-pointer field-icon-toggle"
                    size="xs"
                    @click="showPassword = !showPassword"
                  />
                </template>
              </q-input>
            </div>

            <!-- Confirm Password -->
            <div class="input-group">
              <label class="input-label"> Confirm Password </label>

              <q-input
                v-model="form.password_confirmation"
                placeholder="••••••••"
                :type="showConfirmPassword ? 'text' : 'password'"
                outlined
                dense
                class="custom-field"
                :error="!!errors.password_confirmation"
                :error-message="errors.password_confirmation"
                :rules="[
                  (val) => !!val || 'Please confirm your password',

                  (val) => val === form.password || 'Passwords do not match',
                ]"
                lazy-rules
              >
                <template #prepend>
                  <q-icon name="lock_outline" size="xs" class="field-icon" />
                </template>

                <template #append>
                  <q-icon
                    :name="showConfirmPassword ? 'visibility_off' : 'visibility'"
                    class="cursor-pointer field-icon-toggle"
                    size="xs"
                    @click="showConfirmPassword = !showConfirmPassword"
                  />
                </template>
              </q-input>
            </div>

            <!-- Division / Section -->
            <div class="signup-row">
              <!-- Division -->
              <div class="input-group">
                <label class="input-label"> Division </label>

                <q-select
                  v-model="form.division_id"
                  :options="divisionOptions"
                  option-value="id"
                  option-label="name"
                  emit-value
                  map-options
                  outlined
                  dense
                  class="custom-field"
                  :error="!!errors.division_id"
                  :error-message="errors.division_id"
                  :rules="[(val) => !!val || 'Division is required']"
                  lazy-rules
                >
                  <template #prepend>
                    <q-icon name="business" size="xs" class="field-icon" />
                  </template>
                </q-select>
              </div>

              <!-- Section -->
              <div class="input-group">
                <label class="input-label"> Section </label>

                <q-select
                  v-model="form.section_id"
                  :options="sectionOptions"
                  option-value="id"
                  option-label="name"
                  emit-value
                  map-options
                  outlined
                  dense
                  class="custom-field"
                  :error="!!errors.section_id"
                  :error-message="errors.section_id"
                  :rules="[(val) => !!val || 'Section is required']"
                  lazy-rules
                >
                  <template #prepend>
                    <q-icon name="groups" size="xs" class="field-icon" />
                  </template>
                </q-select>
              </div>
            </div>

            <!-- Position -->
            <div class="input-group">
              <label class="input-label"> Position </label>

              <q-input
                v-model="form.position"
                placeholder="Enter your position"
                outlined
                dense
                class="custom-field"
                :error="!!errors.position"
                :error-message="errors.position"
                :rules="[(val) => !!val || 'Position is required']"
                lazy-rules
              >
                <template #prepend>
                  <q-icon name="work_outline" size="xs" class="field-icon" />
                </template>
              </q-input>
            </div>

            <!-- Signup Button -->
            <div class="action-group">
              <q-btn
                class="full-width gradient-submit-btn"
                unelevated
                type="submit"
                :loading="loading"
                :disable="loading"
              >
                <div class="row items-center no-wrap justify-center full-width">
                  <span class="q-mr-xs text-weight-bold"> Create Account </span>

                  <q-icon name="arrow_forward" size="18px" />
                </div>
              </q-btn>

              <!-- Login Link -->
              <div class="signup-footer-text text-center q-mt-lg">
                <span class="text-grey-7"> Already have an account? </span>

                <router-link to="/login" class="text-weight-bold link-signup"> Login </router-link>
              </div>
            </div>
          </q-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../../boot/axios'
import { fetchDivisions } from '../../constants/organization'

import './SignupPage.scss'

const router = useRouter()

// ==========================================================================
// State
// ==========================================================================

const loading = ref(false)

const showPassword = ref(false)

const showConfirmPassword = ref(false)

// ==========================================================================
// Form
// ==========================================================================

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  division_id: null,
  section_id: null,
  position: '',
})

// ==========================================================================
// Validation Errors
// ==========================================================================

const errors = reactive({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  division_id: '',
  section_id: '',
  position: '',
})

// ==========================================================================
// Options
// ==========================================================================

const divisionOptions = ref([])
const sectionOptions = ref([])

onMounted(async () => {
  divisionOptions.value = await fetchDivisions()
})

watch(
  () => form.division_id,
  (newDivisionId) => {
    form.section_id = null
    const selectedDivision = divisionOptions.value.find((d) => d.id === newDivisionId)
    sectionOptions.value = selectedDivision ? selectedDivision.sections : []
  },
)

// ==========================================================================
// Clear Validation Errors
// ==========================================================================

const clearErrors = () => {
  errors.first_name = ''
  errors.last_name = ''
  errors.email = ''
  errors.password = ''
  errors.password_confirmation = ''
  errors.division_id = ''
  errors.section_id = ''
  errors.position = ''
}

// ==========================================================================
// Signup
// ==========================================================================

const signup = async () => {
  clearErrors()

  loading.value = true

  try {
    const response = await api.post('/register', {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      division_id: form.division_id,
      section_id: form.section_id,
      position: form.position,
    })

    console.log('Registration successful:', response.data)

    // Redirect to Login
    router.push({
      path: '/login',
      query: {
        registered: 'true',
      },
    })
  } catch (error) {
    console.error('Registration error:', error)

    // Laravel Validation Error
    if (error.response?.status === 422) {
      const validationErrors = error.response.data.errors || {}

      errors.first_name = validationErrors.first_name?.[0] || ''

      errors.last_name = validationErrors.last_name?.[0] || ''

      errors.email = validationErrors.email?.[0] || ''

      errors.password = validationErrors.password?.[0] || ''

      errors.password_confirmation = validationErrors.password_confirmation?.[0] || ''

      errors.division_id = validationErrors.division_id?.[0] || ''

      errors.section_id = validationErrors.section_id?.[0] || ''

      errors.position = validationErrors.position?.[0] || ''
    } else {
      alert(error.response?.data?.message || 'Registration failed. Please try again.')
    }
  } finally {
    loading.value = false
  }
}
</script>
