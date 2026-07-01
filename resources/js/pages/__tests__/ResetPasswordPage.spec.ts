import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { createRouter, createWebHistory } from 'vue-router'
import { mountWithPlugins } from '@/__tests__/helpers'
import ResetPasswordPage from '@/pages/auth/ResetPasswordPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/reset-password/:token', name: 'reset-password', component: ResetPasswordPage },
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: { template: '<div>Forgot</div>' },
        },
    ],
})

let mockFetch: ReturnType<typeof vi.fn>

beforeEach(() => {
    mockFetch = vi.fn()
    vi.stubGlobal('fetch', mockFetch)
    vi.stubGlobal('navigator', { language: 'en' })
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('ResetPasswordPage', () => {
    it('shows error for invalid token', async () => {
        mockFetch.mockResolvedValue({
            ok: false,
            status: 422,
            json: () => Promise.resolve({ message: 'Invalid token.' }),
        })

        await router.push('/reset-password/invalid-token')

        const wrapper = mountWithPlugins(ResetPasswordPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Request a new link')
    })

    it('shows reset form when token is valid', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.resolve({ email: 'test@test.com', has_totp: false }),
        })

        await router.push('/reset-password/valid-token')

        const wrapper = mountWithPlugins(ResetPasswordPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Set new password')
    })

    it('shows totp input when user has totp', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.resolve({ email: 'test@test.com', has_totp: true }),
        })

        await router.push('/reset-password/valid-token')

        const wrapper = mountWithPlugins(ResetPasswordPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 100))

        expect(wrapper.text()).toContain('reset your password')
    })

    it('shows success message after reset', async () => {
        mockFetch
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({ email: 'test@test.com', has_totp: false }),
            })
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({ message: 'Password reset.' }),
            })

        await router.push('/reset-password/valid-token')

        const wrapper = mountWithPlugins(ResetPasswordPage, { global: { plugins: [router] } })
        await new Promise((r) => setTimeout(r, 50))

        const inputs = wrapper.findAll('input[type="password"]')
        for (const input of inputs) {
            await input.setValue('newpassword')
        }
        await wrapper.find('form').trigger('submit')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Your password has been reset')
    })
})
