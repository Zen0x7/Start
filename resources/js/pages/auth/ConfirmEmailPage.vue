<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/services/api'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const token = route.params.token as string
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const verified = ref(false)

onMounted(async () => {
    try {
        const res = await api.get<{ email: string }>(
            `/auth/verify-email/${encodeURIComponent(token)}`,
        )
        email.value = res.data?.email ?? ''
    } catch {
        error.value =
            'El enlace de verificación no es válido o ha expirado.'
    }
})

async function handleSubmit() {
    error.value = ''
    loading.value = true

    try {
        const result = await auth.verifyEmail(token, password.value)
        verified.value = true

        setTimeout(() => {
            router.push({ name: 'home' })
        }, 2000)
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        error.value = e.data?.message || e.message || 'Error al verificar el correo.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
        <template v-if="error && !email">
            <div class="w-full space-y-4 text-center">
                <p class="rounded-lg bg-red-50 p-4 text-red-600">
                    {{ error }}
                </p>
                <router-link
                    :to="{ name: 'login' }"
                    class="text-blue-600 hover:underline"
                >Volver a Iniciar Sesión</router-link>
            </div>
        </template>

        <template v-else-if="verified">
            <div class="w-full space-y-4 text-center">
                <div class="rounded-full bg-green-100 p-4 mx-auto w-16 h-16 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Correo Electrónico Confirmado
                </h1>
                <p class="text-gray-600">Redirigiendo...</p>
            </div>
        </template>

        <template v-else>
            <form
                class="w-full space-y-6"
                @submit.prevent="handleSubmit"
            >
                <h1 class="text-center text-2xl font-bold">
                    Confirmar Correo Electrónico
                </h1>

                <p class="text-center text-gray-600">
                    Para confirmar tu correo
                    <strong class="text-gray-900">{{ email }}</strong>,
                    ingresa tu contraseña.
                </p>

                <p
                    v-if="error"
                    class="rounded-lg bg-red-50 p-3 text-sm text-red-600"
                >
                    {{ error }}
                </p>

                <div>
                    <label
                        for="password"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >Contraseña</label>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ loading ? 'Confirmando...' : 'Confirmar Correo Electrónico' }}
                </button>
            </form>
        </template>
    </main>
</template>
