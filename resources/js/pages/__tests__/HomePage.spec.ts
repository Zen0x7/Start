import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '@/pages/HomePage.vue'

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
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Bienvenido')
    })

    it('shows login and register links when not authenticated', () => {
        const wrapper = mount(HomePage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Crear Cuenta')
        expect(wrapper.text()).toContain('Iniciar Sesión')
    })

    it('shows dashboard link when authenticated', () => {
        localStorage.setItem('auth_token', 'some-token')

        const wrapper = mount(HomePage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Ir al Dashboard')
        expect(wrapper.text()).not.toContain('Crear Cuenta')
    })
})
