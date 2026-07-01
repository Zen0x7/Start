import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { mountWithPlugins } from '@/__tests__/helpers'
import VerifyEmailPage from '@/pages/auth/VerifyEmailPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
        { path: '/register', name: 'register', component: { template: '<div>Register</div>' } },
        {
            path: '/email/verify',
            name: 'verify-email',
            component: { template: '<div>Verify</div>' },
        },
    ],
})

let mockFetch: ReturnType<typeof vi.fn>

beforeEach(() => {
    setActivePinia(createPinia())
    mockFetch = vi.fn()
    vi.stubGlobal('fetch', mockFetch)
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('VerifyEmailPage', () => {
    it('renders waiting message', () => {
        const wrapper = mountWithPlugins(VerifyEmailPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('We sent a confirmation link to')
    })

    it('renders resend button', () => {
        const wrapper = mountWithPlugins(VerifyEmailPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Resend')
    })

    it('shows success message after resend', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            status: 200,
            json: () =>
                Promise.resolve({
                    message: 'Link resent.',
                }),
        })

        await router.push('/email/verify?email=test@example.com')

        const wrapper = mountWithPlugins(VerifyEmailPage, {
            global: { plugins: [router] },
        })

        // PvButton renders a button element inside
        const btn = wrapper.find('button')
        await btn.trigger('click')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('test@example.com')
        expect(wrapper.text()).toContain('Link resent.')
    })

    it('shows error when resend fails', async () => {
        mockFetch.mockResolvedValue({
            ok: false,
            status: 429,
            json: () =>
                Promise.resolve({
                    message: 'Too many requests.',
                }),
        })

        await router.push('/email/verify?email=test@example.com')

        const wrapper = mountWithPlugins(VerifyEmailPage, {
            global: { plugins: [router] },
        })

        const btn = wrapper.find('button')
        await btn.trigger('click')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Too many requests.')
    })
})
