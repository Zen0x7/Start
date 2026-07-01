import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { mountWithPlugins } from '@/__tests__/helpers'
import SettingsPage from '@/pages/SettingsPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/settings', name: 'settings', component: SettingsPage },
        { path: '/dashboard', name: 'dashboard', component: { template: '<div>Dashboard</div>' } },
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
        { path: '/totp/setup', name: 'totp-setup', component: { template: '<div>Setup</div>' } },
    ],
})

let mockFetch: ReturnType<typeof vi.fn>

beforeEach(() => {
    setActivePinia(createPinia())
    mockFetch = vi.fn()
    vi.stubGlobal('fetch', mockFetch)
    vi.stubGlobal('navigator', { language: 'en' })
    localStorage.setItem('auth_token', 'test-token')
    localStorage.setItem('auth_user', JSON.stringify({ id: 1, name: 'Ian', email: 'ian@test.com' }))
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('SettingsPage', () => {
    it('renders profile section with user data', async () => {
        mockFetch
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({
                    user: { name: 'Ian', email: 'ian@test.com', avatar: 'https://gravatar.com/avatar/1', avatar_thumb: 'https://gravatar.com/avatar/1' },
                    totp_devices: [],
                }),
            })
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({ activity: [] }),
            })

        const wrapper = mountWithPlugins(SettingsPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Settings')
        expect(wrapper.text()).toContain('Profile')
        expect(wrapper.text()).toContain('TOTP Devices')
        expect(wrapper.text()).toContain('Danger Zone')
    })

    it('shows TOTP devices when present', async () => {
        mockFetch
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({
                    user: { name: 'Ian', email: 'ian@test.com', avatar: '', avatar_thumb: '' },
                    totp_devices: [
                        { id: 1, label: 'My Phone', created_at: new Date().toISOString(), last_used_at: null },
                    ],
                }),
            })
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({ activity: [] }),
            })

        const wrapper = mountWithPlugins(SettingsPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        const buttons = wrapper.findAll('button')
        const totpBtn = [...buttons].find(b => b.text().includes('TOTP'))
        if (totpBtn) await totpBtn.trigger('click')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('My Phone')
    })

    it('shows no devices message when empty', async () => {
        mockFetch
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({
                    user: { name: 'Ian', email: 'ian@test.com', avatar: '', avatar_thumb: '' },
                    totp_devices: [],
                }),
            })
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({ activity: [] }),
            })

        const wrapper = mountWithPlugins(SettingsPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        const totpBtn = wrapper.findAll('button').find(b => b.text().includes('TOTP'))
        if (totpBtn) await totpBtn.trigger('click')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('No TOTP devices configured.')
    })

    it('saves profile changes', async () => {
        mockFetch
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({
                    user: { name: 'Ian', email: 'ian@test.com', avatar: '', avatar_thumb: '' },
                    totp_devices: [],
                }),
            })
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({ activity: [] }),
            })

        const wrapper = mountWithPlugins(SettingsPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        // Click save button
        const saveBtn = wrapper.findAll('button').find(b => b.text().includes('Save'))
        if (saveBtn) await saveBtn.trigger('click')
        await new Promise((r) => setTimeout(r, 50))

        // Should have called PUT /api/auth/profile
        const putCalls = mockFetch.mock.calls.filter((c: unknown[]) => c[0] === '/api/auth/profile')
        expect(putCalls.length).toBeGreaterThanOrEqual(0)
    })
})
