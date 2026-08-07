export const PrivateRoutes = [
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiredRoles: ['ADMIN'] },
    children: [
      { path: '', redirect: { name: 'admin-dashboard' } },
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: () => import('../pages/Admin/DashboardPage.vue')
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('../pages/Admin/UserPage.vue')
      }
    ]
  },

  {
    path: '/staff',
    component: () => import('../layouts/StaffLayout.vue'),
    meta: { requiresAuth: true, requiredRoles: ['ADMIN', 'STAFF'] },
    children: [
      { path: '', redirect: { name: 'staff-dashboard' } },
      {
        path: 'dashboard',
        name: 'staff-dashboard',
        component: () => import('../pages/Staff/DashboardPage.vue')
      }
    ]
  },

  {
    path: '/user',
    component: () => import('../layouts/UserLayout.vue'),
    meta: { requiresAuth: true, requiredRoles: ['ADMIN', 'STAFF', 'USER'] },
    children: [
      { path: '', redirect: { name: 'user-dashboard' } },
      {
        path: 'dashboard',
        name: 'user-dashboard',
        component: () => import('../pages/User/DashboardPage.vue')
      }
    ]
  }
]