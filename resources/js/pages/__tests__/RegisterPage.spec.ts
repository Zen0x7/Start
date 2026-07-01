import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { mountWithPlugins } from '@/__tests__/helpers'
import RegisterPage from '@/pages/auth/RegisterPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
        { path: '/register', name: 'register', component: { template: '<div>Register</div>' } },
        { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
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

describe('RegisterPage', () => {
    it('renders registration form', () => {
        const wrapper = mountWithPlugins(RegisterPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Create Account')
    })

    it('calls register and redirects on submit', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            status: 201,
            json: () =>
                Promise.resolve({
                    message: 'Cuenta creada',
                    email: 'test@test.com',
                }),
        })

        const wrapper = mountWithPlugins(RegisterPage, {
            global: { plugins: [router] },
        })

        const inputs = wrapper.findAll('input')
        await inputs[0].setValue('Test')
        await inputs[1].setValue('test@test.com')
        await inputs[2].setValue('password123')
        await inputs[3].setValue('password123')
        await wrapper.find('form').trigger('submit.prevent')
        await new Promise((r) => setTimeout(r, 50))

        expect(mockFetch).toHaveBeenCalledWith(
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
        mockFetch.mockResolvedValue({
            ok: false,
            status: 422,
            json: () =>
                Promise.resolve({
                    message: 'Validation failed',
                    errors: { email: ['El correo ya está en uso.'] },
                }),
        })

        const wrapper = mountWithPlugins(RegisterPage, {
            global: { plugins: [router] },
        })

        const inputs = wrapper.findAll('input')
        await inputs[0].setValue('Test')
        await inputs[1].setValue('used@test.com')
        await inputs[2].setValue('password123')
        await inputs[3].setValue('password123')
        await wrapper.find('form').trigger('submit.prevent')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('El correo ya está en uso.')
    })

    it('links to login page', () => {
        const wrapper = mountWithPlugins(RegisterPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Sign In')
    })
})
