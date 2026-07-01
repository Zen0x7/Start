import { defineStore } from 'pinia'
import { computed } from 'vue'
import { useStorage } from '@vueuse/core'
import { api } from '@/services/api'

interface User {
    id: number
    name: string
    email: string
}

interface LoginResponse {
    totp_status: 'setup_required' | 'verify_required'
    temp_token: string
    user: User
}

export const useAuthStore = defineStore('auth', () => {
    const token = useStorage<string | null>('auth_token', null)
    const user = useStorage<User | null>('auth_user', null, undefined, {
        serializer: {
            read: (v) => (v ? JSON.parse(v) : null),
            write: (v) => JSON.stringify(v),
        },
    })

    const isAuthenticated = computed(() => token.value !== null)
    const currentUser = computed(() => user.value)

    function setSession(newToken: string, newUser: User) {
        token.value = newToken
        user.value = newUser
    }

    function clearSession() {
        token.value = null
        user.value = null
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
    ): Promise<
        | { verified: true; token: string; user: User }
        | { verified: false; totp_status?: string; temp_token?: string; user?: User; email?: string }
    > {
        try {
            const res = await api.post<LoginResponse>('/auth/login', { email, password })
            const data = res as unknown as LoginResponse

            return {
                verified: false,
                totp_status: data.totp_status,
                temp_token: data.temp_token,
                user: data.user,
            }
        } catch (err: unknown) {
            const error = err as Error & {
                status: number
                data: { message?: string; email?: string }
            }
            if (error.status === 403) {
                return { verified: false, email: error.data?.email }
            }
            throw err
        }
    }

    async function verifyTotp(tempToken: string, totpCode: string): Promise<void> {
        const res = await api.post<{ token: string; user: User }>('/auth/totp/verify', {
            temp_token: tempToken,
            totp_code: totpCode,
        })
        const data = res as unknown as { token: string; user: User }
        setSession(data.token, data.user)
    }

    async function verifyEmail(
        token: string,
        password: string,
    ): Promise<{ totp_status: string; temp_token: string; user: User }> {
        const res = await api.post<{ totp_status: string; temp_token: string; user: User }>(
            '/auth/verify-email',
            { token, password },
        )
        return res as unknown as { totp_status: string; temp_token: string; user: User }
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
        verifyTotp,
        verifyEmail,
        setSession,
        logout,
        clearSession,
    }
})
