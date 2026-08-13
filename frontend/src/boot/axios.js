import { boot } from 'quasar/wrappers'
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: false,
})

export default boot(({ app, router }) => {
  // Request interceptor — attach Bearer token from localStorage
  api.interceptors.request.use(
    (config) => {
      const token = localStorage.getItem('token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
      return config
    },
    (error) => Promise.reject(error),
  )

  // ---------------------------------------------------------------
  // Response interceptor — handle 401 globally
  // ---------------------------------------------------------------
  api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        router.push('/login')
      }
      return Promise.reject(error)
    },
  )

  // Make available via Options API
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }
