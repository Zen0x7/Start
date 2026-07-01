import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import DashboardPage from '@/pages/DashboardPage.vue'
import HomePage from '@/pages/HomePage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomePage,
            meta: { guest: true },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: DashboardPage,
            meta: { requiresAuth: true, hideGlobalNav: true },
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/pages/auth/RegisterPage.vue'),
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/pages/auth/LoginPage.vue'),
            meta: { guest: true },
        },
        {
            path: '/email/verify',
            name: 'verify-email',
            component: () => import('@/pages/auth/VerifyEmailPage.vue'),
        },
        {
            path: '/email/verify/:token',
            name: 'confirm-email',
            component: () => import('@/pages/auth/ConfirmEmailPage.vue'),
        },
        {
            path: '/totp/setup',
            name: 'totp-setup',
            component: () => import('@/pages/auth/TotpSetupPage.vue'),
        },
        {
            path: '/totp/verify',
            name: 'totp-verify',
            component: () => import('@/pages/auth/TotpVerifyPage.vue'),
        },
    ],
})

router.beforeEach((to, from) => {
    const auth = useAuthStore()

    if (to.name === 'home') {
        return auth.isAuthenticated ? { name: 'dashboard' } : { name: 'login' }
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login' }
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' }
    }
})

export default router
