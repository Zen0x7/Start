import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import { createI18n } from 'vue-i18n'
import App from '@/App.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
    ],
})

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: { en: {} },
})

beforeEach(() => {
    vi.stubGlobal('navigator', { language: 'en' })
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('App', () => {
    it('renders RouterView', () => {
        const wrapper = mount(App, {
            global: { plugins: [router, i18n] },
        })
        expect(wrapper.findComponent({ name: 'RouterView' }).exists()).toBe(true)
    })
})
