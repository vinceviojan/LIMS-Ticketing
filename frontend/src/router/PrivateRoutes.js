export const PrivateRoutes = [
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    children: [
    //   {
    //     path: 'dashboard',
    //     component: () => import('../pages/Admin/DashboardPage.vue')
    //   }
    ]
  },

  {
    path: '/staff',
    component: () => import('../layouts/StaffLayout.vue'),
    children: [
    //   {
    //     path: 'dashboard',
    //     component: () => import('../pages/Staff/DashboardPage.vue')
    //   }
    ]
  },

  {
    path: '/user',
    component: () => import('../layouts/UserLayout.vue'),
    children: [
    //   {
    //     path: 'dashboard',
    //     component: () => import('../pages/User/DashboardPage.vue')
    //   }
    ]
  }
]