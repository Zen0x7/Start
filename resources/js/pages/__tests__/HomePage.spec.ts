import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '@/pages/HomePage.vue'
import { i18n } from '@/__tests__/helpers'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: HomePage },
        { path: '/register', name: 'register', component: { template: '<div>Register</div>' } },
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
        { path: '/dashboard', name: 'dashboard', component: { template: '<div>Dashboard</div>' }, meta: { requiresAuth: true } },
    ],
})

beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('HomePage', () => {
    it('renders welcome title', () => {
        const wrapper = mount(HomePage, {
            global: { plugins: [router, i18n] },
        })
        expect(wrapper.text()).toContain('Welcome')
    })

    it('shows login and register links when not authenticated', () => {
        const wrapper = mount(HomePage, {
            global: { plugins: [router, i18n] },
        })
        expect(wrapper.text()).toContain('Create Account')
        expect(wrapper.text()).toContain('Sign In')
    })

    it('shows dashboard link when authenticated', () => {
        localStorage.setItem('auth_token', 'some-token')

        const wrapper = mount(HomePage, {
            global: { plugins: [router, i18n] },
        })
        expect(wrapper.text()).toContain('Go to Dashboard')
        expect(wrapper.text()).not.toContain('Create Account')
    })
})
