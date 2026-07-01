<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/services/api'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const email = ref((route.query.email as string) || '')
const resendLoading = ref(false)
const resendMessage = ref('')
const resendError = ref('')

onMounted(() => {
    if (auth.isAuthenticated && auth.currentUser) {
        email.value = auth.currentUser.email
    }
})

async function resendEmail() {
    if (!email.value) return

    resendLoading.value = true
    resendMessage.value = ''
    resendError.value = ''

    try {
        const res = await api.post<{ message: string }>(
            '/auth/resend-verification',
            { email: email.value },
        )
        resendMessage.value = res.message || 'Correo de verificación reenviado.'
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        resendError.value = e.data?.message || e.message || 'Error al reenviar el correo.'
    } finally {
        resendLoading.value = false
    }
}
</script>

<template>
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
        <div class="w-full space-y-6 text-center">
            <div class="rounded-full bg-yellow-100 p-4 mx-auto w-16 h-16 flex items-center justify-center">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900">
                Antes de continuar deberás confirmar tu correo electrónico
            </h1>

            <p class="text-gray-600">
                Hemos enviado un enlace de verificación a
                <strong class="text-gray-900">{{ email }}</strong>.
                Revisa tu bandeja de entrada y haz clic en el botón
                "Confirmar Correo Electrónico".
            </p>

            <p
                v-if="resendMessage"
                class="rounded-lg bg-green-50 p-3 text-sm text-green-600"
            >
                {{ resendMessage }}
            </p>

            <p
                v-if="resendError"
                class="rounded-lg bg-red-50 p-3 text-sm text-red-600"
            >
                {{ resendError }}
            </p>

            <button
                :disabled="resendLoading"
                class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                @click="resendEmail"
            >
                {{ resendLoading ? 'Enviando...' : 'Reenviar correo de verificación' }}
            </button>

            <p class="text-xs text-gray-500">
                ¿No es tu correo?
                <router-link
                    :to="{ name: 'register' }"
                    class="text-blue-600 hover:underline"
                >Crear cuenta con otro correo</router-link>
            </p>
        </div>
    </main>
</template>
