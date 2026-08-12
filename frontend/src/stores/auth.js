import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '../boot/axios'

export const useAuthStore = defineStore('auth', () => {
    // ---------------------------------------------------------------------------
    // State
    // ---------------------------------------------------------------------------
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
    const token = ref(localStorage.getItem('token') || null)
    const loading = ref(false)
    const error = ref(null)

    // ---------------------------------------------------------------------------
    // Getters
    // ---------------------------------------------------------------------------
    const isAuthenticated = computed(() => !!token.value)
    const userRole = computed(() => user.value?.role?.toLowerCase() || null)
    const userName = computed(() => {
        if (!user.value) return ''
        return `${user.value.first_name || ''} ${user.value.last_name || ''}`.trim()
    })
    const divisionId = computed(() => user.value?.division_id || null)
    const sectionId = computed(() => user.value?.section_id || null)
    const userDivision = computed(() => user.value?.division?.name || null)
    const userSection = computed(() => user.value?.section?.name || null)

    // ---------------------------------------------------------------------------
    // Actions
    // ---------------------------------------------------------------------------

    /**
     * Authenticate with email & password, store the returned token.
     */
    async function login(email, password) {
        loading.value = true
        error.value = null

        try {
            const response = await api.post('/login', { email, password })
            const data = response.data

            // Persist to reactive state
            token.value = data.token
            user.value = data.user

            // Persist to localStorage
            localStorage.setItem('token', data.token)
            localStorage.setItem('user', JSON.stringify(data.user))

            // Attach token to future requests
            api.defaults.headers.common['Authorization'] = `Bearer ${data.token}`

            return data
        } catch (err) {
            const message =
                err.response?.data?.message ||
                err.response?.data?.errors?.email?.[0] ||
                'An unexpected error occurred.'
            error.value = message
            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Log out and clear all stored auth data.
     */
    async function logout() {
        try {
            await api.post('/logout')
        } catch {
            // Silently fail — we still clear local state
        } finally {
            token.value = null
            user.value = null
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            delete api.defaults.headers.common['Authorization']
        }
    }

    /**
     * Fetch the current user profile from /api/me.
     */
    async function fetchUser() {
        loading.value = true
        try {
            const response = await api.get('/me')
            user.value = response.data.user
            localStorage.setItem('user', JSON.stringify(response.data.user))
            return response.data.user
        } catch {
            // Token is invalid or expired — clear auth state
            await logout()
            return null
        } finally {
            loading.value = false
        }
    }

    /**
     * Restore auth headers from localStorage on app boot.
     */
    function initAuth() {
        const storedToken = localStorage.getItem('token')
        if (storedToken) {
            token.value = storedToken
            api.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
        }
    }

    return {
        // State
        user,
        token,
        loading,
        error,

        // Getters
        isAuthenticated,
        userRole,
        userName,
        divisionId,
        sectionId,
        userDivision,
        userSection,

        // Actions
        login,
        logout,
        fetchUser,
        initAuth,
    }
})
