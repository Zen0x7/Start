import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mountWithPlugins } from '@/__tests__/helpers'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import TotpVerifyPage from '@/pages/auth/TotpVerifyPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/totp/verify', name: 'totp-verify', component: TotpVerifyPage },
        { path: '/dashboard', name: 'dashboard', component: { template: '<div>Dashboard</div>' } },
    ],
})

let mockFetch: ReturnType<typeof vi.fn>

beforeEach(async () => {
    setActivePinia(createPinia())
    mockFetch = vi.fn()
    vi.stubGlobal('fetch', mockFetch)
    await router.push('/totp/verify?temp_token=challenge-token')
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('TotpVerifyPage', () => {
    it('renders title and description', () => {
        const wrapper = mountWithPlugins(TotpVerifyPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Two-Factor Authentication')
    })

    it('renders code input', () => {
        const wrapper = mountWithPlugins(TotpVerifyPage, {
            global: { plugins: [router] },
        })
        // PvInputOtp renders multiple input elements for OTP
        expect(wrapper.findAll('input').length).toBeGreaterThanOrEqual(1)
    })

    it('shows error on invalid code', async () => {
        mockFetch.mockResolvedValue({
            ok: false,
            status: 403,
            json: () => Promise.resolve({ message: 'The entered TOTP code is not valid.' }),
        })

        const wrapper = mountWithPlugins(TotpVerifyPage, {
            global: { plugins: [router] },
        })
        // Fill all OTP inputs with digits
        const inputs = wrapper.findAll('input')
        for (let i = 0; i < inputs.length; i++) {
            await inputs[i].setValue(String((i + 1) % 10))
        }
        await wrapper.find('form').trigger('submit')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('The entered TOTP code is not valid.')
    })

    it('redirects to dashboard on success', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.resolve({ token: 'auth-jwt', user: { id: 1, name: 'Ian', email: 'ian@test.com' } }),
        })

        const wrapper = mountWithPlugins(TotpVerifyPage, {
            global: { plugins: [router] },
        })

        // Set totpCode via the component's v-model through the wrapper
        const inputs = wrapper.findAll('input')
        for (let i = 0; i < inputs.length; i++) {
            await inputs[i].setValue(String((i + 1) % 10))
        }
        await wrapper.find('form').trigger('submit')
        await new Promise((r) => setTimeout(r, 200))

        expect(router.currentRoute.value.name).toBe('dashboard')
    })
})
