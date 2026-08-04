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
    component: () => import('../pages/NotFoundPage.vue')
  }
]

export default defineRouter(() => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : (
        import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history'
          ? createWebHistory
          : createWebHashHistory
      )

  const Router = createRouter({
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE),
    routes,
    scrollBehavior: () => ({ left: 0, top: 0 })
  })

  // =====================================================
  // TEMPORARY AUTH & ROLE GUARD
  // =====================================================

  Router.beforeEach((to, from, next) => {
    // ---------------------------------------------------
    // Authentication (Uncomment when backend is ready)
    // ---------------------------------------------------

    // const token = localStorage.getItem('token')
    // const role = localStorage.getItem('role')

    // if (!token && to.meta.requiresAuth) {
    //   return next('/login')
    // }

    // ---------------------------------------------------
    // Temporary role for testing
    // ---------------------------------------------------

    const role = 'admin'
    // const role = 'staff'
    // const role = 'user'

    // Redirect "/" or "/dashboard" based on role
    if (to.path === '/' || to.path === '/dashboard') {
      switch (role) {
        case 'admin':
          return next('/admin/dashboard')

        case 'staff':
          return next('/staff/dashboard')

        case 'user':
          return next('/user/dashboard')

        default:
          return next('/login')
      }
    }

    next()
  })

  return Router
})