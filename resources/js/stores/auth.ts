import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/services/api'

interface User {
    id: number
    name: string
    email: string
}

export const useAuthStore = defineStore('auth', () => {
    const token = ref<string | null>(localStorage.getItem('auth_token'))
    const user = ref<User | null>(
        JSON.parse(localStorage.getItem('auth_user') ?? 'null'),
    )

    const isAuthenticated = computed(() => token.value !== null)
    const currentUser = computed(() => user.value)

    function setSession(newToken: string, newUser: User) {
        token.value = newToken
        user.value = newUser
        localStorage.setItem('auth_token', newToken)
        localStorage.setItem('auth_user', JSON.stringify(newUser))
    }

    function clearSession() {
        token.value = null
        user.value = null
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
    }

    async function register(
        name: string,
        email: string,
        password: string,
        passwordConfirmation: string,
    ): Promise<{ email: string }> {
        const res = await api.post<{ email: string }>('/auth/register', {
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
        })
        return { email: (res as unknown as { email: string }).email ?? email }
    }

    async function login(
        email: string,
        password: string,
    ): Promise<{ verified: boolean; token?: string; user?: User }> {
        try {
            const res = await api.post<{ token: string; user: User }>(
                '/auth/login',
                { email, password },
            )
            const data = res as unknown as { token: string; user: User }
            setSession(data.token, data.user)
            return { verified: true, token: data.token, user: data.user }
        } catch (err: unknown) {
            const error = err as Error & {
                status: number
                data: { message?: string; email?: string }
            }
            if (error.status === 403) {
                return { verified: false }
            }
            throw err
        }
    }

    async function verifyEmail(
        token: string,
        password: string,
    ): Promise<{ token: string; user: User }> {
        const res = await api.post<{ token: string; user: User }>(
            '/auth/verify-email',
            { token, password },
        )
        const data = res as unknown as { token: string; user: User }
        setSession(data.token, data.user)
        return { token: data.token, user: data.user }
    }

    function logout() {
        clearSession()
    }

    return {
        token,
        user,
        isAuthenticated,
        currentUser,
        register,
        login,
        verifyEmail,
        logout,
        clearSession,
    }
})
