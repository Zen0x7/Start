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
        const result = await auth.register(
            'Ian',
            'ian@example.com',
            'secret123',
            'secret123',
        )

        expect(result.email).toBe('ian@example.com')
        expect(auth.isAuthenticated).toBe(false)

        expect(mock).toHaveBeenCalledWith('/api/auth/register', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: 'Ian',
                email: 'ian@example.com',
                password: 'secret123',
                password_confirmation: 'secret123',
            }),
        })
    })

    it('login with verified user saves token and user', async () => {
        mockFetch(200, {
            token: 'jwt-token',
            user: { id: 1, name: 'Ian', email: 'ian@example.com' },
        })

        const auth = useAuthStore()
        const result = await auth.login('ian@example.com', 'secret123')

        expect(result.verified).toBe(true)
        expect(auth.isAuthenticated).toBe(true)
        expect(auth.currentUser?.name).toBe('Ian')
        expect(localStorage.getItem('auth_token')).toBe('jwt-token')
    })

    it('login with unverified user returns verified=false', async () => {
        mockFetch(403, {
            message:
                'Antes de continuar deberás confirmar tu correo electrónico.',
        })

        const auth = useAuthStore()
        const result = await auth.login('ian@example.com', 'secret123')

        expect(result.verified).toBe(false)
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

    it('verifyEmail saves token and user', async () => {
        mockFetch(200, {
            message: 'Correo electrónico confirmado exitosamente.',
            token: 'auth-jwt',
            user: { id: 1, name: 'Ian', email: 'ian@example.com' },
        })

        const auth = useAuthStore()
        const result = await auth.verifyEmail('verification-token', 'secret123')

        expect(result.token).toBe('auth-jwt')
        expect(result.user.email).toBe('ian@example.com')
        expect(auth.isAuthenticated).toBe(true)
        expect(localStorage.getItem('auth_token')).toBe('auth-jwt')
    })

    it('logout clears session', () => {
        localStorage.setItem('auth_token', 'some-token')
        localStorage.setItem(
            'auth_user',
            JSON.stringify({ id: 1, name: 'Ian', email: 'i@i.com' }),
        )

        const auth = useAuthStore()
        auth.logout()

        expect(auth.isAuthenticated).toBe(false)
        expect(auth.currentUser).toBeNull()
        expect(localStorage.getItem('auth_token')).toBeNull()
    })

    it('restores session from localStorage', () => {
        localStorage.setItem('auth_token', 'persisted-token')
        localStorage.setItem(
            'auth_user',
            JSON.stringify({ id: 1, name: 'Ian', email: 'i@i.com' }),
        )

        const auth = useAuthStore()
        expect(auth.isAuthenticated).toBe(true)
        expect(auth.currentUser?.name).toBe('Ian')
        expect(auth.token).toBe('persisted-token')
    })
})
