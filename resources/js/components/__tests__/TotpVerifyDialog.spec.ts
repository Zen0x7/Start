import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mountWithPlugins } from '@/__tests__/helpers'
import TotpVerifyDialog from '@/components/TotpVerifyDialog.vue'

let mockFetch: ReturnType<typeof vi.fn>

beforeEach(() => {
    mockFetch = vi.fn()
    vi.stubGlobal('fetch', mockFetch)
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('TotpVerifyDialog', () => {
    it('renders title and description', () => {
        const wrapper = mountWithPlugins(TotpVerifyDialog)
        expect(wrapper.text()).toContain('Confirm Operation')
        expect(wrapper.text()).toContain('Enter your TOTP code')
    })

    it('renders input and buttons', () => {
        const wrapper = mountWithPlugins(TotpVerifyDialog)
        expect(wrapper.find('input').exists()).toBe(true)
        expect(wrapper.text()).toContain('Cancel')
        expect(wrapper.text()).toContain('Confirm')
    })

    it('emits cancel when cancel button clicked', async () => {
        const wrapper = mountWithPlugins(TotpVerifyDialog)

        const cancelButtons = wrapper.findAll('button')
        const closeBtn = cancelButtons[0]
        await closeBtn.trigger('click')
        expect(wrapper.emitted('cancel')).toBeTruthy()
    })

    it('calls API on submit with valid code', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.resolve({ message: 'ok' }),
        })

        const wrapper = mountWithPlugins(TotpVerifyDialog)
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
        mockFetch.mockResolvedValue({
            ok: true,
            status: 200,
            json: () => Promise.resolve({ message: 'ok' }),
        })

        const wrapper = mountWithPlugins(TotpVerifyDialog)
        const input = wrapper.find('input')
        await input.setValue('123456')
        await wrapper.find('form').trigger('submit')

        await new Promise((r) => setTimeout(r, 50))
        expect(wrapper.emitted('verified')).toBeTruthy()
    })
})
