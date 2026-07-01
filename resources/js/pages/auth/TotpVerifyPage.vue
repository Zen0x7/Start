<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const tempToken = ref((route.query.temp_token as string) || '')
const totpCode = ref('')
const loading = ref(false)
const error = ref('')

async function handleSubmit() {
    error.value = ''
    loading.value = true

    try {
        await auth.verifyTotp(tempToken.value, totpCode.value)
        router.push({ name: 'dashboard' })
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        error.value = e.data?.message || e.message || 'Código inválido.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
        <form
            class="w-full space-y-6"
            @submit.prevent="handleSubmit"
        >
            <div class="text-center">
                <div class="rounded-full bg-blue-100 p-4 mx-auto w-16 h-16 flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold">Autenticación de Dos Factores</h1>
                <p class="mt-2 text-gray-600">
                    Ingresa el código de 6 dígitos de tu aplicación autenticadora.
                </p>
            </div>

            <p
                v-if="error"
                class="rounded-lg bg-red-50 p-3 text-sm text-red-600"
            >
                {{ error }}
            </p>

            <div>
                <input
                    id="totp-code"
                    v-model="totpCode"
                    type="text"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    required
                    autofocus
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-center text-3xl tracking-[0.5em] focus:border-blue-500 focus:outline-none"
                    placeholder="000000"
                />
            </div>

            <button
                type="submit"
                :disabled="loading || totpCode.length !== 6"
                class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
            >
                {{ loading ? 'Verificando...' : 'Verificar' }}
            </button>
        </form>
    </main>
</template>
