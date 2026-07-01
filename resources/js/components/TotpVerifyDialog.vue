<script setup lang="ts">
import { ref } from 'vue'
import { api } from '@/services/api'

const emit = defineEmits<{
    (e: 'verified'): void
    (e: 'cancel'): void
}>()

const totpCode = ref('')
const loading = ref(false)
const error = ref('')

async function handleSubmit() {
    error.value = ''
    loading.value = true

    try {
        await api.post('/auth/totp/confirm-action', {
            totp_code: totpCode.value,
            action: 'confirm_operation',
        })
        emit('verified')
    } catch (err: unknown) {
        const e = err as Error & { data?: { message?: string } }
        error.value = e.data?.message || e.message || 'Código inválido.'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl">
            <h2 class="mb-2 text-lg font-bold">Confirmar Operación</h2>
            <p class="mb-4 text-sm text-gray-600">
                Ingresa tu código TOTP para confirmar esta acción.
            </p>

            <p
                v-if="error"
                class="mb-3 rounded-lg bg-red-50 p-2 text-sm text-red-600"
            >
                {{ error }}
            </p>

            <form @submit.prevent="handleSubmit">
                <input
                    v-model="totpCode"
                    type="text"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    required
                    autofocus
                    class="mb-4 w-full rounded-lg border border-gray-300 px-4 py-2 text-center text-2xl tracking-widest focus:border-blue-500 focus:outline-none"
                    placeholder="000000"
                />

                <div class="flex gap-3">
                    <button
                        type="button"
                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50"
                        @click="emit('cancel')"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="loading || totpCode.length !== 6"
                        class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ loading ? '...' : 'Confirmar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
