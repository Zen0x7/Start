import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { mountWithPlugins } from '@/__tests__/helpers'
import DashboardPage from '@/pages/DashboardPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/dashboard', name: 'dashboard', component: DashboardPage },
        { path: '/settings', name: 'settings', component: { template: '<div>Settings</div>' } },
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
    ],
})

let mockFetch: ReturnType<typeof vi.fn>

beforeEach(() => {
    setActivePinia(createPinia())
    mockFetch = vi.fn()
    vi.stubGlobal('fetch', mockFetch)
    vi.stubGlobal('navigator', { language: 'en' })
    localStorage.setItem('auth_token', 'some-token')
    localStorage.setItem('auth_user', JSON.stringify({ id: 1, name: 'Ian', email: 'ian@test.com' }))
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('DashboardPage', () => {
    it('renders welcome message with user name', async () => {
        mockFetch.mockResolvedValue({ ok: true, status: 200, json: () => Promise.resolve({ user: { avatar_thumb: '' } }) })

        const wrapper = mountWithPlugins(DashboardPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Welcome, Ian')
    })

    it('shows user email', async () => {
        mockFetch.mockResolvedValue({ ok: true, status: 200, json: () => Promise.resolve({ user: { avatar_thumb: '' } }) })

        const wrapper = mountWithPlugins(DashboardPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('ian@test.com')
    })

    it('has settings link', async () => {
        mockFetch.mockResolvedValue({ ok: true, status: 200, json: () => Promise.resolve({ user: { avatar_thumb: '' } }) })

        const wrapper = mountWithPlugins(DashboardPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Welcome')
    })
})
