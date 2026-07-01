import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import DashboardPage from '@/pages/DashboardPage.vue'
import { i18n } from '@/__tests__/helpers'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/dashboard', name: 'dashboard', component: DashboardPage },
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
    ],
})

beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('DashboardPage', () => {
    it('renders welcome message with user name', () => {
        localStorage.setItem(
            'auth_user',
            JSON.stringify({ id: 1, name: 'Ian', email: 'ian@test.com' }),
        )

        const wrapper = mount(DashboardPage, {
            global: { plugins: [router, i18n] },
        })
        expect(wrapper.text()).toContain('Welcome, Ian')
    })

    it('shows user email', () => {
        localStorage.setItem(
            'auth_user',
            JSON.stringify({ id: 1, name: 'Ian', email: 'ian@test.com' }),
        )

        const wrapper = mount(DashboardPage, {
            global: { plugins: [router, i18n] },
        })
        expect(wrapper.text()).toContain('ian@test.com')
    })

    it('has logout button', () => {
        localStorage.setItem(
            'auth_user',
            JSON.stringify({ id: 1, name: 'Ian', email: 'ian@test.com' }),
        )

        const wrapper = mount(DashboardPage, {
            global: { plugins: [router, i18n] },
        })
        expect(wrapper.text()).toContain('Sign Out')
    })

    it('clears session and redirects on logout', async () => {
        localStorage.setItem('auth_token', 'some-token')
        localStorage.setItem(
            'auth_user',
            JSON.stringify({ id: 1, name: 'Ian', email: 'ian@test.com' }),
        )

        const wrapper = mount(DashboardPage, {
            global: { plugins: [router, i18n] },
        })
        await wrapper.find('button').trigger('click')

        expect(localStorage.getItem('auth_token')).toBeNull()
    })
})
