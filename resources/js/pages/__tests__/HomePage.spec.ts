import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mountWithPlugins } from '@/__tests__/helpers'
import HomePage from '@/pages/HomePage.vue'

describe('HomePage', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.stubGlobal('fetch', vi.fn())
    })

    afterEach(() => {
        vi.unstubAllGlobals()
    })

    it('renders welcome title', () => {
        const wrapper = mountWithPlugins(HomePage)
        expect(wrapper.text()).toContain('Welcome')
    })

    it('shows login and register links when not authenticated', () => {
        const wrapper = mountWithPlugins(HomePage)
        expect(wrapper.text()).toContain('Create Account')
        expect(wrapper.text()).toContain('Sign In')
    })

    it('shows dashboard link when authenticated', () => {
        localStorage.setItem('auth_token', 'some-token')

        const wrapper = mountWithPlugins(HomePage)
        expect(wrapper.text()).toContain('Go to Dashboard')
        expect(wrapper.text()).not.toContain('Create Account')
    })
})
