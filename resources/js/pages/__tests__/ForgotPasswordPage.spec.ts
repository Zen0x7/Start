import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { createRouter, createWebHistory } from 'vue-router'
import { mountWithPlugins } from '@/__tests__/helpers'
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordPage },
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
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

describe('ForgotPasswordPage', () => {
    it('renders forgot password form', () => {
        const wrapper = mountWithPlugins(ForgotPasswordPage, { global: { plugins: [router] } })
        expect(wrapper.text()).toContain('Forgot password')
        expect(wrapper.find('input').exists()).toBe(true)
    })

    it('shows success message after submitting', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.resolve({ message: 'Sent' }),
        })

        const wrapper = mountWithPlugins(ForgotPasswordPage, { global: { plugins: [router] } })
        const input = wrapper.find('input')
        await input.setValue('test@test.com')
        await wrapper.find('form').trigger('submit')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Check your email')
    })

    it('shows error on failure', async () => {
        mockFetch.mockResolvedValue({
            ok: false,
            status: 422,
            json: () => Promise.resolve({ message: 'Email not found.' }),
        })

        const wrapper = mountWithPlugins(ForgotPasswordPage, { global: { plugins: [router] } })
        const input = wrapper.find('input')
        await input.setValue('test@test.com')
        await wrapper.find('form').trigger('submit')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Email not found.')
    })
})
