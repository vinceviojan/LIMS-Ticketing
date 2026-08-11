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
      },
      {
        path: 'ticket-management',
        name: 'admin-tickets',
        component: () => import('../pages/Admin/TicketManagementPage.vue')
      },
      {
        path: 'problem-categories',
        name: 'admin-problem-categories',
        component: () => import('../pages/Admin/ProblemCategoriesPage.vue')
      },
      {
        path: 'reports',
        name: 'admin-reports',
        component: () => import('../pages/Admin/ReportPage.vue')
      },
      {
        path: 'logs',
        name: 'admin-logs',
        component: () => import('../pages/Admin/LogsPage.vue')
      },
      {
        path: 'settings',
        name: 'admin-settings',
        component: () => import('../pages/Admin/SettingsPage.vue')
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
      },
      {
        path: 'ticket-management',
        name: 'staff-tickets',
        component: () => import('../pages/Staff/TicketManagementPage.vue')
      },
      {
        path: 'settings',
        name: 'staff-settings',
        component: () => import('../pages/Staff/SettingsPage.vue')
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