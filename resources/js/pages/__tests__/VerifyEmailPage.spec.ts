import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import VerifyEmailPage from '@/pages/auth/VerifyEmailPage.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
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

describe('VerifyEmailPage', () => {
    it('renders waiting message', () => {
        const wrapper = mount(VerifyEmailPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain(
            'Antes de continuar deberás confirmar tu correo electrónico',
        )
    })

    it('renders resend button', () => {
        const wrapper = mount(VerifyEmailPage, {
            global: { plugins: [router] },
        })
        expect(wrapper.text()).toContain('Reenviar correo de verificación')
    })

    it('shows success message after resend', async () => {
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            status: 200,
            json: () =>
                Promise.resolve({
                    message: 'Correo de verificación reenviado.',
                }),
        } as Response)

        await router.push('/email/verify?email=test@example.com')

        const wrapper = mount(VerifyEmailPage, {
            global: { plugins: [router] },
        })

        await wrapper.find('button').trigger('click')
        await new Promise((r) => setTimeout(r, 50))

        expect(wrapper.text()).toContain('test@example.com')
        expect(wrapper.text()).toContain('Correo de verificación reenviado.')
    })
})
