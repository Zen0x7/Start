<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function handleSubmit() {
    error.value = ''
    loading.value = true

    try {
        const result = await auth.login(email.value, password.value)

        if (!result.verified) {
            router.push({
                name: 'verify-email',
                query: { email: email.value },
            })
            return
        }

        router.push({ name: 'home' })
    } catch (err: unknown) {
        const e = err as Error & { data?: { errors?: Record<string, string[]> } }
        const messages = e.data?.errors
            ? Object.values(e.data.errors).flat().join(', ')
            : e.message || 'Error al iniciar sesión'
        error.value = messages
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
            <h1 class="text-center text-2xl font-bold">Iniciar Sesión</h1>

            <p
                v-if="error"
                class="rounded-lg bg-red-50 p-3 text-sm text-red-600"
            >
                {{ error }}
            </p>

            <div>
                <label
                    for="email"
                    class="mb-1 block text-sm font-medium text-gray-700"
                >Correo Electrónico</label>
                <input
                    id="email"
                    v-model="email"
                    type="email"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none"
                />
            </div>

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
                {{ loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
            </button>

            <p class="text-center text-sm text-gray-600">
                ¿No tienes cuenta?
                <router-link
                    :to="{ name: 'register' }"
                    class="text-blue-600 hover:underline"
                >Registrarse</router-link>
            </p>
        </form>
    </main>
</template>
