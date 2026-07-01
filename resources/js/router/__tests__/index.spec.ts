import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import router from '@/router'

beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('Router guards', () => {
    it('redirects to login when unauthenticated and accessing dashboard', async () => {
        await router.push('/dashboard')
        await new Promise((r) => setTimeout(r, 50))
        expect(router.currentRoute.value.name).toBe('login')
    })

    it('redirects to login when unauthenticated and accessing root', async () => {
        await router.push('/')
        await new Promise((r) => setTimeout(r, 50))
        expect(router.currentRoute.value.name).toBe('login')
    })
})
