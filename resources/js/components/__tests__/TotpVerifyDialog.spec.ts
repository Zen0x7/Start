import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TotpVerifyDialog from '@/components/TotpVerifyDialog.vue'

beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('TotpVerifyDialog', () => {
    it('renders title and description', () => {
        const wrapper = mount(TotpVerifyDialog)
        expect(wrapper.text()).toContain('Confirmar Operación')
        expect(wrapper.text()).toContain('Ingresa tu código TOTP')
    })

    it('renders input and buttons', () => {
        const wrapper = mount(TotpVerifyDialog)
        expect(wrapper.find('input').exists()).toBe(true)
        expect(wrapper.text()).toContain('Cancelar')
        expect(wrapper.text()).toContain('Confirmar')
    })

    it('emits cancel when cancel button clicked', async () => {
        const wrapper = mount(TotpVerifyDialog)
        const buttons = wrapper.findAll('button')
        await buttons[0].trigger('click')
        expect(wrapper.emitted('cancel')).toBeTruthy()
    })

    it('disables confirm button when code is not 6 digits', () => {
        const wrapper = mount(TotpVerifyDialog)
        const confirmBtn = wrapper.findAll('button')[1]
        expect(confirmBtn.attributes('disabled')).toBeDefined()
    })

    it('calls API on submit with valid code', async () => {
        const mockFetch = vi.mocked(fetch).mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.resolve({ message: 'ok' }),
        } as Response)

        const wrapper = mount(TotpVerifyDialog)
        const input = wrapper.find('input')
        await input.setValue('123456')
        await wrapper.find('form').trigger('submit')

        expect(mockFetch).toHaveBeenCalledWith(
            '/api/auth/totp/confirm-action',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({
                    totp_code: '123456',
                    action: 'confirm_operation',
                }),
            }),
        )
    })

    it('emits verified on successful API response', async () => {
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.resolve({ message: 'ok' }),
        } as Response)

        const wrapper = mount(TotpVerifyDialog)
        const input = wrapper.find('input')
        await input.setValue('123456')
        await wrapper.find('form').trigger('submit')

        await new Promise((r) => setTimeout(r, 50))
        expect(wrapper.emitted('verified')).toBeTruthy()
    })
})
