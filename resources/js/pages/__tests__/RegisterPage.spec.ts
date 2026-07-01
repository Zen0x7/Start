import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import RegisterPage from '@/pages/auth/RegisterPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
        { path: '/register', name: 'register', component: { template: '<div>Register</div>' } },
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

describe('RegisterPage', () => {
    it('renders registration form', () => {
        const wrapper = mount(RegisterPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Crear Cuenta')
        expect(wrapper.find('#name').exists()).toBe(true)
        expect(wrapper.find('#email').exists()).toBe(true)
        expect(wrapper.find('#password').exists()).toBe(true)
        expect(wrapper.find('#password-confirmation').exists()).toBe(true)
    })

    it('calls register and redirects on submit', async () => {
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            status: 201,
            json: () =>
                Promise.resolve({
                    message: 'Cuenta creada',
                    email: 'test@test.com',
                }),
        } as Response)

        const wrapper = mount(RegisterPage, {
            global: { plugins: [router] },
        })

        await wrapper.find('#name').setValue('Test')
        await wrapper.find('#email').setValue('test@test.com')
        await wrapper.find('#password').setValue('password123')
        await wrapper.find('#password-confirmation').setValue('password123')
        await wrapper.find('form').trigger('submit.prevent')

        await new Promise((r) => setTimeout(r, 50))

        expect(fetch).toHaveBeenCalledWith(
            '/api/auth/register',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({
                    name: 'Test',
                    email: 'test@test.com',
                    password: 'password123',
                    password_confirmation: 'password123',
                }),
            }),
        )
    })

    it('shows error on failed registration', async () => {
        vi.mocked(fetch).mockResolvedValue({
            ok: false,
            status: 422,
            json: () =>
                Promise.resolve({
                    message: 'Validation failed',
                    errors: { email: ['El correo ya está en uso.'] },
                }),
        } as Response)

        const wrapper = mount(RegisterPage, {
            global: { plugins: [router] },
        })

        await wrapper.find('#name').setValue('Test')
        await wrapper.find('#email').setValue('used@test.com')
        await wrapper.find('#password').setValue('password123')
        await wrapper.find('#password-confirmation').setValue('password123')
        await wrapper.find('form').trigger('submit.prevent')

        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('El correo ya está en uso.')
    })

    it('links to login page', () => {
        const wrapper = mount(RegisterPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Iniciar Sesión')
    })
})
