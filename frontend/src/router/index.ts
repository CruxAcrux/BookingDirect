import { createRouter, createWebHistory } from 'vue-router'

import { authGuard } from './guards'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: () => import('@/components/layout/SiteLayout.vue'),
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('@/features/home/views/HomeView.vue'),
        },
        {
          path: ':pathMatch(.*)*',
          name: 'not-found',
          component: () => import('@/features/errors/views/NotFoundView.vue'),
        },
      ],
    },
    {
      path: '/',
      component: () => import('@/features/auth/views/AuthLayout.vue'),
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('@/features/auth/views/LoginView.vue'),
          meta: { guestOnly: true },
        },
        {
          path: 'forgot-password',
          name: 'forgot-password',
          component: () => import('@/features/auth/views/ForgotPasswordView.vue'),
          meta: { guestOnly: true },
        },
        {
          path: 'reset-password',
          name: 'reset-password',
          component: () => import('@/features/auth/views/ResetPasswordView.vue'),
        },
      ],
    },
    {
      path: '/dashboard',
      component: () => import('@/features/dashboard/views/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('@/features/dashboard/views/DashboardHomeView.vue'),
        },
      ],
    },
  ],
  scrollBehavior: (_to, _from, saved) => saved ?? { top: 0 },
})

router.beforeEach(authGuard)

export default router
