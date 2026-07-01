import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: {
        en: {},
        es: {},
    },
})

beforeEach(() => {
    vi.stubGlobal('navigator', { language: 'en' })
    localStorage.clear()
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('LanguageSwitcher', () => {
    it('renders two language buttons', () => {
        const wrapper = mount(LanguageSwitcher, {
            global: { plugins: [i18n] },
        })
        const buttons = wrapper.findAll('button')
        expect(buttons.length).toBe(2)
        expect(buttons[0].text()).toBe('EN')
        expect(buttons[1].text()).toBe('ES')
    })

    it('highlights active language', () => {
        const wrapper = mount(LanguageSwitcher, {
            global: { plugins: [i18n] },
        })
        const buttons = wrapper.findAll('button')
        expect(buttons[0].classes()).toContain('bg-[#111]')
        expect(buttons[0].classes()).toContain('text-white')
    })

    it('switches locale on click', async () => {
        const wrapper = mount(LanguageSwitcher, {
            global: { plugins: [i18n] },
        })
        const buttons = wrapper.findAll('button')
        await buttons[1].trigger('click')

        expect(buttons[1].classes()).toContain('bg-[#111]')
        expect(buttons[0].classes()).not.toContain('bg-[#111]')
        expect(localStorage.getItem('app_locale')).toBe('es')
    })

    it('has accessible attributes', () => {
        const wrapper = mount(LanguageSwitcher, {
            global: { plugins: [i18n] },
        })
        const group = wrapper.find('[role="radiogroup"]')
        expect(group.exists()).toBe(true)

        const buttons = wrapper.findAll('button')
        expect(buttons[0].attributes('role')).toBe('radio')
        expect(buttons[0].attributes('aria-checked')).toBeDefined()
    })
})
