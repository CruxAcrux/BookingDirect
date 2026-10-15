import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

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
      path: '/s/:slug',
      component: () => import('@/features/public-shop/views/ShopLayout.vue'),
      props: true,
      children: [
        {
          path: '',
          name: 'shop',
          component: () => import('@/features/public-shop/views/ShopView.vue'),
        },
        {
          path: 'book',
          name: 'book',
          component: () => import('@/features/booking/views/BookingView.vue'),
        },
        {
          path: 'my-bookings',
          name: 'my-bookings',
          component: () => import('@/features/manage-booking/views/MyBookingsView.vue'),
        },
      ],
    },
    {
      path: '/b/:token',
      name: 'manage-booking',
      component: () => import('@/features/manage-booking/views/ManageBookingView.vue'),
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
          // A platform admin without a shop has nothing to see on the overview.
          beforeEnter: () => {
            const auth = useAuthStore()
            return auth.currentShop === null && auth.user?.is_platform_admin
              ? { name: 'admin-shops' }
              : true
          },
        },
        {
          path: 'calendar',
          name: 'calendar',
          component: () => import('@/features/calendar/views/CalendarView.vue'),
        },
        {
          path: 'team',
          name: 'team',
          component: () => import('@/features/team/views/TeamView.vue'),
        },
        {
          path: 'team/:barberId(\\d+)',
          component: () => import('@/features/team/views/BarberLayout.vue'),
          children: [
            {
              path: '',
              name: 'barber',
              component: () => import('@/features/team/views/BarberProfileView.vue'),
            },
            {
              path: 'services',
              name: 'barber-services',
              component: () => import('@/features/team/views/BarberServicesView.vue'),
            },
            {
              path: 'schedule',
              name: 'barber-schedule',
              component: () => import('@/features/team/views/BarberScheduleView.vue'),
            },
            {
              path: 'time-off',
              name: 'barber-time-off',
              component: () => import('@/features/team/views/BarberTimeOffView.vue'),
            },
            {
              path: 'portfolio',
              name: 'barber-portfolio',
              component: () => import('@/features/team/views/BarberPortfolioView.vue'),
            },
          ],
        },
        {
          path: 'services',
          name: 'services',
          component: () => import('@/features/services/views/ServicesView.vue'),
        },
        {
          path: 'settings',
          component: () => import('@/features/settings/views/SettingsLayout.vue'),
          meta: { requiresOwner: true },
          children: [
            {
              path: '',
              name: 'settings',
              component: () => import('@/features/settings/views/ShopProfileView.vue'),
            },
            {
              path: 'booking',
              name: 'settings-booking',
              component: () => import('@/features/settings/views/BookingRulesView.vue'),
            },
            {
              path: 'hours',
              name: 'settings-hours',
              component: () => import('@/features/settings/views/OpeningHoursView.vue'),
            },
            {
              path: 'images',
              name: 'settings-images',
              component: () => import('@/features/settings/views/ShopImagesView.vue'),
            },
          ],
        },
        {
          path: 'admin/shops',
          name: 'admin-shops',
          component: () => import('@/features/admin/views/AdminShopsView.vue'),
          meta: { requiresPlatformAdmin: true },
        },
      ],
    },
  ],
  // Steps of the booking flow change only the query; keep the scroll position there.
  scrollBehavior: (to, from, saved) =>
    saved ?? (to.path === from.path && to.name === 'book' ? false : { top: 0 }),
})

router.beforeEach(authGuard)

export default router
