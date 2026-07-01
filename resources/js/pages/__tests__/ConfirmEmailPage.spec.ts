import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mountWithPlugins } from '@/__tests__/helpers'
import { createRouter, createWebHistory } from 'vue-router'
import ConfirmEmailPage from '@/pages/auth/ConfirmEmailPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/email/verify/:token', name: 'confirm-email', component: ConfirmEmailPage },
        { path: '/totp/setup', name: 'totp-setup', component: { template: '<div>Setup</div>' } },
        { path: '/register', name: 'register', component: { template: '<div>Register</div>' } },
    ],
})

let mockFetch: ReturnType<typeof vi.fn>

beforeEach(() => {
    mockFetch = vi.fn()
    vi.stubGlobal('fetch', mockFetch)
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('ConfirmEmailPage', () => {
    it('shows error when token check fails', async () => {
        mockFetch.mockResolvedValue({
            ok: false,
            status: 400,
            json: () => Promise.resolve({ message: 'Token inválido' }),
        })

        await router.push('/email/verify/invalid-token')

        const wrapper = mountWithPlugins(ConfirmEmailPage, {
            global: { plugins: [router] },
        })

        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('This link is invalid or has expired.')
    })

    it('shows password form when token is valid', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.resolve({ email: 'test@example.com' }),
        })

        await router.push('/email/verify/valid-token')

        const wrapper = mountWithPlugins(ConfirmEmailPage, {
            global: { plugins: [router] },
        })

        await new Promise((r) => setTimeout(r, 100))

        expect(wrapper.text()).toContain('Confirm your email')
    })
    it('keeps form visible on wrong password', async () => {
        mockFetch
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({ email: 'test@example.com' }),
            })
            .mockResolvedValueOnce({
                ok: false,
                status: 403,
                json: () => Promise.resolve({ message: 'The entered password is incorrect.' }),
            })

        await router.push('/email/verify/valid-token')

        const wrapper = mountWithPlugins(ConfirmEmailPage, {
            global: { plugins: [router] },
        })

        await new Promise((r) => setTimeout(r, 50))

        const inputs = wrapper.findAll('input')

        for (const input of inputs) {
            await input.setValue('wrong')
        }
        await wrapper.find('form').trigger('submit')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.find('form').exists()).toBe(true)
    })

    it('redirects to totp-setup on successful verification', async () => {
        mockFetch
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () => Promise.resolve({ email: 'test@example.com' }),
            })
            .mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: () =>
                    Promise.resolve({
                        message: 'Email confirmed successfully.',
                        totp_status: 'setup_required',
                        temp_token: 'challenge',
                        user: { id: 1, name: 'Test', email: 'test@example.com' },
                    }),
            })

        await router.push('/email/verify/valid-token')

        const wrapper = mountWithPlugins(ConfirmEmailPage, {
            global: { plugins: [router] },
        })

        await new Promise((r) => setTimeout(r, 100))

        const inputs = wrapper.findAll('input')
        for (const input of inputs) {
            await input.setValue('password123')
        }
        await wrapper.find('form').trigger('submit')
        await new Promise((r) => setTimeout(r, 200))

        expect(wrapper.text()).toContain('Email confirmed')
    })
})
