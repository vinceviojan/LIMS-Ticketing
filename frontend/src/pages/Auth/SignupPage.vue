<template>
  <div class="signup-page">
    <div class="signup-container">
      <div class="signup-card">

        <!-- Header -->
        <div class="signup-header">
          <h1>Sign Up</h1>
          <p>Create your account to continue</p>
        </div>

        <!-- Signup Form -->
        <q-form
          class="signup-form"
          @submit.prevent="signup"
        >

          <!-- First Name / Last Name -->
          <div class="signup-row">

            <!-- First Name -->
            <q-input
              v-model="form.first_name"
              label="First Name"
              outlined
              class="signup-input"
              :error="!!errors.first_name"
              :error-message="errors.first_name"
            />

            <!-- Last Name -->
            <q-input
              v-model="form.last_name"
              label="Last Name"
              outlined
              class="signup-input"
              :error="!!errors.last_name"
              :error-message="errors.last_name"
            />

          </div>

          <!-- Full Name -->
          <q-input
            v-model="form.name"
            label="Full Name"
            outlined
            class="signup-input"
            :error="!!errors.name"
            :error-message="errors.name"
          />

          <!-- Email -->
          <q-input
            v-model="form.email"
            type="email"
            label="Email Address"
            outlined
            class="signup-input"
            :error="!!errors.email"
            :error-message="errors.email"
          />

          <!-- Password -->
          <q-input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            label="Password"
            outlined
            class="signup-input"
            :error="!!errors.password"
            :error-message="errors.password"
          >
            <template #append>
              <q-icon
                :name="
                  showPassword
                    ? 'visibility'
                    : 'visibility_off'
                "
                class="cursor-pointer"
                @click="showPassword = !showPassword"
              />
            </template>
          </q-input>

          <!-- Confirm Password -->
          <q-input
            v-model="form.password_confirmation"
            :type="
              showConfirmPassword
                ? 'text'
                : 'password'
            "
            label="Confirm Password"
            outlined
            class="signup-input"
            :error="!!errors.password_confirmation"
            :error-message="errors.password_confirmation"
          >
            <template #append>
              <q-icon
                :name="
                  showConfirmPassword
                    ? 'visibility'
                    : 'visibility_off'
                "
                class="cursor-pointer"
                @click="
                  showConfirmPassword =
                    !showConfirmPassword
                "
              />
            </template>
          </q-input>

          <!-- Division / Section -->
          <div class="signup-row">

            <!-- Division -->
            <q-select
              v-model="form.division"
              :options="divisionOptions"
              label="Division"
              outlined
              class="signup-input"
              :error="!!errors.division"
              :error-message="errors.division"
            />

            <!-- Section -->
            <q-select
              v-model="form.sections"
              :options="sectionOptions"
              label="Section"
              outlined
              class="signup-input"
              :error="!!errors.sections"
              :error-message="errors.sections"
            />

          </div>

          <!-- Role / Position -->
          <div class="signup-row">

            <!-- Role -->
            <q-select
              v-model="form.role"
              :options="roleOptions"
              label="Role"
              outlined
              class="signup-input"
              :error="!!errors.role"
              :error-message="errors.role"
            />

            <!-- Position -->
            <q-input
              v-model="form.position"
              label="Position"
              outlined
              class="signup-input"
              :error="!!errors.position"
              :error-message="errors.position"
            />

          </div>

          <!-- Status -->
          <q-select
            v-model="form.status"
            :options="statusOptions"
            label="Status"
            outlined
            class="signup-input"
            :error="!!errors.status"
            :error-message="errors.status"
          />

          <!-- Signup Button -->
          <q-btn
            type="submit"
            label="Create Account"
            class="signup-button"
            unelevated
            :loading="loading"
            :disable="loading"
          />

        </q-form>

        <!-- Login -->
        <div class="login-section">
          <span>Already have an account?</span>

          <q-btn
            flat
            label="Login"
            class="login-button"
            @click="goToLogin"
          />
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

// =========================================
// Router
// =========================================

const router = useRouter()

// =========================================
// Form
// =========================================

const form = reactive({
  first_name: '',
  last_name: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  division: '',
  sections: '',
  status: 'active',
  role: '',
  position: ''
})

// =========================================
// State
// =========================================

const loading = ref(false)

const showPassword = ref(false)

const showConfirmPassword = ref(false)

// =========================================
// Validation Errors
// =========================================

const errors = reactive({
  first_name: '',
  last_name: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  division: '',
  sections: '',
  status: '',
  role: '',
  position: ''
})

// =========================================
// Division Options
// =========================================

const divisionOptions = [
  'Office of the Director',
  'Office of the Assistant Director',
  'Office of the Chief'
]

// =========================================
// Section Options
// =========================================

const sectionOptions = [
  'Soil Chemistry Section',
  'Soil Microbiology Section',
  'Soil Physics Section',
  'Water Chemistry Section',
  'Rapid Soil Test Section',
  'Technical Equipment Instrumentation and Maintenance',
  'Regional Soil Laboratory',
  'Private Laboratories',
  'Customer Center',
  'Document Control',
  'Others'
]

// =========================================
// Role Options
// =========================================

const roleOptions = [
  'Administrator',
  'User'
]

// =========================================
// Status Options
// =========================================

const statusOptions = [
  'active',
  'inactive'
]

// =========================================
// Signup
// =========================================

const signup = async () => {
  loading.value = true

  // Clear previous validation errors
  Object.keys(errors).forEach((key) => {
    errors[key] = ''
  })

  try {
    // Temporary test
    console.log('Signup Form:', {
      ...form
    })

    /*
     * Your Laravel API request will go here.
     *
     * Example:
     *
     * await axios.post('/api/register', form)
     */

  } catch (error) {
    console.error('Signup error:', error)

    /*
     * Laravel validation errors can be handled here.
     *
     * Example:
     *
     * if (error.response?.data?.errors) {
     *   Object.keys(error.response.data.errors).forEach((key) => {
     *     errors[key] =
     *       error.response.data.errors[key][0]
     *   })
     * }
     */
  } finally {
    loading.value = false
  }
}

// =========================================
// Go To Login
// =========================================

const goToLogin = () => {
  router.push('/login')
}
</script>

<style lang="scss" scoped>
@import './SignupPage.scss';
</style>

