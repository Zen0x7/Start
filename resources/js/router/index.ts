import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '@/pages/HomePage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomePage,
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
    ],
})

export default router
