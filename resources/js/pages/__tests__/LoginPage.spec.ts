import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
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

beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('LoginPage', () => {
    it('renders login form', () => {
        const wrapper = mount(LoginPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Iniciar Sesión')
        expect(wrapper.find('#email').exists()).toBe(true)
        expect(wrapper.find('#password').exists()).toBe(true)
    })

    it('links to register page', () => {
        const wrapper = mount(LoginPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Registrarse')
    })

    it('shows error on wrong credentials', async () => {
        vi.mocked(fetch).mockResolvedValue({
            ok: false,
            status: 422,
            json: () =>
                Promise.resolve({
                    message: 'Las credenciales proporcionadas son incorrectas.',
                    errors: { email: ['Credenciales incorrectas.'] },
                }),
        } as Response)

        const wrapper = mount(LoginPage, {
            global: { plugins: [router] },
        })

        await wrapper.find('#email').setValue('test@test.com')
        await wrapper.find('#password').setValue('wrong')
        await wrapper.find('form').trigger('submit.prevent')

        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Credenciales incorrectas.')
    })
})
