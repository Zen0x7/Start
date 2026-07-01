import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { mountWithPlugins } from '@/__tests__/helpers'
import LoginPage from '@/pages/auth/LoginPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
        { path: '/register', name: 'register', component: { template: '<div>Register</div>' } },
        { path: '/totp/verify', name: 'totp-verify', component: { template: '<div>TOTP</div>' } },
        { path: '/totp/setup', name: 'totp-setup', component: { template: '<div>Setup</div>' } },
        { path: '/email/verify', name: 'verify-email', component: { template: '<div>Verify</div>' } },
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

describe('LoginPage', () => {
    it('renders login form', () => {
        const wrapper = mountWithPlugins(LoginPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Sign In')
    })

    it('links to register page', () => {
        const wrapper = mountWithPlugins(LoginPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Create Account')
    })

    it('shows error on wrong credentials', async () => {
        mockFetch.mockResolvedValue({
            ok: false,
            status: 422,
            json: () =>
                Promise.resolve({
                    message: 'Las credenciales proporcionadas son incorrectas.',
                    errors: { email: ['Credenciales incorrectas.'] },
                }),
        })

        const wrapper = mountWithPlugins(LoginPage, {
            global: { plugins: [router] },
        })

        const inputs = wrapper.findAll('input')
        await inputs[0].setValue('test@test.com')
        await inputs[1].setValue('wrong')
        await wrapper.find('form').trigger('submit.prevent')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Credenciales incorrectas.')
    })

    it('redirects to totp-setup when setup_required', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            status: 200,
            json: () =>
                Promise.resolve({
                    totp_status: 'setup_required',
                    temp_token: 'challenge',
                    user: { id: 1, name: 'Ian', email: 'ian@test.com' },
                }),
        })

        const wrapper = mountWithPlugins(LoginPage, {
            global: { plugins: [router] },
        })

        const inputs = wrapper.findAll('input')
        await inputs[0].setValue('ian@test.com')
        await inputs[1].setValue('password')
        await wrapper.find('form').trigger('submit')
        await new Promise((r) => setTimeout(r, 100))

        expect(router.currentRoute.value.name).toBe('totp-setup')
    })

    it('redirects to verify-email when email not verified', async () => {
        mockFetch.mockResolvedValue({
            ok: false,
            status: 403,
            json: () =>
                Promise.resolve({
                    message: 'Antes de continuar deberás confirmar tu correo electrónico.',
                    email: 'unverified@test.com',
                }),
        })

        const wrapper = mountWithPlugins(LoginPage, {
            global: { plugins: [router] },
        })

        const inputs = wrapper.findAll('input')
        await inputs[0].setValue('unverified@test.com')
        await inputs[1].setValue('password')
        await wrapper.find('form').trigger('submit')
        await new Promise((r) => setTimeout(r, 100))

        expect(router.currentRoute.value.name).toBe('verify-email')
    })
})
