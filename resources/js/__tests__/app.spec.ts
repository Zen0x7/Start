import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('App bootstrap', () => {
    it('creates and mounts the app to #app', async () => {
        const div = document.createElement('div')
        div.id = 'app'
        document.body.appendChild(div)

        const { createApplication } = await import('@/app')
        const app = createApplication()
        app.mount('#app')

        expect(div.innerHTML).not.toBe('')
        app.unmount()
        document.body.removeChild(div)
    })
})
