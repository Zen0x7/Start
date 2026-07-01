import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { api } from '@/services/api'

function mockFetch(status: number, body: unknown) {
    const mock = vi.fn().mockResolvedValue({
        ok: status >= 200 && status < 300,
        status,
        json: () => Promise.resolve(body),
    })
    vi.stubGlobal('fetch', mock)
    return mock
}

beforeEach(() => {
    localStorage.clear()
    vi.stubGlobal('navigator', { language: 'en' })
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('api', () => {
    it('makes GET request', async () => {
        const mock = mockFetch(200, { data: 'hello' })

        const result = await api.get('/test')

        expect(mock).toHaveBeenCalledWith(
            '/api/test',
            expect.objectContaining({
                method: 'GET',
                headers: expect.objectContaining({ Accept: 'application/json' }),
            }),
        )
        expect(result).toEqual({ data: 'hello' })
    })

    it('makes POST request with body', async () => {
        const mock = mockFetch(201, { id: 1 })

        const result = await api.post('/test', { name: 'ian' })

        expect(mock).toHaveBeenCalledWith(
            '/api/test',
            expect.objectContaining({
                method: 'POST',
                headers: expect.objectContaining({
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                }),
                body: JSON.stringify({ name: 'ian' }),
            }),
        )
        expect(result).toEqual({ id: 1 })
    })

    it('includes auth token in headers', async () => {
        localStorage.setItem('auth_token', 'test-token-123')
        const mock = mockFetch(200, {})

        await api.get('/test')

        expect(mock).toHaveBeenCalledWith(
            '/api/test',
            expect.objectContaining({
                method: 'GET',
                headers: expect.objectContaining({
                    Accept: 'application/json',
                    Authorization: 'Bearer test-token-123',
                }),
            }),
        )
    })

    it('makes PUT request', async () => {
        const mock = mockFetch(200, { updated: true })

        const result = await api.put('/test/1', { name: 'new' })

        expect(mock).toHaveBeenCalledWith(
            '/api/test/1',
            expect.objectContaining({
                method: 'PUT',
                headers: expect.objectContaining({
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                }),
                body: JSON.stringify({ name: 'new' }),
            }),
        )
        expect(result).toEqual({ updated: true })
    })

    it('makes DELETE request', async () => {
        const mock = mockFetch(200, { deleted: true })

        const result = await api.delete('/test/1')

        expect(mock).toHaveBeenCalledWith(
            '/api/test/1',
            expect.objectContaining({
                method: 'DELETE',
                headers: expect.objectContaining({ Accept: 'application/json' }),
            }),
        )
        expect(result).toEqual({ deleted: true })
    })

    it('throws error with message on failure', async () => {
        mockFetch(422, {
            message: 'Validation failed',
            errors: { email: ['Required'] },
        })

        try {
            await api.post('/test', {})
            expect.unreachable()
        } catch (err: unknown) {
            const e = err as Error & { status: number; data: unknown }
            expect(e.status).toBe(422)
            expect(e.message).toBe('Validation failed')
        }
    })

    it('throws plain error when no message', async () => {
        mockFetch(500, {})

        try {
            await api.get('/test')
            expect.unreachable()
        } catch (err: unknown) {
            const e = err as Error
            expect(e.message).toBe('Request failed')
        }
    })
})
