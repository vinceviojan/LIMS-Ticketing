export const PublicRoutes = [
    {
        path: '/',
        component: () => import('../layouts/AuthLayout.vue'),
        children: [
            { path: '', redirect: 'login' },
            {
                path: 'login',
                name: 'login',
                component: () => import('../pages/Auth/LoginPage.vue'),

            },
            {
                path: 'signup',
                name: 'signup',
                component: () => import('../pages/Auth/SignupPage.vue'),
            }
        ]
    },
    {
        path: '/forbidden',
        name: 'forbidden',
        component: () => import('../pages/ForbiddenPage.vue')
    }
];
