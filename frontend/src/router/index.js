import { defineRouter } from '#q-app'
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router'

import { PublicRoutes } from './PublicRoutes'
import { PrivateRoutes } from './PrivateRoutes'

const routes = [
  ...PublicRoutes,
  ...PrivateRoutes,
  {
    path: '/:catchAll(.*)*',
    component: () => import('../pages/NotFoundPage.vue'),
  },
]

export default defineRouter(() => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory

  const Router = createRouter({
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE),
    routes,
    scrollBehavior: () => ({ left: 0, top: 0 }),
  })

  // =====================================================
  // AUTH & ROLE NAVIGATION GUARD
  // =====================================================

  Router.beforeEach((to) => {
    const token = localStorage.getItem('token')
    const storedUser = localStorage.getItem('user')
    const user = storedUser ? JSON.parse(storedUser) : null
    const role = user?.role?.toLowerCase() || null

    // ---- Redirect authenticated users away from public pages ----
    if (token && (to.path === '/login' || to.path === '/signup' || to.path === '/')) {
      switch (role) {
        case 'admin':
          return '/admin/dashboard'
        case 'staff':
          return '/staff/dashboard'
        case 'user':
          return '/user/dashboard'
        default:
          return true
      }
    }

    // ---- Require authentication for private routes ----
    if (to.meta.requiresAuth && !token) {
      return '/login'
    }

    // ---- Role-based access check ----
    if (to.meta.requiredRoles && token) {
      const allowedRoles = to.meta.requiredRoles.map((r) => r.toLowerCase())
      if (!allowedRoles.includes(role)) {
        return '/forbidden'
      }
    }

    return true
  })

  return Router
})
