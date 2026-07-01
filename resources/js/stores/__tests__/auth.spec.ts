import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

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
    setActivePinia(createPinia())
    localStorage.clear()
    vi.stubGlobal('navigator', { language: 'en' })
})

afterEach(() => {
    vi.unstubAllGlobals()
})

describe('auth store', () => {
    it('starts without authentication', () => {
        const auth = useAuthStore()
        expect(auth.isAuthenticated).toBe(false)
        expect(auth.currentUser).toBeNull()
    })

    it('register calls API and returns email', async () => {
        const mock = mockFetch(201, {
            message: 'Cuenta creada',
            email: 'ian@example.com',
        })

        const auth = useAuthStore()
        const result = await auth.register('Ian', 'ian@example.com', 'secret123', 'secret123')

        expect(result.email).toBe('ian@example.com')
        expect(auth.isAuthenticated).toBe(false)

        expect(mock).toHaveBeenCalledWith(
            '/api/auth/register',
            expect.objectContaining({
                method: 'POST',
                headers: expect.objectContaining({
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                }),
                body: JSON.stringify({
                    name: 'Ian',
                    email: 'ian@example.com',
                    password: 'secret123',
                    password_confirmation: 'secret123',
                }),
            }),
        )
    })

    it('login returns totp_status and temp_token', async () => {
        mockFetch(200, {
            totp_status: 'verify_required',
            temp_token: 'challenge-token',
            user: { id: 1, name: 'Ian', email: 'ian@example.com' },
        })

        const auth = useAuthStore()
        const result = await auth.login('ian@example.com', 'secret123')

        const r = result as { totp_status: string; temp_token: string; user: { name: string } }
        expect(r.totp_status).toBe('verify_required')
        expect(r.temp_token).toBe('challenge-token')
        expect(r.user.name).toBe('Ian')
        expect(auth.isAuthenticated).toBe(false)
    })

    it('login with unverified user returns email', async () => {
        mockFetch(403, {
            message: 'Antes de continuar deberás confirmar tu correo electrónico.',
        })

        const auth = useAuthStore()
        const result = await auth.login('ian@example.com', 'secret123')

        const r = result as { email: string }
        expect(r.email).toBeDefined()
        expect(auth.isAuthenticated).toBe(false)
    })

    it('login throws on wrong credentials', async () => {
        mockFetch(422, {
            message: 'Las credenciales proporcionadas son incorrectas.',
            errors: {
                email: ['Las credenciales proporcionadas son incorrectas.'],
            },
        })

        const auth = useAuthStore()

        try {
            await auth.login('ian@example.com', 'wrong')
            expect.unreachable()
        } catch {
            expect(auth.isAuthenticated).toBe(false)
        }
    })

    it('verifyTotp saves token and user', async () => {
        mockFetch(200, {
            token: 'auth-jwt',
            user: { id: 1, name: 'Ian', email: 'ian@example.com' },
        })

        const auth = useAuthStore()
        await auth.verifyTotp('challenge-token', '123456')

        expect(auth.isAuthenticated).toBe(true)
        expect(auth.currentUser?.name).toBe('Ian')
        expect(auth.token).toBe('auth-jwt')
    })

    it('logout clears session', () => {
        localStorage.setItem('auth_token', 'some-token')
        localStorage.setItem('auth_user', JSON.stringify({ id: 1, name: 'Ian', email: 'i@i.com' }))

        const auth = useAuthStore()
        auth.logout()

        expect(auth.isAuthenticated).toBe(false)
        expect(auth.currentUser).toBeNull()
        expect(auth.token).toBeNull()
    })

    it('restores session from localStorage', () => {
        localStorage.setItem('auth_token', 'persisted-token')
        localStorage.setItem('auth_user', JSON.stringify({ id: 1, name: 'Ian', email: 'i@i.com' }))

        const auth = useAuthStore()
        expect(auth.isAuthenticated).toBe(true)
        expect(auth.currentUser?.name).toBe('Ian')
        expect(auth.token).toBe('persisted-token')
    })
})
